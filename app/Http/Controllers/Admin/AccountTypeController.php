<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UpdateAccountTypeRequest;
use App\Models\AccountType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountTypeController extends AdminBaseController
{
    private function authorizeOwner(AccountType $accountType): void
    {
        abort_if((int) $accountType->user_id !== $this->ownerId(), 403);
    }

    public function index(Request $request)
    {
        $ownerId = $this->ownerId();

        $q = AccountType::query()
            ->where('user_id', $ownerId)
            ->latest();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $q->where('status', (int) $request->status);
        }

        $items = $q->paginate(15)->withQueryString();

        return view('admin.account_types.index', compact('items'));
    }

    public function create()
    {
        return view('admin.account_types.create');
    }

    public function store(Request $request)
    {
        $ownerId = $this->ownerId();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('account_types')
                    ->where(fn ($q) => $q->where('user_id', $ownerId)),
            ],
            'code' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'boolean'],
            'date' => ['nullable', 'date'],
            'allow_posting' => ['nullable', 'boolean'],
        ]);

        AccountType::create([
            'user_id' => $ownerId,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'status' => $request->boolean('status'),
            'date' => $validated['date'] ?? null,
            'allow_posting' => $request->boolean('allow_posting'), // ✅ unchecked = false
            'updated_by' => auth()->user()->name ?? (string) auth()->id(),
        ]);

        return redirect()
            ->route('account_types.index')
            ->with('success', 'تم إضافة نوع الحساب بنجاح');
    }

    public function show(AccountType $accountType)
    {
        $this->authorizeOwner($accountType);

        return view('admin.account_types.show', compact('accountType'));
    }

    public function edit(AccountType $accountType)
    {
        $this->authorizeOwner($accountType);

        return view('admin.account_types.edit', compact('accountType'));
    }

    public function update(UpdateAccountTypeRequest $request, AccountType $accountType)
    {
        $this->authorizeOwner($accountType);

        $accountType->update([
            'name' => $request->name,
            'code' => $request->code,
            'date' => $request->date,
            'status' => $request->boolean('status'),
            'allow_posting' => $request->boolean('allow_posting'),
            'updated_by' => auth()->user()->name ?? (string) auth()->id(),
        ]);

        return redirect()
            ->route('account_types.index')
            ->with('success', 'تم تعديل نوع الحساب بنجاح');
    }

    public function destroy(AccountType $accountType)
    {
        $this->authorizeOwner($accountType);

        $accountType->delete();

        return redirect()
            ->route('account_types.index')
            ->with('success', 'تم حذف نوع الحساب بنجاح');
    }

    public function toggleStatus($id)
    {
        $ownerId = $this->ownerId();

        $accountType = AccountType::query()
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        $accountType->status = ! $accountType->status;
        $accountType->updated_by = auth()->user()->name ?? (string) auth()->id();
        $accountType->save();

        return response()->json([
            'ok' => true,
            'status' => (int) $accountType->status,
            'status_text' => $accountType->status ? 'نشط' : 'غير نشط',
        ]);
    }

    public function toggleAllowPosting($id)
    {
        $ownerId = $this->ownerId();

        $item = AccountType::query()
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        $item->allow_posting = ! $item->allow_posting;
        $item->updated_by = auth()->user()->name ?? (string) auth()->id();
        $item->save();

        return response()->json([
            'status' => true,
            'allow_posting_value' => (int) $item->allow_posting,
            'allow_posting_text' => $item->allow_posting ? 'نعم' : 'لا',
            'message' => 'تم تحديث السماح بالحركة بنجاح',
        ]);
    }
}
