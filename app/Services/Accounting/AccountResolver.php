<?php

namespace App\Services\Accounting;

use App\Models\Account;

class AccountResolver
{
    public function byNumber(int $tenantId, string $number, string $label = ''): int
    {
        $id = Account::where('user_id', $tenantId)
            ->where('account_number', $number)
            ->value('id');

        if (! $id) {
            $name = $label ? " ({$label})" : '';
            throw new \RuntimeException("Missing account {$number}{$name} for tenant={$tenantId}. Run AccountSeeder or fix chart of accounts.");
        }

        return (int) $id;
    }

    // ✅ Cash transactional account (under 1100 group)
    public function cash(int $tenantId): int
    {
        return $this->byNumber($tenantId, '1101', 'Cash Drawer');
    }

    public function cardClearing(int $tenantId): int
    {
        return $this->byNumber($tenantId, '1114', 'Card Clearing');
    }

    public function walletClearing(int $tenantId): int
    {
        return $this->byNumber($tenantId, '1115', 'Wallet Clearing');
    }

    public function inventory(int $tenantId): int
    {
        return $this->byNumber($tenantId, '1141', 'Inventory (Merchandise)');
    }

    public function apControl(int $tenantId): int
    {
        return $this->byNumber($tenantId, '2100', 'A/P Control');
    }

    public function arControl(int $tenantId): int
    {
        return $this->byNumber($tenantId, '1120', 'A/R Control');
    }

    public function inputVat(int $tenantId): int
    {
        return $this->byNumber($tenantId, '2122', 'VAT Input');
    }

    public function outputVat(int $tenantId): int
    {
        return $this->byNumber($tenantId, '2121', 'VAT Output');
    }

    public function salesRevenue(int $tenantId): int
    {
        return $this->byNumber($tenantId, '4100', 'Sales Revenue');
    }

    public function salesReturns(int $tenantId): int
    {
        return $this->byNumber($tenantId, '4110', 'Sales Returns');
    }

    public function cogs(int $tenantId): int
    {
        return $this->byNumber($tenantId, '5100', 'COGS');
    }

    public function shippingPurchases(int $tenantId): int
    {
        return $this->byNumber($tenantId, '5121', 'Purchase Freight');
    }

    public function purchaseCharges(int $tenantId): int
    {
        return $this->byNumber($tenantId, '5120', 'Purchase Charges');
    }

    public function purchasesExpense(int $tenantId): int
    {
        return $this->byNumber($tenantId, '5120', 'Purchases Expense');
    }

    // ✅ Opening Balance Offset (Equity)
    public function openingBalanceOffset(int $tenantId): int
    {
        return $this->byNumber($tenantId, '3900', 'Opening Balance Offset');
    }

    public function salesDiscount(int $tenantId): int
    {
        return $this->byNumber($tenantId, '4120', 'Sales Discount Allowed');
    }
}
