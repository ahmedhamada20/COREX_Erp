<?php

namespace App\Services\Customers;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Customer;
use App\Services\Accounting\PostCustomerOpeningBalance;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateCustomerWithAccount
{
    public function handle(int $tenantId, array $payload, string $actorName, ?UploadedFile $imageFile): Customer
    {
        return DB::transaction(function () use ($tenantId, $payload, $actorName, $imageFile) {

            // -----------------------------
            // Customer payload
            // -----------------------------
            $payload['user_id'] = $tenantId;
            $payload['updated_by'] = $actorName;
            $payload['status'] = (bool) ($payload['status'] ?? false);

            if (empty($payload['code'])) {
                $payload['code'] = $this->nextCustomerCode($tenantId);
            }

            $startBalance = (float) ($payload['start_balance'] ?? 0);

            // ✅ عند الإنشاء فقط (لو هتخلي الحقيقة من القيود: خليها 0)
            $payload['current_balance'] = $startBalance;

            if ($imageFile) {
                $payload['image'] = $imageFile->store('customers', 'public');
            }

            /** @var Customer $customer */
            $customer = Customer::create($payload);

            // -----------------------------
            // Ensure control account 1120
            // -----------------------------
            $control = $this->ensureCustomersControlAccount($tenantId, $actorName);

            // -----------------------------
            // Create customer sub-account
            // -----------------------------
            $accountNumber = $this->nextCustomerSubAccountNumber($tenantId);

            /** @var Account $customerAccount */
            $customerAccount = Account::create([
                'user_id' => $tenantId,
                'account_type_id' => $control->account_type_id,
                'parent_account_id' => $control->id,
                'name' => "عميل: {$customer->name} ({$customer->code})",
                'account_number' => $accountNumber,
                'start_balance' => $startBalance,
                'current_balance' => $startBalance, // لو JE فقط: خليها 0
                'status' => (bool) $customer->status,
                'date' => $customer->date ?? now()->toDateString(),
                'updated_by' => $actorName,
                'other_table_id' => "customers:{$customer->id}",
            ]);

            // Link customer
            $customer->update([
                'account_number' => $customerAccount->account_number,
            ]);

            // -----------------------------
            // ✅ Post opening balance via Service
            // -----------------------------
            if (abs($startBalance) > 0.0001) {
                app(PostCustomerOpeningBalance::class)->handle(
                    tenantId: $tenantId,
                    customerAccount: $customerAccount,
                    amount: $startBalance,
                    actorName: $actorName,
                    entryDate: ($customer->date ?? now()->toDateString()),
                    dims: [
                        'currency_code' => $payload['currency_code'] ?? 'EGP',
                        'fx_rate' => $payload['fx_rate'] ?? 1,
                        'branch_id' => $payload['branch_id'] ?? null,
                        'cost_center_id' => $payload['cost_center_id'] ?? null,
                        'project_id' => $payload['project_id'] ?? null,
                        'warehouse_id' => $payload['warehouse_id'] ?? null,

                        // ✅ عشان نسجل reference_id صح (customer_id)
                        'reference_type' => 'customers',
                        'reference_id' => (int) $customer->id,
                        'reference_label' => "{$customer->name} ({$customer->code})",
                    ]
                );
            }

            return $customer->refresh();
        });
    }

    // ======= نفس helpers بتوعك زي ما هم =======

    private function ensureCustomersControlAccount(int $tenantId, string $actorName): Account
    {
        // 1120 customers control
        $control = Account::query()
            ->where('user_id', $tenantId)
            ->where('account_number', '1120')
            ->lockForUpdate()
            ->first();

        if ($control) {
            return $control;
        }

        // Get/create asset type for this tenant
        $assetTypeId = AccountType::query()
            ->where('user_id', $tenantId)
            ->where('name', 'الأصول')
            ->value('id');

        if (! $assetTypeId) {
            $assetTypeId = AccountType::create([
                'user_id' => $tenantId,
                'name' => 'الأصول',
                'status' => true,
            ])->id;
        }

        // Ensure 1000 (assets root)
        $assets = Account::query()
            ->where('user_id', $tenantId)
            ->where('account_number', '1000')
            ->lockForUpdate()
            ->first();

        if (! $assets) {
            $assets = Account::create([
                'user_id' => $tenantId,
                'account_type_id' => $assetTypeId,
                'parent_account_id' => null,
                'name' => 'الأصول',
                'account_number' => '1000',
                'start_balance' => 0,
                'current_balance' => 0,
                'status' => true,
                'date' => now()->toDateString(),
                'updated_by' => $actorName,
            ]);
        }

        // Ensure 1010 (current assets)
        $currentAssets = Account::query()
            ->where('user_id', $tenantId)
            ->where('account_number', '1010')
            ->lockForUpdate()
            ->first();

        if (! $currentAssets) {
            $currentAssets = Account::create([
                'user_id' => $tenantId,
                'account_type_id' => $assetTypeId,
                'parent_account_id' => $assets->id,
                'name' => 'الأصول المتداولة',
                'account_number' => '1010',
                'start_balance' => 0,
                'current_balance' => 0,
                'status' => true,
                'date' => now()->toDateString(),
                'updated_by' => $actorName,
            ]);
        }

        // Create 1120 under 1010 (A/R Control)
        return Account::create([
            'user_id' => $tenantId,
            'account_type_id' => $assetTypeId,
            'parent_account_id' => $currentAssets->id,
            'name' => 'العملاء',
            'account_number' => '1120',
            'start_balance' => 0,
            'current_balance' => 0,
            'status' => true,
            'date' => now()->toDateString(),
            'updated_by' => $actorName,
            'notes' => 'A/R Control',
        ]);
    }

    private function nextCustomerCode(int $tenantId): string
    {
        $max = Customer::query()
            ->where('user_id', $tenantId)
            ->where('code', 'like', 'CUST-%')
            ->selectRaw('MAX(CAST(SUBSTRING(code, 6) AS UNSIGNED)) as max_no')
            ->lockForUpdate()
            ->value('max_no');

        $next = ((int) ($max ?? 0)) + 1;

        return 'CUST-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function nextCustomerSubAccountNumber(int $tenantId): string
    {
        $max = Account::query()
            ->where('user_id', $tenantId)
            ->where('account_number', 'like', '1120%')
            ->where('account_number', '!=', '1120')
            ->selectRaw('MAX(CAST(account_number AS UNSIGNED)) as max_no')
            ->lockForUpdate()
            ->value('max_no');

        $next = ((int) ($max ?? 11200000)) + 1;

        return (string) $next;
    }
}
