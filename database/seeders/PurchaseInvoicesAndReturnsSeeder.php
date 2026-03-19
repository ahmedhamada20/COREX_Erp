<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Items;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PurchaseInvoicesAndReturnsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $owners = User::whereNull('owner_user_id')->orderBy('id')->get();

            if ($owners->isEmpty()) {
                $this->command?->warn('No owner user found. Seed UsersSeeder first.');

                return;
            }

            foreach ($owners as $owner) {

                // =========================================
                // 1) Required accounts from your Chart
                // =========================================
                $accAPControl = $this->findAccount($owner->id, '2100'); // الموردين (A/P Control)

                // accounts for payments (optional linking if purchase_payments.account_id exists)
                $accCashMain = $this->findAccount($owner->id, '1101'); // خزينة رئيسية
                $accBankCib = $this->findAccount($owner->id, '1111'); // بنك CIB

                if (! $accAPControl) {
                    $this->command?->warn("Owner {$owner->id}: Missing account 2100 (Suppliers Control). Run AccountSeeder first.");

                    continue;
                }

                // =========================================
                // 2) Suppliers & Items must exist
                // =========================================
                $suppliers = Supplier::where('user_id', $owner->id)->inRandomOrder()->take(20)->get();
                $items = Items::where('user_id', $owner->id)->inRandomOrder()->take(80)->get();

                if ($suppliers->isEmpty()) {
                    $this->command?->warn("Owner {$owner->id}: No suppliers found.");

                    continue;
                }

                if ($items->isEmpty()) {
                    $this->command?->warn("Owner {$owner->id}: No items found.");

                    continue;
                }

                // =========================================
                // 3) Create supplier sub-accounts & link
                // =========================================
                foreach ($suppliers as $supplier) {
                    $supplierAcc = $this->upsertSupplierAccount($owner, $accAPControl, $supplier);

                    if (Schema::hasColumn('suppliers', 'account_id')) {
                        $supplier->update(['account_id' => $supplierAcc->id]);
                    }
                }

                // =========================================
                // 4) Seed Purchase Invoices (+ Items + Payments)
                // =========================================
                $invoiceCount = 25;

                for ($i = 1; $i <= $invoiceCount; $i++) {

                    $supplier = $suppliers->random();
                    $supplierAcc = $this->upsertSupplierAccount($owner, $accAPControl, $supplier);

                    $invoiceDate = now()->subDays(random_int(1, 90))->toDateString();

                    // سنعمل سيناريوهات واقعية:
                    // cash -> paid
                    // credit -> posted/partial/paid
                    $paymentType = collect(['cash', 'credit'])->random();
                    $statusPool = $paymentType === 'cash'
                        ? ['paid']
                        : ['posted', 'partial', 'paid', 'cancelled', 'draft'];

                    $status = collect($statusPool)->random();

                    $dueDate = $paymentType === 'credit'
                        ? now()->addDays(random_int(7, 45))->toDateString()
                        : null;

                    $invoice = PurchaseInvoice::create([
                        'user_id' => $owner->id,
                        'supplier_id' => $supplier->id,

                        'purchase_invoice_code' => 'PI-'.Str::upper(Str::random(10)),
                        'invoice_number' => 'PINV-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),

                        'invoice_date' => $invoiceDate,
                        'purchase_order_id' => null,

                        'payment_type' => $paymentType,
                        'due_date' => $dueDate,

                        // currency
                        'currency_code' => 'EGP',
                        'exchange_rate' => 1,

                        'tax_included' => false,

                        // totals: will be updated after lines
                        'subtotal_before_discount' => 0,

                        'discount_type' => 'none',
                        'discount_rate' => null,
                        'discount_value' => 0,

                        'shipping_cost' => 0,
                        'other_charges' => 0,

                        'subtotal' => 0,
                        'tax_value' => 0,
                        'total' => 0,

                        'paid_amount' => 0,
                        'remaining_amount' => 0,

                        'status' => $status,

                        'posted_at' => in_array($status, ['posted', 'partial', 'paid'], true) ? now() : null,
                        'posted_by' => in_array($status, ['posted', 'partial', 'paid'], true) ? $owner->name : null,

                        'notes' => 'Seeded purchase invoice',
                        'date' => now()->toDateString(),
                        'updated_by' => $owner->name,
                    ]);

                    // ---- invoice charges (sometimes)
                    $shipping = random_int(0, 1) ? round(random_int(0, 300), 2) : 0;
                    $otherCharges = random_int(0, 1) ? round(random_int(0, 200), 2) : 0;

                    // ---- create invoice items
                    $linesCount = random_int(2, 7);

                    $subtotalBeforeDiscount = 0.0;
                    $linesDiscountTotal = 0.0;
                    $linesTaxTotal = 0.0;

                    for ($l = 1; $l <= $linesCount; $l++) {

                        $item = $items->random();

                        $qty = round((random_int(1, 200) / 10), 2);
                        $unit = round(random_int(50, 1500), 2);

                        $lineSub = round($qty * $unit, 2);
                        $subtotalBeforeDiscount += $lineSub;

                        // line discount
                        $lineDiscountType = collect(['none', 'percent', 'fixed'])->random();
                        $lineDiscountRate = null;
                        $lineDiscountValue = 0.0;

                        if ($lineDiscountType === 'percent') {
                            $lineDiscountRate = round(random_int(0, 15), 2);
                            $lineDiscountValue = round($lineSub * ($lineDiscountRate / 100), 2);
                        } elseif ($lineDiscountType === 'fixed') {
                            $lineDiscountValue = round(min($lineSub, random_int(0, 200)), 2);
                        }

                        $afterLineDiscount = round($lineSub - $lineDiscountValue, 2);
                        if ($afterLineDiscount < 0) {
                            $afterLineDiscount = 0;
                        }

                        $linesDiscountTotal += $lineDiscountValue;

                        // tax on line (after line discount)
                        $taxRate = collect([0, 5, 10, 14])->random();
                        $taxValue = round($afterLineDiscount * ($taxRate / 100), 2);
                        $linesTaxTotal += $taxValue;

                        $lineTotal = round($afterLineDiscount + $taxValue, 2);

                        // warehouse: if you have warehouses table/model, set warehouse_id here.
                        // We'll store snapshot name anyway
                        PurchaseInvoiceItem::create([
                            'user_id' => $owner->id,
                            'purchase_invoice_id' => $invoice->id,
                            'item_id' => $item->id,

                            'warehouse_name_snapshot' => 'المخزن الرئيسي',

                            'quantity' => $qty,
                            'unit_price' => $unit,

                            'discount_type' => $lineDiscountType,
                            'discount_rate' => $lineDiscountRate,
                            'discount_value' => $lineDiscountValue,

                            'tax_rate' => $taxRate ?: null,
                            'tax_value' => $taxValue,

                            'line_subtotal' => $lineSub,
                            'line_total' => $lineTotal,

                            'date' => now()->toDateString(),
                            'updated_by' => $owner->name,
                        ]);
                    }

                    // ---- invoice-level discount
                    $invDiscType = collect(['none', 'percent', 'fixed'])->random();
                    $invDiscRate = null;
                    $invDiscValue = 0.0;

                    if ($invDiscType === 'percent') {
                        $invDiscRate = round(random_int(0, 10), 2);
                        $invDiscValue = round($subtotalBeforeDiscount * ($invDiscRate / 100), 2);
                    } elseif ($invDiscType === 'fixed') {
                        $invDiscValue = round(min($subtotalBeforeDiscount, random_int(0, 500)), 2);
                    }

                    // subtotal after discounts (lines + invoice)
                    $subtotalAfterLineDiscounts = round($subtotalBeforeDiscount - $linesDiscountTotal, 2);
                    if ($subtotalAfterLineDiscounts < 0) {
                        $subtotalAfterLineDiscounts = 0;
                    }

                    $subtotalAfterAllDiscounts = round($subtotalAfterLineDiscounts - $invDiscValue, 2);
                    if ($subtotalAfterAllDiscounts < 0) {
                        $subtotalAfterAllDiscounts = 0;
                    }

                    // total before payments
                    $taxTotal = round($linesTaxTotal, 2);
                    $total = round($subtotalAfterAllDiscounts + $taxTotal + $shipping + $otherCharges, 2);

                    // ---- payments based on status/payment_type
                    $paid = 0.0;

                    if ($status === 'paid') {
                        $paid = $total;
                    } elseif ($status === 'partial') {
                        $paid = round($total * (random_int(10, 80) / 100), 2);
                    } else {
                        $paid = 0.0;
                    }

                    if ($status === 'cancelled' || $status === 'draft') {
                        $paid = 0.0;
                    }

                    $remaining = round($total - $paid, 2);
                    if ($remaining < 0) {
                        $remaining = 0;
                    }

                    // update invoice totals
                    $invoice->update([
                        'subtotal_before_discount' => round($subtotalBeforeDiscount, 2),

                        'discount_type' => $invDiscType,
                        'discount_rate' => $invDiscRate,
                        'discount_value' => round($invDiscValue, 2),

                        'shipping_cost' => $shipping,
                        'other_charges' => $otherCharges,

                        'subtotal' => $subtotalAfterAllDiscounts,
                        'tax_value' => $taxTotal,
                        'total' => $total,

                        'paid_amount' => $paid,
                        'remaining_amount' => $remaining,

                        // ضبط status منطقيًا
                        'status' => $this->normalizeStatus($status, $total, $paid),
                    ]);

                    // create payments rows (if table exists)
                    if (Schema::hasTable('purchase_payments') && $paid > 0 && ! in_array($status, ['draft', 'cancelled'], true)) {
                        $this->seedPaymentsForInvoice(
                            ownerId: $owner->id,
                            invoiceId: $invoice->id,
                            paid: $paid,
                            invoiceDate: $invoiceDate,
                            updatedBy: $owner->name,
                            cashAccountId: $accCashMain?->id,
                            bankAccountId: $accBankCib?->id
                        );
                    }

                    // link supplier account to supplier (again just in case)
                    if (Schema::hasColumn('suppliers', 'account_id')) {
                        $supplier->update(['account_id' => $supplierAcc->id]);
                    }
                }

                // =========================================
                // 5) Seed Purchase Returns (linked to invoices sometimes)
                // =========================================
                $returnCount = 10;

                $invoices = PurchaseInvoice::where('user_id', $owner->id)->inRandomOrder()->take(10)->get();

                for ($i = 1; $i <= $returnCount; $i++) {

                    $supplier = $suppliers->random();

                    $status = collect(['draft', 'posted', 'cancelled'])->random();
                    $returnDate = now()->subDays(random_int(1, 90))->toDateString();

                    $linkedInvoice = (Schema::hasColumn('purchase_returns', 'purchase_invoice_id') && $invoices->isNotEmpty() && random_int(0, 1))
                        ? $invoices->random()
                        : null;

                    $ret = PurchaseReturn::create([
                        'user_id' => $owner->id,
                        'supplier_id' => $supplier->id,
                        'purchase_invoice_id' => $linkedInvoice?->id,

                        'purchase_return_code' => 'PR-'.Str::upper(Str::random(10)),
                        'return_number' => 'PRET-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                        'return_date' => $returnDate,

                        'tax_included' => false,

                        'subtotal' => 0,
                        'tax_value' => 0,
                        'total' => 0,

                        'status' => $status,
                        'posted_at' => $status === 'posted' ? now() : null,
                        'posted_by' => $status === 'posted' ? $owner->name : null,

                        'notes' => 'Seeded purchase return',
                        'date' => now()->toDateString(),
                        'updated_by' => $owner->name,
                    ]);

                    $linesCount = random_int(1, 5);

                    $subtotal = 0.0;
                    $taxTotal = 0.0;

                    for ($l = 1; $l <= $linesCount; $l++) {
                        $item = $items->random();

                        $qty = round((random_int(1, 100) / 10), 2);
                        $unit = round(random_int(50, 1500), 2);

                        $lineSub = round($qty * $unit, 2);

                        $taxRate = collect([0, 5, 10, 14])->random();
                        $taxValue = round($lineSub * ($taxRate / 100), 2);

                        $lineTotal = round($lineSub + $taxValue, 2);

                        PurchaseReturnItem::create([
                            'user_id' => $owner->id,
                            'purchase_return_id' => $ret->id,
                            'item_id' => $item->id,

                            // link to invoice item if column exists
                            'purchase_invoice_item_id' => null,

                            'warehouse_name_snapshot' => 'المخزن الرئيسي',

                            'quantity' => $qty,
                            'unit_price' => $unit,

                            'tax_rate' => $taxRate ?: null,
                            'tax_value' => $taxValue,

                            'line_subtotal' => $lineSub,
                            'line_total' => $lineTotal,

                            'notes' => null,

                            'date' => now()->toDateString(),
                            'updated_by' => $owner->name,
                        ]);

                        $subtotal += $lineSub;
                        $taxTotal += $taxValue;
                    }

                    $subtotal = round($subtotal, 2);
                    $taxTotal = round($taxTotal, 2);
                    $total = round($subtotal + $taxTotal, 2);

                    if ($status === 'cancelled') {
                        $subtotal = 0;
                        $taxTotal = 0;
                        $total = 0;
                    }

                    $ret->update([
                        'subtotal' => $subtotal,
                        'tax_value' => $taxTotal,
                        'total' => $total,
                    ]);
                }
            }
        });
    }

    // =========================
    // Helpers
    // =========================

    private function findAccount(int $ownerId, string $accountNumber): ?Account
    {
        return Account::where('user_id', $ownerId)
            ->where('account_number', $accountNumber)
            ->first();
    }

    /**
     * Supplier Sub-Account under 2100:
     * 2100 + 6 digits supplier_id => 2100000001
     */
    private function upsertSupplierAccount(User $owner, Account $apControl, Supplier $supplier): Account
    {
        $accNo = '2100'.str_pad((string) $supplier->id, 6, '0', STR_PAD_LEFT);

        return Account::updateOrCreate(
            [
                'user_id' => $owner->id,
                'account_number' => $accNo,
            ],
            [
                'account_type_id' => $apControl->account_type_id,
                'parent_account_id' => $apControl->id,
                'name' => 'مورد: '.($supplier->name ?? ('Supplier #'.$supplier->id)),
                'start_balance' => 0,
                'status' => true,
                'notes' => 'Auto-created supplier sub-ledger under A/P control (2100)',
                'updated_by' => $owner->name,
            ]
        );
    }

    private function normalizeStatus(string $status, float $total, float $paid): string
    {
        if (in_array($status, ['draft', 'cancelled'], true)) {
            return $status;
        }
        if ($total <= 0) {
            return 'posted';
        }
        if ($paid <= 0) {
            return 'posted';
        }
        if ($paid >= $total) {
            return 'paid';
        }

        return 'partial';
    }

    /**
     * Create 1-2 payment rows, optionally linking to cash/bank account
     * if purchase_payments table has account_id column.
     */
    private function seedPaymentsForInvoice(
        int $ownerId,
        int $invoiceId,
        float $paid,
        string $invoiceDate,
        string $updatedBy,
        ?int $cashAccountId,
        ?int $bankAccountId
    ): void {
        // split payments sometimes
        $parts = random_int(0, 1) ? 2 : 1;

        $remaining = $paid;

        for ($p = 1; $p <= $parts; $p++) {
            $amount = ($p === $parts) ? $remaining : round($paid * (random_int(30, 70) / 100), 2);
            $remaining = round($remaining - $amount, 2);
            if ($amount <= 0) {
                continue;
            }

            $payload = [
                'user_id' => $ownerId,
                'purchase_invoice_id' => $invoiceId,
                'payment_date' => $invoiceDate,
                'amount' => $amount,
                'reference' => 'PAY-'.Str::upper(Str::random(8)),
                'notes' => 'Seeded payment',
                'date' => now()->toDateString(),
                'updated_by' => $updatedBy,
            ];

            // link payment to account (cash/bank) if column exists
            if (Schema::hasColumn('purchase_payments', 'account_id')) {
                $payload['account_id'] = (random_int(0, 1) && $bankAccountId) ? $bankAccountId : ($cashAccountId ?? null);
            }

            DB::table('purchase_payments')->insert(array_merge($payload, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
