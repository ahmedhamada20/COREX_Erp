<?php

namespace App\Services\Accounting;

use App\Models\Account;

class AccountValidator
{
    /**
     * Validate that an account allows posting
     *
     * @throws \RuntimeException
     */
    public function validatePostingAccount(int $accountId, int $tenantId): Account
    {
        $account = Account::query()
            ->where('user_id', $tenantId)
            ->findOrFail($accountId);

        // ✅ Check if account type allows posting
        if (! $account->type->allow_posting) {
            throw new \RuntimeException(
                "Account '{$account->name}' ({$account->account_number}) ".
                'does not allow posting. This is a group/summary account only. '.
                'Please use a detail account for transactions.'
            );
        }

        return $account;
    }

    /**
     * Validate and find a customer account
     *
     * @throws \RuntimeException
     */
    public function validateCustomerAccount(string $accountNumber, string $customerName, int $tenantId): Account
    {
        if (empty($accountNumber)) {
            throw new \RuntimeException('Customer account number cannot be empty');
        }

        $account = Account::query()
            ->where('user_id', $tenantId)
            ->where('account_number', $accountNumber)
            ->first();

        if (! $account) {
            throw new \RuntimeException(
                "Customer account '{$accountNumber}' not found for customer '{$customerName}'. ".
                'Please create the account first or update the customer record.'
            );
        }

        // ✅ Validate posting capability
        return $this->validatePostingAccount($account->id, $tenantId);
    }

    /**
     * Validate and find a supplier account
     *
     * @throws \RuntimeException
     */
    public function validateSupplierAccount(string $accountNumber, string $supplierName, int $tenantId): Account
    {
        if (empty($accountNumber)) {
            throw new \RuntimeException('Supplier account number cannot be empty');
        }

        $account = Account::query()
            ->where('user_id', $tenantId)
            ->where('account_number', $accountNumber)
            ->first();

        if (! $account) {
            throw new \RuntimeException(
                "Supplier account '{$accountNumber}' not found for supplier '{$supplierName}'. ".
                'Please create the account first or update the supplier record.'
            );
        }

        // ✅ Validate posting capability
        return $this->validatePostingAccount($account->id, $tenantId);
    }
}
