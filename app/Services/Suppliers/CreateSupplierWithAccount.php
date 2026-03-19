<?php

namespace App\Services\Suppliers;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Supplier;
use App\Services\Accounting\PostSupplierOpeningBalance;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateSupplierWithAccount
{
    public function handle(int $tenantId, array $payload, string $actorName, ?UploadedFile $imageFile): Supplier
    {
        return DB::transaction(function () use ($tenantId, $payload, $actorName, $imageFile) {

            $payload['user_id'] = $tenantId;
            $payload['updated_by'] = $actorName;
            $payload['status'] = (bool) ($payload['status'] ?? false);

            if (empty($payload['code'])) {
                $payload['code'] = $this->nextSupplierCode($tenantId);
            }

            $startBalance = (float) ($payload['start_balance'] ?? 0);

            // current_balance = start_balance عند الإنشاء فقط (لو هتخلي الحقيقة من القيود: خليه 0)
            $payload['current_balance'] = $startBalance;

            if ($imageFile) {
                $payload['image'] = $imageFile->store('suppliers', 'public');
            }

            /** @var Supplier $supplier */
            $supplier = Supplier::create($payload);

            // ✅ Ensure 2100 suppliers control under 2000 -> 2010
            $parent = $this->ensureSuppliersControlAccount($tenantId, $actorName);

            // ✅ Create supplier sub-account under 2100
            $accountNumber = $this->nextSupplierSubAccountNumber($tenantId);

            /** @var Account $account */
            $account = Account::create([
                'user_id' => $tenantId,
                'account_type_id' => $parent->account_type_id,
                'parent_account_id' => $parent->id,
                'name' => "مورد: {$supplier->name} ({$supplier->code})",
                'account_number' => $accountNumber,
                'start_balance' => $startBalance,
                'current_balance' => $startBalance,
                'status' => (bool) $supplier->status,
                'date' => $supplier->date ?? now()->toDateString(),
                'updated_by' => $actorName,
                'other_table_id' => "suppliers:{$supplier->id}",
            ]);

            $supplier->update([
                'account_number' => $account->account_number,
            ]);

            // ✅ Post Opening Balance JE (لو فيه رصيد افتتاحي)
            if (abs($startBalance) > 0.0001) {
                app(PostSupplierOpeningBalance::class)->handle(
                    tenantId: $tenantId,
                    supplierAccount: $account,
                    amount: $startBalance,
                    actorName: $actorName,
                    entryDate: ($supplier->date ?? now()->toDateString()),
                    dims: [
                        'currency_code' => $payload['currency_code'] ?? 'EGP',
                        'fx_rate' => $payload['fx_rate'] ?? 1,
                        'branch_id' => $payload['branch_id'] ?? null,
                        'cost_center_id' => $payload['cost_center_id'] ?? null,
                        'project_id' => $payload['project_id'] ?? null,
                        'warehouse_id' => $payload['warehouse_id'] ?? null,
                        // لو عايز تربط supplier_id داخل JE: عدل الـ service واضف reference_id
                    ]
                );
            }

            return $supplier->refresh();
        });
    }

    /**
     * ✅ Ensure: 2000 الخصوم -> 2010 الخصوم المتداولة -> 2100 الموردين (A/P Control)
     */
    private function ensureSuppliersControlAccount(int $tenantId, string $actorName): Account
    {
        $control = Account::query()
            ->where('user_id', $tenantId)
            ->where('account_number', '2100')
            ->lockForUpdate()
            ->first();

        if ($control) {
            return $control;
        }

        // Get/create liabilities type
        $liabTypeId = AccountType::query()
            ->where('user_id', $tenantId)
            ->where('name', 'الخصوم')
            ->value('id');

        if (! $liabTypeId) {
            $liabTypeId = AccountType::create([
                'user_id' => $tenantId,
                'name' => 'الخصوم',
                'status' => true,
            ])->id;
        }

        // Ensure 2000
        $liabilities = Account::query()
            ->where('user_id', $tenantId)
            ->where('account_number', '2000')
            ->lockForUpdate()
            ->first();

        if (! $liabilities) {
            $liabilities = Account::create([
                'user_id' => $tenantId,
                'account_type_id' => $liabTypeId,
                'parent_account_id' => null,
                'name' => 'الخصوم',
                'account_number' => '2000',
                'start_balance' => 0,
                'current_balance' => 0,
                'status' => true,
                'date' => now()->toDateString(),
                'updated_by' => $actorName,
            ]);
        }

        // Ensure 2010
        $currentLiab = Account::query()
            ->where('user_id', $tenantId)
            ->where('account_number', '2010')
            ->lockForUpdate()
            ->first();

        if (! $currentLiab) {
            $currentLiab = Account::create([
                'user_id' => $tenantId,
                'account_type_id' => $liabTypeId,
                'parent_account_id' => $liabilities->id,
                'name' => 'الخصوم المتداولة',
                'account_number' => '2010',
                'start_balance' => 0,
                'current_balance' => 0,
                'status' => true,
                'date' => now()->toDateString(),
                'updated_by' => $actorName,
            ]);
        }

        // Create 2100
        return Account::create([
            'user_id' => $tenantId,
            'account_type_id' => $liabTypeId,
            'parent_account_id' => $currentLiab->id,
            'name' => 'الموردين',
            'account_number' => '2100',
            'start_balance' => 0,
            'current_balance' => 0,
            'status' => true,
            'date' => now()->toDateString(),
            'updated_by' => $actorName,
            'notes' => 'A/P Control',
        ]);
    }

    private function nextSupplierCode(int $tenantId): string
    {
        $max = Supplier::query()
            ->where('user_id', $tenantId)
            ->where('code', 'like', 'SUP-%')
            ->selectRaw('MAX(CAST(SUBSTRING(code, 5) AS UNSIGNED)) as max_no')
            ->lockForUpdate()
            ->value('max_no');

        $next = ((int) ($max ?? 0)) + 1;

        return 'SUP-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    /**
     * ✅ رقم حساب المورد: 2100xxxx (تسلسلي) تحت Control 2100
     */
    private function nextSupplierSubAccountNumber(int $tenantId): string
    {
        $max = Account::query()
            ->where('user_id', $tenantId)
            ->where('account_number', 'like', '2100%')
            ->where('account_number', '!=', '2100')
            ->selectRaw('MAX(CAST(account_number AS UNSIGNED)) as max_no')
            ->lockForUpdate()
            ->value('max_no');

        $next = ((int) ($max ?? 21000000)) + 1;

        return (string) $next;
    }
}
