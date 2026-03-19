<?php

namespace App\Jobs;

use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Services\Accounting\PostPurchaseInvoiceToLedger;
use App\Services\Accounting\PostSalesInvoiceToLedger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PostInvoiceToLedgerJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $invoiceType,
        public readonly int $invoiceId,
        public readonly int $tenantId,
        public readonly int $actorUserId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if ($this->invoiceType === 'sales') {
                $invoice = SalesInvoice::with(['items.item', 'customer', 'payments'])
                    ->where('user_id', $this->tenantId)
                    ->findOrFail($this->invoiceId);

                app(PostSalesInvoiceToLedger::class)->handle(
                    tenantId: $this->tenantId,
                    invoice: $invoice,
                    actorUserId: $this->actorUserId
                );
            } elseif ($this->invoiceType === 'purchase') {
                $invoice = PurchaseInvoice::with(['items.item', 'supplier'])
                    ->where('user_id', $this->tenantId)
                    ->findOrFail($this->invoiceId);

                app(PostPurchaseInvoiceToLedger::class)->handle(
                    tenantId: $this->tenantId,
                    invoice: $invoice,
                    actorUserId: $this->actorUserId
                );
            }
        } catch (\Throwable $e) {
            Log::error("PostInvoiceToLedgerJob failed: {$e->getMessage()}", [
                'invoiceType' => $this->invoiceType,
                'invoiceId' => $this->invoiceId,
                'tenantId' => $this->tenantId,
            ]);
            throw $e;
        }
    }
}
