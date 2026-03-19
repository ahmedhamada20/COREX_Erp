<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UpdateSettingRequest;
use App\Models\Settings;
use App\Services\Tenants\SettingsCacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminController extends AdminBaseController
{
    public function index()
    {
        return view('admin.index');
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
