<?php

namespace App\Services\Suppliers;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Supplier;
use App\Services\Accounting\PostSupplierOpeningBalance;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateSupplierWithAccount
{
    public function handle(
        int $tenantId,
        Supplier $supplier,
        array $payload,
        string $actorName,
        ?UploadedFile $imageFile
    ): Supplier {
        return DB::transaction(function () use ($tenantId, $supplier, $payload, $actorName, $imageFile) {

            $payload['updated_by'] = $actorName;
            $payload['status'] = (bool) ($payload['status'] ?? false);

            if (array_key_exists('code', $payload) && empty($payload['code'])) {
                $payload['code'] = $this->nextSupplierCode($tenantId);
            }

            // ✅ ممنوع تعديل current_balance يدويًا
            unset($payload['current_balance']);

            // ✅ فرق start_balance لو اتغير
            $wantsStartBalanceChange = array_key_exists('start_balance', $payload);
            $newStartBalance = $wantsStartBalanceChange ? (float) $payload['start_balance'] : null;
            $oldStartBalance = (float) ($supplier->start_balance ?? 0);

            // remove_image
            $removeImage = (bool) ($payload['remove_image'] ?? false);
            unset($payload['remove_image']);

            if ($removeImage) {
                if ($supplier->image && Storage::disk('public')->exists($supplier->image)) {
                    Storage::disk('public')->delete($supplier->image);
                }
                $payload['image'] = null;
            }

            // رفع صورة جديدة
            if ($imageFile) {
                if ($supplier->image && Storage::disk('public')->exists($supplier->image)) {
                    Storage::disk('public')->delete($supplier->image);
                }
                $payload['image'] = $imageFile->store('suppliers', 'public');
            }

            // Update supplier
            $supplier->update($payload);

            // Find linked account
            $account = $this->findSupplierAccount($tenantId, $supplier);

            // لو الحساب مش موجود: أنشئه تحت 2100
            if (! $account) {
                $parent = $this->ensureSuppliersControlAccount($tenantId, $actorName);

                $startBalance = (float) ($supplier->start_balance ?? 0);
                $accountNumber = $this->nextSupplierSubAccountNumber($tenantId);

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

                $supplier->update(['account_number' => $account->account_number]);

                // ✅ لو فيه رصيد افتتاحي: اعمله JE (أول مرة)
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
                        ]
                    );
                }

                return $supplier->refresh();
            }

            // Update existing account (بدون current_balance)
            $account->update([
                'name' => "مورد: {$supplier->name} ({$supplier->code})",
                'status' => (bool) $supplier->status,
                'updated_by' => $actorName,
                'start_balance' => (float) ($supplier->start_balance ?? $account->start_balance ?? 0),
                'date' => $supplier->date ?? ($account->date ?? now()->toDateString()),
            ]);

            // ✅ لو start_balance اتغير: قيد تسوية بالفرق (بنفس service)
            if ($wantsStartBalanceChange) {
                $diff = (float) $newStartBalance - (float) $oldStartBalance;

                if (abs($diff) > 0.0001) {
                    app(PostSupplierOpeningBalance::class)->handle(
                        tenantId: $tenantId,
                        supplierAccount: $account,
                        amount: $diff, // الفرق فقط
                        actorName: $actorName,
                        entryDate: ($supplier->date ?? now()->toDateString()),
                        dims: [
                            'currency_code' => $payload['currency_code'] ?? 'EGP',
                            'fx_rate' => $payload['fx_rate'] ?? 1,
                            'branch_id' => $payload['branch_id'] ?? null,
                            'cost_center_id' => $payload['cost_center_id'] ?? null,
                            'project_id' => $payload['project_id'] ?? null,
                            'warehouse_id' => $payload['warehouse_id'] ?? null,
                        ]
                    );
                }
            }

            return $supplier->refresh();
        });
    }

    private function findSupplierAccount(int $tenantId, Supplier $supplier): ?Account
    {
        if (! empty($supplier->account_number)) {
            $acc = Account::query()
                ->where('user_id', $tenantId)
                ->where('account_number', $supplier->account_number)
                ->lockForUpdate()
                ->first();

            if ($acc) {
                return $acc;
            }
        }

        return Account::query()
            ->where('user_id', $tenantId)
            ->where('other_table_id', "suppliers:{$supplier->id}")
            ->lockForUpdate()
            ->first();
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

    /**
     * ✅ Ensure: 2000 -> 2010 -> 2100 (نفس Create) بدون Reflection
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
}
