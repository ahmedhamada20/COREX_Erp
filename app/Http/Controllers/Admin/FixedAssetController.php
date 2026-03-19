<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\RunFixedAssetDepreciationRequest;
use App\Http\Requests\StoreFixedAssetRequest;
use App\Models\Account;
use App\Models\FixedAsset;
use App\Services\Accounting\PostFixedAssetDepreciation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FixedAssetController extends AdminBaseController
{
    public function index(): View
    {
        $ownerId = $this->ownerId();

        $assets = FixedAsset::query()
            ->where('user_id', $ownerId)
            ->with(['parent:id,name', 'depreciations'])
            ->orderBy('asset_code')
            ->paginate(20);

        return view('admin.fixed_assets.index', compact('assets'));
    }

    public function create(): View
    {
        $ownerId = $this->ownerId();
        $accounts = Account::query()->where('user_id', $ownerId)->orderBy('account_number')->get();
        $parents = FixedAsset::query()->where('user_id', $ownerId)->where('is_group', true)->orderBy('asset_code')->get();

        return view('admin.fixed_assets.create', compact('accounts', 'parents'));
    }

    public function store(StoreFixedAssetRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $this->ownerId();
        $data['status'] = (bool) ($data['status'] ?? true);
        $data['is_group'] = (bool) ($data['is_group'] ?? false);
        $data['updated_by'] = auth()->user()->name ?? 'system';

        $asset = FixedAsset::create($data);

        return redirect()->route('fixed_assets.show', $asset)->with('success', 'تم إضافة الأصل الثابت.');
    }

    public function show(FixedAsset $fixedAsset): View
    {
        abort_if((int) $fixedAsset->user_id !== $this->ownerId(), 403);

        $fixedAsset->load(['parent:id,name', 'children:id,parent_id,asset_code,name', 'depreciations.journalEntry:id,entry_number,entry_date']);

        return view('admin.fixed_assets.show', ['asset' => $fixedAsset]);
    }

    public function edit(FixedAsset $fixedAsset): View
    {
        abort_if((int) $fixedAsset->user_id !== $this->ownerId(), 403);

        return $this->show($fixedAsset);
    }

    public function update(Request $request, FixedAsset $fixedAsset): RedirectResponse
    {
        abort_if((int) $fixedAsset->user_id !== $this->ownerId(), 403);

        $data = $request->validate([
            'status' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $fixedAsset->update([
            'status' => array_key_exists('status', $data) ? (bool) $data['status'] : $fixedAsset->status,
            'notes' => $data['notes'] ?? $fixedAsset->notes,
            'updated_by' => auth()->user()->name ?? 'system',
        ]);

        return back()->with('success', 'تم تحديث الأصل الثابت.');
    }

    public function destroy(FixedAsset $fixedAsset): RedirectResponse
    {
        abort_if((int) $fixedAsset->user_id !== $this->ownerId(), 403);

        $fixedAsset->delete();

        return redirect()->route('fixed_assets.index')->with('success', 'تم حذف الأصل الثابت.');
    }

    public function runDepreciation(
        RunFixedAssetDepreciationRequest $request,
        PostFixedAssetDepreciation $service
    ): RedirectResponse {
        $result = $service->handle(
            tenantId: $this->ownerId(),
            periodFrom: $request->validated('period_from'),
            periodTo: $request->validated('period_to'),
            actorUserId: (int) auth()->id(),
        );

        return redirect()
            ->route('fixed_assets.index')
            ->with('success', "تم ترحيل {$result['posted_count']} أصل بإجمالي {$result['total_amount']}");
    }
}
