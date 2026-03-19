<?php

namespace App\Http\Controllers\Admin;

use App\Models\Customer;
use App\Models\Items;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Http\Requests\UpdateSettingRequest;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\Settings;
use App\Models\Supplier;
use App\Services\Tenants\SettingsCacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminController extends AdminBaseController
{
    public function index()
    {
        $ownerId = $this->ownerId();

        $salesInvoicesQuery = SalesInvoice::query()
            ->where('user_id', $ownerId)
            ->where('status', '!=', 'cancelled');

        $purchaseInvoicesQuery = PurchaseInvoice::query()
            ->where('user_id', $ownerId)
            ->where('status', '!=', 'cancelled');

        $salesTotal = (float) (clone $salesInvoicesQuery)->sum('total');
        $salesPaidTotal = (float) (clone $salesInvoicesQuery)->sum('paid_amount');
        $salesDueTotal = (float) (clone $salesInvoicesQuery)->sum('remaining_amount');
        $salesReturnsTotal = (float) SalesReturn::query()
            ->where('user_id', $ownerId)
            ->sum('total');

        $purchaseTotal = (float) (clone $purchaseInvoicesQuery)->sum('total');
        $purchasePaidTotal = (float) (clone $purchaseInvoicesQuery)->sum('paid_amount');
        $purchaseDueTotal = (float) (clone $purchaseInvoicesQuery)->sum('remaining_amount');
        $purchaseReturnsTotal = (float) PurchaseReturn::query()
            ->where('user_id', $ownerId)
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        $dashboardReport = [
            'counts' => [
                'customers' => Customer::query()->where('user_id', $ownerId)->count(),
                'suppliers' => Supplier::query()->where('user_id', $ownerId)->count(),
                'items' => Items::query()->where('user_id', $ownerId)->count(),
                'sales_invoices' => (clone $salesInvoicesQuery)->count(),
                'purchase_invoices' => (clone $purchaseInvoicesQuery)->count(),
            ],
            'finance' => [
                'sales_total' => $salesTotal,
                'sales_paid_total' => $salesPaidTotal,
                'sales_due_total' => $salesDueTotal,
                'sales_returns_total' => $salesReturnsTotal,
                'net_sales' => $salesTotal - $salesReturnsTotal,
                'purchase_total' => $purchaseTotal,
                'purchase_paid_total' => $purchasePaidTotal,
                'purchase_due_total' => $purchaseDueTotal,
                'purchase_returns_total' => $purchaseReturnsTotal,
                'net_purchases' => $purchaseTotal - $purchaseReturnsTotal,
            ],
        ];

        return view('admin.index', compact('dashboardReport'));
    }

    public function setting()
    {
        $ownerId = $this->ownerId();

        $data = Settings::firstOrNew(['user_id' => $ownerId]);

        return view('admin.setting.index', compact('data'));
    }

    public function post_setting(UpdateSettingRequest $request)
    {
        $ownerId = $this->ownerId();
        $userName = auth()->user()->name ?? (string) auth()->id();

        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $validated['status'] = $request->boolean('status');

            $settings = Settings::where('user_id', $ownerId)->first();

            // ✅ Logo
            if ($request->hasFile('logo')) {
                if ($settings?->logo && Storage::disk('public')->exists($settings->logo)) {
                    Storage::disk('public')->delete($settings->logo);
                }

                $validated['logo'] = $request->file('logo')->store('settings/logo', 'public');
            }

            // ✅ Favicon
            if ($request->hasFile('favicon')) {
                if ($settings?->favicon && Storage::disk('public')->exists($settings->favicon)) {
                    Storage::disk('public')->delete($settings->favicon);
                }

                $validated['favicon'] = $request->file('favicon')->store('settings/favicon', 'public');
            }

            Settings::updateOrCreate(
                ['user_id' => $ownerId],
                $validated + ['updated_by' => $userName]
            );

            // ✅ Bust the settings cache so next request gets fresh data
            app(SettingsCacheService::class)->forget($ownerId);

            DB::commit();

            return back()->with('success', 'تم حفظ الإعدادات بنجاح');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Settings update failed', [
                'tenant_user_id' => $ownerId,
                'actor_user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'حدث خطأ غير متوقع أثناء حفظ الإعدادات، برجاء المحاولة مرة أخرى');
        }
    }
}
