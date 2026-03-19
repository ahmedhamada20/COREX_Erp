<?php

namespace App\Services\Customers;

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\AccountType;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateCustomerWithAccount
{
    public function handle(
        int $tenantId,
        Customer $customer,
        array $payload,
        string $actorName,
        ?UploadedFile $imageFile
    ): Customer {
        return DB::transaction(function () use ($tenantId, $customer, $payload, $actorName, $imageFile) {

            $payload['updated_by'] = $actorName;
            $payload['status'] = (bool) ($payload['status'] ?? false);

            // لو بعت code فاضي: اعمل code جديد
            if (array_key_exists('code', $payload) && empty($payload['code'])) {
                $payload['code'] = $this->nextCustomerCode($tenantId);
            }

            // ✅ ممنوع تغيير current_balance من هنا
            unset($payload['current_balance']);

            // هنحتاج الفرق لو start_balance اتغير
            $wantsStartBalanceChange = array_key_exists('start_balance', $payload);
            $newStartBalance = $wantsStartBalanceChange ? (float) $payload['start_balance'] : null;
            $oldStartBalance = (float) ($customer->start_balance ?? 0);

            // صورة
            if ($imageFile) {
                if ($customer->image && Storage::disk('public')->exists($customer->image)) {
                    Storage::disk('public')->delete($customer->image);
                }
                $payload['image'] = $imageFile->store('customers', 'public');
            }

            // Update customer
            $customer->update($payload);

            // Find linked account
            $account = $this->findCustomerAccount($tenantId, $customer);

            if ($account) {
                $account->update([
                    'name' => "عميل: {$customer->name} ({$customer->code})",
                    'status' => (bool) $customer->status,
                    'updated_by' => $actorName,
                    // start_balance في الحساب: خليه يطابق customer.start_balance
                    // (لو أنت عايز start_balance "افتتاحي فقط" وممنوع يتغير، شيل السطر ده)
                    'start_balance' => (float) ($customer->start_balance ?? $account->start_balance ?? 0),
                    'date' => $customer->date ?? ($account->date ?? now()->toDateString()),
                ]);
            }

            // ✅ لو start_balance اتغير: اعمل قيد تسوية بالفرق
            if ($wantsStartBalanceChange && $account) {
                $diff = (float) $newStartBalance - (float) $oldStartBalance;

                if (abs($diff) > 0.0001) {
                    $this->postOpeningBalanceAdjustmentForCustomer(
                        tenantId: $tenantId,
                        customer: $customer,
                        customerAccount: $account,
                        diff: $diff, // الفرق فقط
                        actorName: $actorName,
                        entryDate: ($customer->date ?? now()->toDateString()),
                        currency: ($payload['currency_code'] ?? 'EGP'),
                        branchId: ($payload['branch_id'] ?? null),
                        costCenterId: ($payload['cost_center_id'] ?? null),
                        projectId: ($payload['project_id'] ?? null),
                        warehouseId: ($payload['warehouse_id'] ?? null),
                    );
                }
            }

            return $customer;
        });
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function findCustomerAccount(int $tenantId, Customer $customer): ?Account
    {
        // 1) Primary: account_number
        if (! empty($customer->account_number)) {
            $acc = Account::query()
                ->where('user_id', $tenantId)
                ->where('account_number', $customer->account_number)
                ->first();

            if ($acc) {
                return $acc;
            }
        }

        // 2) Fallback: other_table_id
        return Account::query()
            ->where('user_id', $tenantId)
            ->where('other_table_id', "customers:{$customer->id}")
            ->first();
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

    // ------------------------------------------------------------------
    // Accounting: Adjustment JE for start_balance difference
    // ------------------------------------------------------------------

    private function postOpeningBalanceAdjustmentForCustomer(
        int $tenantId,
        Customer $customer,
        Account $customerAccount,
        float $diff, // may be +/- (new - old)
        string $actorName,
        string $entryDate,
        string $currency = 'EGP',
        ?int $branchId = null,
        ?int $costCenterId = null,
        ?int $projectId = null,
        ?int $warehouseId = null,
    ): void {
        // Offset account (3900)
        $offsetAccount = $this->ensureOpeningBalanceOffsetAccount($tenantId, $actorName);

        // diff موجب: زودنا رصيد العميل => Debit العميل / Credit الأوفست
        // diff سالب: قللنا رصيد العميل => Credit العميل / Debit الأوفست
        $debitCustomer = $diff > 0 ? $diff : 0.0;
        $creditCustomer = $diff < 0 ? abs($diff) : 0.0;

        $debitOffset = $creditCustomer;
        $creditOffset = $debitCustomer;

        $total = abs($diff);

        $entry = JournalEntry::create([
            'user_id' => $tenantId,
            'entry_number' => $this->nextJournalEntryNumber($tenantId),
            'entry_date' => $entryDate,
            'source' => 'opening_balance_adjustment',
            'reference_type' => 'customers',
            'reference_id' => (int) $customer->id,
            'description' => "تسوية رصيد افتتاحي - {$customer->name} ({$customer->code}) - فرق: {$diff}",
            'total_debit' => (string) $total,
            'total_credit' => (string) $total,
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => null,
        ]);

        JournalEntryLine::create([
            'user_id' => $tenantId,
            'journal_entry_id' => $entry->id,
            'account_id' => $customerAccount->id,
            'cost_center_id' => $costCenterId,
            'project_id' => $projectId,
            'branch_id' => $branchId,
            'warehouse_id' => $warehouseId,
            'debit' => (string) $debitCustomer,
            'credit' => (string) $creditCustomer,
            'currency_code' => $currency,
            'fx_rate' => '1',
            'memo' => 'Opening balance adjustment - customer',
            'line_no' => 1,
        ]);

        JournalEntryLine::create([
            'user_id' => $tenantId,
            'journal_entry_id' => $entry->id,
            'account_id' => $offsetAccount->id,
            'cost_center_id' => $costCenterId,
            'project_id' => $projectId,
            'branch_id' => $branchId,
            'warehouse_id' => $warehouseId,
            'debit' => (string) $debitOffset,
            'credit' => (string) $creditOffset,
            'currency_code' => $currency,
            'fx_rate' => '1',
            'memo' => 'Opening balance adjustment - offset',
            'line_no' => 2,
        ]);

        // ✅ Update AccountBalance (لو بتستخدمه)
        $this->applyToAccountBalance($tenantId, $customerAccount->id, $currency, $branchId, $debitCustomer, $creditCustomer);
        $this->applyToAccountBalance($tenantId, $offsetAccount->id, $currency, $branchId, $debitOffset, $creditOffset);
    }

    private function ensureOpeningBalanceOffsetAccount(int $tenantId, string $actorName): Account
    {
        // Equity type
        $equityTypeId = AccountType::query()
            ->where('user_id', $tenantId)
            ->where('name', 'حقوق الملكية')
            ->value('id');

        if (! $equityTypeId) {
            $equityTypeId = AccountType::create([
                'user_id' => $tenantId,
                'name' => 'حقوق الملكية',
                'status' => true,
            ])->id;
        }

        // Ensure 3000
        $equityRoot = Account::query()
            ->where('user_id', $tenantId)
            ->where('account_number', '3000')
            ->lockForUpdate()
            ->first();

        if (! $equityRoot) {
            $equityRoot = Account::create([
                'user_id' => $tenantId,
                'account_type_id' => $equityTypeId,
                'parent_account_id' => null,
                'name' => 'حقوق الملكية',
                'account_number' => '3000',
                'start_balance' => 0,
                'current_balance' => 0,
                'status' => true,
                'date' => now()->toDateString(),
                'updated_by' => $actorName,
            ]);
        }

        // Ensure 3900
        $offset = Account::query()
            ->where('user_id', $tenantId)
            ->where('account_number', '3900')
            ->lockForUpdate()
            ->first();

        if ($offset) {
            return $offset;
        }

        return Account::create([
            'user_id' => $tenantId,
            'account_type_id' => $equityTypeId,
            'parent_account_id' => $equityRoot->id,
            'name' => 'رصيد افتتاحي',
            'account_number' => '3900',
            'start_balance' => 0,
            'current_balance' => 0,
            'status' => true,
            'date' => now()->toDateString(),
            'updated_by' => $actorName,
            'notes' => 'Opening Balance Offset',
        ]);
    }

    private function nextJournalEntryNumber(int $tenantId): string
    {
        $max = JournalEntry::query()
            ->where('user_id', $tenantId)
            ->selectRaw('MAX(CAST(entry_number AS UNSIGNED)) as max_no')
            ->lockForUpdate()
            ->value('max_no');

        return (string) (((int) ($max ?? 0)) + 1);
    }

    private function applyToAccountBalance(
        int $tenantId,
        int $accountId,
        string $currency,
        ?int $branchId,
        float $debit,
        float $credit
    ): void {
        app(\App\Services\Accounting\AccountBalanceUpdater::class)->apply(
            userId: $tenantId,
            accountId: $accountId,
            currency: $currency,
            branchId: $branchId,
            debit: $debit,
            credit: $credit
        );
    }
}
