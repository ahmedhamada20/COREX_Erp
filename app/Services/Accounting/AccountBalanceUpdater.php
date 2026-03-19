<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountBalance;

class AccountBalanceUpdater
{
    public function apply(
        int $userId,
        int $accountId,
        string $currency,
        ?int $branchId,
        float $debit,
        float $credit
    ): void {
        $currency = $currency ?: 'EGP';
        $debit = round((float) $debit, 4);
        $credit = round((float) $credit, 4);

        $bal = AccountBalance::query()
            ->where('user_id', $userId)
            ->where('account_id', $accountId)
            ->where('currency_code', $currency)
            ->where('branch_id', $branchId)
            ->lockForUpdate()
            ->first();

        if (! $bal) {
            AccountBalance::create([
                'user_id' => $userId,
                'account_id' => $accountId,
                'currency_code' => $currency,
                'branch_id' => $branchId,
                'debit_total' => 0,
                'credit_total' => 0,
                'balance' => 0,
            ]);

            $bal = AccountBalance::query()
                ->where('user_id', $userId)
                ->where('account_id', $accountId)
                ->where('currency_code', $currency)
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->first();
        }

        $newDebitTotal = round(((float) $bal->debit_total) + $debit, 4);
        $newCreditTotal = round(((float) $bal->credit_total) + $credit, 4);

        $normal = $this->normalSideForAccount($accountId);

        $newBalance = $normal === 'credit'
            ? round($newCreditTotal - $newDebitTotal, 4)
            : round($newDebitTotal - $newCreditTotal, 4);

        AccountBalance::query()
            ->whereKey($bal->id)
            ->update([
                'debit_total' => $newDebitTotal,
                'credit_total' => $newCreditTotal,
                'balance' => $newBalance,
                'updated_at' => now(),
            ]);

        Account::query()
            ->where('user_id', $userId)
            ->whereKey($accountId)
            ->update([
                'current_balance' => $newBalance,
                'updated_at' => now(),
            ]);
    }

    private function normalSideForAccount(int $accountId): string
    {
        $row = Account::query()
            ->join('account_types', 'accounts.account_type_id', '=', 'account_types.id')
            ->where('accounts.id', $accountId)
            ->select('account_types.normal_side')
            ->first();

        $side = (string) ($row?->normal_side ?? 'debit');

        return $side === 'credit' ? 'credit' : 'debit';
    }
}
