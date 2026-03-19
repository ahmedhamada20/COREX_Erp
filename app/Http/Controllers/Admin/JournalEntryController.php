<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StoreManualJournalEntryRequest;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\Accounting\PostManualJournalEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class JournalEntryController extends AdminBaseController
{
    public function index(): View
    {
        $entries = JournalEntry::query()
            ->where('user_id', $this->ownerId())
            ->where('source', 'manual')
            ->withCount('lines')
            ->latest('id')
            ->paginate(20);

        return view('admin.journal_entries.index', compact('entries'));
    }

    public function create(): View
    {
        $accounts = Account::query()
            ->where('user_id', $this->ownerId())
            ->where('status', true)
            ->orderBy('account_number')
            ->get();

        return view('admin.journal_entries.create', compact('accounts'));
    }

    public function store(StoreManualJournalEntryRequest $request, PostManualJournalEntry $service): RedirectResponse
    {
        $entry = $service->handle(
            tenantId: $this->ownerId(),
            actorUserId: (int) auth()->id(),
            data: $request->validated(),
        );

        return redirect()
            ->route('journal_entries.show', $entry)
            ->with('success', 'تم حفظ سند القيد اليدوي بنجاح');
    }

    public function show(JournalEntry $journalEntry): View
    {
        abort_if((int) $journalEntry->user_id !== $this->ownerId(), 403);

        $journalEntry->load(['lines.account']);

        return view('admin.journal_entries.show', ['entry' => $journalEntry]);
    }
}
