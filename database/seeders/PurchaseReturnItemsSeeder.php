<?php

namespace Database\Seeders;

use App\Models\Items;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseReturnItemsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $owners = User::whereNull('owner_user_id')->orderBy('id')->get();
            if ($owners->isEmpty()) {
                $this->command?->warn('No owner users found.');

                return;
            }

            foreach ($owners as $owner) {

                $returns = PurchaseReturn::query()
                    ->where('user_id', $owner->id)
                    ->orderBy('id')
                    ->get();

                if ($returns->isEmpty()) {
                    $this->command?->info("Owner {$owner->id}: No purchase returns found. Skipping return items seeding.");

                    continue;
                }

                foreach ($returns as $ret) {
                    PurchaseReturnItem::query()
                        ->where('user_id', $owner->id)
                        ->where('purchase_return_id', $ret->id)
                        ->delete();

                    $invoice = null;
                    if (! empty($ret->purchase_invoice_id)) {
                        $invoice = PurchaseInvoice::query()
                            ->where('user_id', $owner->id)
                            ->find($ret->purchase_invoice_id);
                    }

                    $sourceInvoiceItems = collect();

                    if ($invoice) {
                        $sourceInvoiceItems = PurchaseInvoiceItem::query()
                            ->where('user_id', $owner->id)
                            ->where('purchase_invoice_id', $invoice->id)
                            ->inRandomOrder()
                            ->limit(rand(1, 4))
                            ->get();
                    }

                    if ($sourceInvoiceItems->isEmpty()) {

                        $fallbackItems = Items::query()
                            ->where('user_id', $owner->id)
                            ->inRandomOrder()
                            ->limit(rand(1, 4))
                            ->get();

                        foreach ($fallbackItems as $it) {
                            $qty = round(mt_rand(1, 500) / 10, 2); // 0.1 .. 50
                            $unitPrice = round(mt_rand(100, 50000) / 100, 2); // 1 .. 500
                            $taxRate = [null, 0, 5, 14, 15][array_rand([0, 1, 2, 3, 4])];

                            $subtotal = round($qty * $unitPrice, 2);
                            $taxValue = $taxRate === null ? 0 : round($subtotal * ((float) $taxRate / 100), 2);
                            $total = round($subtotal + $taxValue, 2);

                            PurchaseReturnItem::create([
                                'user_id' => $owner->id,
                                'purchase_return_id' => $ret->id,
                                'item_id' => $it->id,
                                'purchase_invoice_item_id' => null,
                                'warehouse_name_snapshot' => $ret->warehouse_name_snapshot ?? null,
                                'transaction_id' => $ret->transaction_id ?? ('PR-'.Str::upper(Str::random(10))),
                                'quantity' => $qty,
                                'unit_price' => $unitPrice,
                                'tax_rate' => $taxRate,
                                'tax_value' => $taxValue,
                                'line_subtotal' => $subtotal,
                                'line_total' => $total,
                                'notes' => null,
                                'date' => $ret->date ?? now()->toDateString(),
                                'updated_by' => $ret->updated_by ?? 'Seeder',
                            ]);
                        }

                        continue;
                    }

                    foreach ($sourceInvoiceItems as $invItem) {

                        $maxQty = (float) ($invItem->quantity ?? 0);
                        if ($maxQty <= 0) {
                            continue;
                        }

                        $qty = round($maxQty * (mt_rand(10, 80) / 100), 2);
                        if ($qty <= 0) {
                            continue;
                        }

                        $unitPrice = (float) ($invItem->unit_price ?? 0);
                        $taxRate = $invItem->tax_rate; // ممكن null
                        $subtotal = round($qty * $unitPrice, 2);
                        $taxValue = $taxRate === null ? 0 : round($subtotal * ((float) $taxRate / 100), 2);
                        $total = round($subtotal + $taxValue, 2);

                        PurchaseReturnItem::create([
                            'user_id' => $owner->id,
                            'purchase_return_id' => $ret->id,
                            'item_id' => $invItem->item_id,
                            'purchase_invoice_item_id' => $invItem->id,
                            'warehouse_name_snapshot' => $ret->warehouse_name_snapshot ?? null,
                            'transaction_id' => $ret->transaction_id ?? ($invoice->transaction_id ?? ('PR-'.Str::upper(Str::random(10)))),
                            'quantity' => $qty,
                            'unit_price' => $unitPrice,
                            'tax_rate' => $taxRate,
                            'tax_value' => $taxValue,
                            'line_subtotal' => $subtotal,
                            'line_total' => $total,
                            'notes' => null,
                            'date' => $ret->date ?? now()->toDateString(),
                            'updated_by' => $ret->updated_by ?? 'Seeder',
                        ]);
                    }
                }

                $this->command?->info("Owner {$owner->id}: PurchaseReturnItems seeded.");
            }
        });
    }
}
