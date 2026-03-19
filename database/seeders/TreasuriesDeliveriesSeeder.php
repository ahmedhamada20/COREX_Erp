<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Treasuries;
use App\Models\TreasuriesDelivery;
use App\Models\User;
use App\Services\Accounting\TreasuryPostingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TreasuriesDeliveriesSeeder extends Seeder
{
    public function run(): void
    {
        $owners = User::query()
            ->whereNull('owner_user_id')
            ->select('id', 'name')
            ->get();

        if ($owners->isEmpty()) {
            $this->command?->warn('No owners found. Seed users first.');

            return;
        }

        foreach ($owners as $owner) {

            $treasuries = Treasuries::query()
                ->where('user_id', $owner->id)
                ->where('status', 1)
                ->select('id', 'is_master', 'account_id', 'name')
                ->get();

            if ($treasuries->isEmpty()) {
                continue;
            }

            // ✅ لازم كل خزنة يكون لها account_id علشان posting يسمع
            $missingAcc = $treasuries->firstWhere('account_id', null);
            if ($missingAcc) {
                $this->command?->warn("Owner #{$owner->id}: treasury {$missingAcc->name} has no account_id. Run TreasurySeeder first.");

                continue;
            }

            $treasuryIds = $treasuries->pluck('id')->values();

            // ✅ رصيد في الذاكرة لمنع السالب
            $balances = [];
            foreach ($treasuryIds as $tid) {
                $balances[(int) $tid] = 0.0;
            }

            // ✅ service
            $posting = app(TreasuryPostingService::class);

            // ✅ تحضير طرف مقابل (علشان collection/payment لازم counterparty_account_id)
            $counterpartyAccountIds = $this->seedCounterpartyAccounts($owner->id);

            // ✅ رصيد افتتاحي للخزنة الرئيسية (كـ collection) + JE
            $masterId = (int) optional($treasuries->firstWhere('is_master', true))->id;
            if ($masterId) {
                $openingAmount = 50000.0;

                $delivery = $this->createDeliveryWithReceipt(
                    ownerId: (int) $owner->id,
                    actorId: (int) $owner->id,
                    treasuryIdForReceipt: $masterId,
                    type: 'collection',
                    fromId: null,
                    toId: $masterId,
                    amount: $openingAmount,
                    docDate: now()->toDateString(),
                    counterpartyAccountId: Arr::random($counterpartyAccountIds),
                    reason: 'رصيد افتتاحي (Seeder)',
                    updatedBy: $owner->name ?: 'system'
                );

                $posting->postDelivery($delivery);
                $balances[$masterId] += $openingAmount;
            }

            // ✅ 20 حركة
            for ($i = 0; $i < 20; $i++) {

                $allowedTypes = $treasuryIds->count() > 1
                    ? ['collection', 'payment', 'transfer']
                    : ['collection', 'payment'];

                $type = Arr::random($allowedTypes);
                $amount = (float) random_int(100, 10000);

                $from = null;
                $to = null;

                $treasuriesWithMoney = collect($balances)
                    ->filter(fn ($b) => $b > 0.0)
                    ->keys()
                    ->map(fn ($k) => (int) $k)
                    ->values();

                // ✅ لو هنعمل صرف/تحويل ومفيش رصيد في أي خزنة => اجبار قبض
                if (in_array($type, ['payment', 'transfer'], true) && $treasuriesWithMoney->isEmpty()) {
                    $type = 'collection';
                }

                $counterpartyAccId = null;

                if ($type === 'collection') {
                    $to = (int) $treasuryIds->random();
                    $balances[$to] += $amount;
                    $counterpartyAccId = Arr::random($counterpartyAccountIds);

                } elseif ($type === 'payment') {

                    $from = (int) $treasuriesWithMoney->random();
                    $amount = min($amount, $balances[$from]);

                    if ($amount <= 0) {
                        // fallback to collection
                        $type = 'collection';
                        $to = (int) $treasuryIds->random();
                        $amount = (float) random_int(100, 10000);
                        $balances[$to] += $amount;
                        $counterpartyAccId = Arr::random($counterpartyAccountIds);
                        $from = null;
                    } else {
                        $balances[$from] -= $amount;
                        $counterpartyAccId = Arr::random($counterpartyAccountIds);
                    }

                } else { // transfer

                    $from = (int) $treasuriesWithMoney->random();
                    do {
                        $to = (int) $treasuryIds->random();
                    } while ($to === $from);

                    $amount = min($amount, $balances[$from]);

                    if ($amount <= 0) {
                        // fallback to collection
                        $type = 'collection';
                        $to = (int) $treasuryIds->random();
                        $amount = (float) random_int(100, 10000);
                        $balances[$to] += $amount;
                        $counterpartyAccId = Arr::random($counterpartyAccountIds);
                        $from = null;
                    } else {
                        $balances[$from] -= $amount;
                        $balances[$to] += $amount;
                    }
                }

                $createdAt = now()->subDays(random_int(0, 30));
                $docDate = $createdAt->toDateString();

                $delivery = $this->createDeliveryWithReceipt(
                    ownerId: (int) $owner->id,
                    actorId: (int) $owner->id,
                    treasuryIdForReceipt: (int) ($type === 'collection' ? $to : $from), // receipt per خزنة المصدر/الاستلام
                    type: $type,
                    fromId: $from,
                    toId: $to,
                    amount: $amount,
                    docDate: $docDate,
                    counterpartyAccountId: $counterpartyAccId,
                    reason: fake()->sentence(),
                    updatedBy: $owner->name ?: 'system',
                    createdAt: $createdAt
                );

                // ✅ عمل JE + account_balances
                $posting->postDelivery($delivery);
            }
        }
    }

    /**
     * ✅ يعمل مجموعة حسابات “مسموح بها” كطرف مقابل (عميل/مورد/حساب عام)
     * أبسط شيء: نجيب أي حسابات فعّالة غير خزائن.
     */
    private function seedCounterpartyAccounts(int $ownerId): array
    {
        $treasuryAccountIds = Treasuries::query()
            ->where('user_id', $ownerId)
            ->whereNotNull('account_id')
            ->pluck('account_id')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        $accounts = Account::query()
            ->where('user_id', $ownerId)
            ->where('status', 1)
            ->whereNotIn('id', $treasuryAccountIds) // منع استخدام حساب خزنة كطرف مقابل
            ->limit(50)
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        if (empty($accounts)) {
            throw new \RuntimeException("Owner #{$ownerId}: no counterparty accounts found.");
        }

        return $accounts;
    }

    /**
     * ✅ ينشئ Delivery ويولّد receipt_no بطريقة صحيحة لكل خزنة ولكل نوع
     * - collection: يستخدم last_collection_receipt_no على خزنة الاستلام (to)
     * - payment:    يستخدم last_payment_receipt_no على خزنة الصرف (from)
     * - transfer:   غالبًا يعتبر “سند تحويل” — هنعمل receipt على خزنة الإرسال كـ payment counter
     */
    private function createDeliveryWithReceipt(
        int $ownerId,
        int $actorId,
        int $treasuryIdForReceipt,
        string $type,
        ?int $fromId,
        ?int $toId,
        float $amount,
        string $docDate,
        ?int $counterpartyAccountId,
        ?string $reason,
        ?string $updatedBy,
        ?\Illuminate\Support\Carbon $createdAt = null
    ): TreasuriesDelivery {

        return DB::transaction(function () use (
            $ownerId, $actorId, $treasuryIdForReceipt, $type, $fromId, $toId, $amount,
            $docDate, $counterpartyAccountId, $reason, $updatedBy, $createdAt
        ) {

            $t = Treasuries::query()
                ->where('user_id', $ownerId)
                ->where('id', $treasuryIdForReceipt)
                ->lockForUpdate()
                ->firstOrFail();

            $receiptNo = null;

            if ($type === 'collection') {
                $receiptNo = (int) $t->last_collection_receipt_no + 1;
                $t->update(['last_collection_receipt_no' => $receiptNo]);

            } elseif ($type === 'payment') {
                $receiptNo = (int) $t->last_payment_receipt_no + 1;
                $t->update(['last_payment_receipt_no' => $receiptNo]);

            } else { // transfer
                // اعتبره سند تحويل صادر من خزنة الإرسال
                $receiptNo = (int) $t->last_payment_receipt_no + 1;
                $t->update(['last_payment_receipt_no' => $receiptNo]);
            }

            $payload = [
                'user_id' => $ownerId,
                'actor_user_id' => $actorId,
                'shift_id' => null,

                'type' => $type,
                'from_treasury_id' => $fromId,
                'to_treasury_id' => $toId,

                'counterparty_account_id' => $counterpartyAccountId,

                'amount' => $amount,
                'receipt_no' => $receiptNo,
                'doc_date' => $docDate,

                'reason' => $reason,
                'notes' => fake()->boolean(30) ? fake()->paragraph() : null,
                'updated_by' => $updatedBy ?: 'system',
            ];

            if ($createdAt) {
                $payload['created_at'] = $createdAt;
                $payload['updated_at'] = $createdAt;
            }

            return TreasuriesDelivery::create($payload);
        });
    }
}
