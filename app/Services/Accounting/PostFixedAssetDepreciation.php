<?php

namespace App\Services\Accounting;

use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PostFixedAssetDepreciation
{
    /**
     * @return array{posted_count:int,total_amount:float}
     */
    public function handle(int $tenantId, string $periodFrom, string $periodTo, int $actorUserId): array
    {
        return DB::transaction(function () use ($tenantId, $periodFrom, $periodTo, $actorUserId): array {
            $from = Carbon::parse($periodFrom)->startOfDay();
            $to = Carbon::parse($periodTo)->endOfDay();
            $periodKey = $from->format('Y-m');

            $assets = FixedAsset::query()
                ->where('user_id', $tenantId)
                ->where('is_group', false)
                ->where('status', true)
                ->whereNotNull('depreciation_start_date')
                ->whereDate('depreciation_start_date', '<=', $to->toDateString())
                ->get();

            $postedCount = 0;
            $totalAmount = 0.0;

            foreach ($assets as $asset) {
                $exists = FixedAssetDepreciation::query()
                    ->where('user_id', $tenantId)
                    ->where('fixed_asset_id', $asset->id)
                    ->where('period_key', $periodKey)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $monthly = round($asset->depreciationBase() / max(1, (int) $asset->useful_life_months), 2);
                if ($monthly <= 0) {
                    continue;
                }

                if (! $asset->depreciation_expense_account_id || ! $asset->accumulated_depreciation_account_id) {
                    continue;
                }

                $entry = app(LedgerWriter::class)->postEntry([
                    'user_id' => $tenantId,
                    'entry_number' => app(JournalEntryNumberGenerator::class)->next($tenantId, $from),
                    'entry_date' => $to->toDateString(),
                    'source' => 'fixed_asset_depreciation',
                    'reference_type' => FixedAsset::class,
                    'reference_id' => $asset->id,
                    'description' => 'Depreciation '.$asset->asset_code.' '.$periodKey,
                    'status' => 'posted',
                    'posted_at' => now(),
                    'posted_by' => $actorUserId,
                ], [
                    [
                        'account_id' => $asset->depreciation_expense_account_id,
                        'debit' => $monthly,
                        'credit' => 0,
                        'memo' => 'Monthly depreciation expense',
                    ],
                    [
                        'account_id' => $asset->accumulated_depreciation_account_id,
                        'debit' => 0,
                        'credit' => $monthly,
                        'memo' => 'Accumulated depreciation',
                    ],
                ]);

                FixedAssetDepreciation::create([
                    'user_id' => $tenantId,
                    'fixed_asset_id' => $asset->id,
                    'period_key' => $periodKey,
                    'period_from' => $from->toDateString(),
                    'period_to' => $to->toDateString(),
                    'amount' => $monthly,
                    'journal_entry_id' => $entry->id,
                    'status' => 'posted',
                    'updated_by' => auth()->user()->name ?? (string) $actorUserId,
                ]);

                $postedCount++;
                $totalAmount += $monthly;
            }

            return [
                'posted_count' => $postedCount,
                'total_amount' => round($totalAmount, 2),
            ];
        });
    }
}
