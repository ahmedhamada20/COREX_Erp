<?php

namespace App\Http\Controllers\Admin;

use App\Models\Treasuries;
use App\Models\TreasuriesDelivery;
use App\Models\UserShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserShiftsController extends AdminBaseController
{
    private function actorId(): int
    {

        return (int) auth()->id();
    }

    public function index()
    {
        $ownerId = $this->ownerId();

        $shifts = UserShift::where('user_id', $ownerId)->with(['actor', 'treasury'])->latest('opened_at')->paginate(30);

        return view('admin.shifts.index', compact('shifts'));
    }

    public function create(Request $request)
    {
        $ownerId = $this->ownerId();

        $treasuries = Treasuries::query()
            ->where('user_id', $ownerId)
            ->where('status', 1)
            ->orderByDesc('is_master')
            ->orderBy('name')
            ->get();

        $selectedTreasury = $request->get('treasury_id');

        return view('admin.shifts.create', compact('treasuries', 'selectedTreasury'));
    }

    public function store(Request $request)
    {
        $ownerId = $this->ownerId();
        $actorId = $this->actorId();

        $data = $request->validate([
            'treasury_id' => ['required', 'integer', 'exists:treasuries,id'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
        ]);

        $treasuryId = (int) $data['treasury_id'];

        // تأكد الخزنة تبع نفس التينانت
        $treasuryOk = Treasuries::query()
            ->where('user_id', $ownerId)
            ->where('status', 1)
            ->where('id', $treasuryId)
            ->exists();

        abort_unless($treasuryOk, 403);

        return DB::transaction(function () use ($ownerId, $actorId, $treasuryId, $data) {

            // ممنوع المستخدم يفتح شفتين
            $hasOpenForActor = UserShift::query()
                ->where('user_id', $ownerId)
                ->where('actor_user_id', $actorId)
                ->where('status', 'open')
                ->exists();

            if ($hasOpenForActor) {
                return back()->with('error', 'عندك شفت مفتوح بالفعل. اقفله أولاً.');
            }

            // ممنوع خزنة عليها شفت مفتوح
            $hasOpenOnTreasury = UserShift::query()
                ->where('user_id', $ownerId)
                ->where('treasury_id', $treasuryId)
                ->where('status', 'open')
                ->exists();

            if ($hasOpenOnTreasury) {
                return back()->with('error', 'الخزنة عليها شفت مفتوح لمستخدم آخر.');
            }

            UserShift::create([
                'user_id' => $ownerId,
                'actor_user_id' => $actorId,
                'treasury_id' => $treasuryId,

                'opened_at' => now(),
                'opening_balance' => (float) ($data['opening_balance'] ?? 0),

                'closing_expected' => 0,
                'closing_actual' => null,
                'difference' => 0,

                'status' => 'open',
                'closed_by' => null,
            ]);

            return redirect()->route('shifts.index')->with('success', 'تم فتح الشفت بنجاح');
        });
    }

    public function show(UserShift $shift)
    {

        $ownerId = $this->ownerId();
        abort_if((int) $shift->user_id !== $ownerId, 403);

        $shift->load(['actor', 'treasury']);

        // Totals from deliveries in this shift
        $deliveries = TreasuriesDelivery::query()

            ->where('shift_id', $shift->id);

        $collections = (float) (clone $deliveries)->where('type', 'collection')->sum('amount');
        $payments = (float) (clone $deliveries)->where('type', 'payment')->sum('amount');

        $transferIn = (float) (clone $deliveries)
            ->where('type', 'transfer')
            ->where('to_treasury_id', $shift->treasury_id)
            ->sum('amount');

        $transferOut = (float) (clone $deliveries)
            ->where('type', 'transfer')
            ->where('from_treasury_id', $shift->treasury_id)
            ->sum('amount');

        $expected = (float) $shift->opening_balance
            + $collections + $transferIn
            - $payments - $transferOut;

        // current balance within shift (same as expected)
        $shiftBalance = $expected;

        $shift->load([
            'deliveries.fromTreasury',
            'deliveries.toTreasury',
            'deliveries.actor',
        ]);

        return view('admin.shifts.show', compact(
            'shift',
            'collections',
            'payments',
            'transferIn',
            'transferOut',
            'expected',
            'shiftBalance'
        ));
    }

    public function closeForm()
    {
        $ownerId = $this->ownerId();
        $actorId = $this->actorId();

        $shift = UserShift::where('user_id', $ownerId)
            ->where('actor_user_id', $actorId)
            ->where('status', 'open')
            ->with(['actor', 'treasury'])
            ->latest('opened_at')
            ->first();

        if (! $shift) {
            return back()->with('error', 'لا يوجد شفت مفتوح.');
        }

        // هنا هنعرض صفحة فيها expected (لما تبقى تربط deliveries بالـ shift)
        return view('admin.shifts.close', compact('shift'));
    }

    public function close(Request $request)
    {
        $ownerId = $this->ownerId();
        $actorId = $this->actorId();

        $shift = UserShift::query()
            ->where('user_id', $ownerId)
            ->where('actor_user_id', $actorId)
            ->where('status', 'open')
            ->latest('opened_at')
            ->lockForUpdate()
            ->first();

        if (! $shift) {
            return back()->with('error', 'لا يوجد شفت مفتوح.');
        }

        $data = $request->validate([
            'closing_actual' => ['required', 'numeric', 'min:0'],
        ]);

        // ✅ حساب closing_expected من deliveries المرتبطة بالشفت
        // collection = to_treasury, payment = from_treasury, transfer حسب from/to
        $collections = $shift->deliveries()->where('type', 'collection')->sum('amount');
        $payments = $shift->deliveries()->where('type', 'payment')->sum('amount');

        // transfer: لو الخزنة هي from يبقى out، لو هي to يبقى in
        $transferIn = $shift->deliveries()
            ->where('type', 'transfer')
            ->where('to_treasury_id', $shift->treasury_id)
            ->sum('amount');

        $transferOut = $shift->deliveries()
            ->where('type', 'transfer')
            ->where('from_treasury_id', $shift->treasury_id)
            ->sum('amount');

        $expected = (float) $shift->opening_balance
            + (float) $collections
            + (float) $transferIn
            - (float) $payments
            - (float) $transferOut;

        $actual = (float) $data['closing_actual'];
        $diff = $actual - $expected;

        $shift->update([
            'closing_expected' => $expected,
            'closing_actual' => $actual,
            'difference' => $diff,
            'closed_at' => now(),
            'status' => 'closed',
            'closed_by' => auth()->user()->name ?? (string) auth()->id(),
        ]);

        return redirect()->route('shifts.index')->with('success', 'تم قفل الشفت بنجاح');
    }
}
