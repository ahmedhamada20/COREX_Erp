@extends('admin.layouts.master')

@section('title', 'تفاصيل الشفت')

@section('css')
    <style>
        .kpi-card { border: 1px solid rgba(0,0,0,.06); border-radius: 12px; }
        .kpi-title { font-size: 12px; color: #6c757d; }
        .kpi-value { font-size: 20px; font-weight: 800; }
        .muted { color: #6c757d; }
        .num { font-variant-numeric: tabular-nums; }
        .pill { border-radius: 999px; padding: 4px 10px; font-size: 12px; }
        .table td, .table th { vertical-align: middle; }
    </style>
@endsection

@section('content')

    <div class="content-header d-flex align-items-center justify-content-between">
        <div>
            <h5 class="mb-0">
                تفاصيل الشفت
                <span class="text-muted fw-normal ms-2">— {{ auth()->user()->name }}</span>
            </h5>
            <div class="small text-muted mt-1">
                <span class="me-2">#{{ $shift->id }}</span>
                <span class="mx-1">•</span>
                <span class="me-2">الخزنة: <span class="fw-semibold text-dark">{{ $shift->treasury?->name ?? '-' }}</span></span>
                <span class="mx-1">•</span>
                <span>المستخدم: <span class="fw-semibold text-dark">{{ $shift->actor?->name ?? '-' }}</span></span>
            </div>
        </div>

        <div class="d-flex gap-2">
            @if($shift->status === 'open')
                <a href="{{ route('shifts.close.form') }}"
                   class="btn btn-sm btn-warning">
                    <i class="ti ti-lock"></i>
                    قفل الشفت
                </a>
            @endif

            <a href="{{ route('shifts.index') }}" class="btn btn-sm btn-light">
                <i class="ti ti-arrow-right"></i>
                رجوع
            </a>
        </div>
    </div>

    @include('admin.Alerts')

    @php
        $isOpen = $shift->status === 'open';
        $diff   = (float) ($shift->difference ?? 0);

        $diffClass = $diff == 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-primary');
        $statusBadge = $isOpen
            ? '<span class="badge bg-warning text-dark pill">مفتوح</span>'
            : '<span class="badge bg-success pill">مغلق</span>';

        $openedAt = optional($shift->opened_at)->timezone('Africa/Cairo');
        $closedAt = optional($shift->closed_at)->timezone('Africa/Cairo');
    @endphp

    {{-- ====== Summary Header ====== --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-center">

                <div class="col-md-3">
                    <div class="p-3 kpi-card bg-light">
                        <div class="kpi-title">حالة الشفت</div>
                        <div class="mt-2">{!! $statusBadge !!}</div>
                        <div class="small muted mt-2">
                            فتح: <span class="fw-semibold text-dark">
                                {{ $openedAt ? $openedAt->format('Y-m-d h:i A') : '-' }}
                            </span>
                            <br>
                            إقفال: <span class="fw-semibold text-dark">
                                {{ $shift->closed_at ? $closedAt->format('Y-m-d h:i A') : '—' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="p-3 kpi-card">
                        <div class="kpi-title">Opening Balance</div>
                        <div class="kpi-value num">{{ number_format((float)$shift->opening_balance, 2) }}</div>
                        <div class="small muted">المبلغ اللي اتحط في بداية الشفت</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="p-3 kpi-card">
                        <div class="kpi-title">رصيد الشفت الحالي (داخل الشفت)</div>
                        <div class="kpi-value num">{{ number_format((float)($shiftBalance ?? 0), 2) }}</div>
                        <div class="small muted">Opening + صافي الحركات</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="p-3 kpi-card border border-primary">
                        <div class="kpi-title text-primary fw-semibold">
                            الرصيد المفترض تسليمه
                        </div>

                        <div class="kpi-value num text-primary">
                            {{ number_format((float)($expected ?? $shift->closing_expected), 2) }}
                        </div>

                        <div class="small muted">
                            هذا هو المبلغ الذي يجب أن يكون فعليًا في الخزنة عند الإقفال
                        </div>
                    </div>
                </div>


            </div>

            <hr class="my-4">

            {{-- ====== Movement Summary ====== --}}
            <div class="row g-3">

                <div class="col-md-3">
                    <div class="p-3 kpi-card bg-light">
                        <div class="kpi-title">إجمالي التحصيل (Collections)</div>
                        <div class="kpi-value num">{{ number_format((float)($collections ?? 0), 2) }}</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="p-3 kpi-card bg-light">
                        <div class="kpi-title">إجمالي الصرف (Payments)</div>
                        <div class="kpi-value num">{{ number_format((float)($payments ?? 0), 2) }}</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="p-3 kpi-card bg-light">
                        <div class="kpi-title">تحويلات داخل (Transfer In)</div>
                        <div class="kpi-value num">{{ number_format((float)($transferIn ?? 0), 2) }}</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="p-3 kpi-card bg-light">
                        <div class="kpi-title">تحويلات خارج (Transfer Out)</div>
                        <div class="kpi-value num">{{ number_format((float)($transferOut ?? 0), 2) }}</div>
                    </div>
                </div>

            </div>

            {{-- ===== Close Results ===== --}}
            @if(!$isOpen)
                <hr class="my-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 kpi-card">
                            <div class="kpi-title">Actual @ Close</div>
                            <div class="kpi-value num">
                                {{ $shift->closing_actual !== null ? number_format((float)$shift->closing_actual, 2) : '—' }}
                            </div>
                            <div class="small muted">المبلغ اللي اتقفل بيه الشفت</div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 kpi-card">
                            <div class="kpi-title">Difference</div>
                            <div class="kpi-value num {{ $diffClass }}">{{ number_format($diff, 2) }}</div>
                            <div class="small muted">Actual - Expected</div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 kpi-card bg-light">
                            <div class="kpi-title">Closed By</div>
                            <div class="fw-semibold mt-1">
                                {{ $shift->closed_by ?: '—' }}
                            </div>
                            <div class="small muted">توقيع الإقفال</div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- ===== Deliveries Table ===== --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="fw-semibold">
                حركات الشفت
                <span class="text-muted fw-normal">({{ $shift->deliveries->count() }})</span>
            </div>

            <div class="small text-muted">
                كل الحركات هنا مربوطة بـ shift_id = {{ $shift->id }}
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 text-end align-middle">
                    <thead>
                    <tr>
                        <th style="width:70px">#</th>
                        <th style="width:120px">التاريخ</th>
                        <th style="width:120px">النوع</th>
                        <th>من</th>
                        <th>إلى</th>
                        <th style="width:140px">المبلغ</th>
                        <th style="width:120px">رقم السند</th>
                        <th style="width:220px">الطرف المقابل</th>
                        <th style="width:120px">قيد</th>
                        <th style="width:220px">السبب/ملاحظات</th>
                    </tr>
                    </thead>
                    <tbody>

                    @forelse($shift->deliveries as $delivery)
                        @php
                            $typeLabel = $delivery->type === 'collection'
                                ? '<span class="badge bg-success pill">تحصيل</span>'
                                : ($delivery->type === 'payment'
                                    ? '<span class="badge bg-danger pill">صرف</span>'
                                    : '<span class="badge bg-info pill">تحويل</span>');
                        @endphp

                        <tr>
                            <td class="text-muted">{{ $loop->iteration }}</td>

                            <td class="num">
                                {{ optional($delivery->doc_date)->format('Y-m-d') ?? '—' }}
                            </td>

                            <td>{!! $typeLabel !!}</td>

                            <td>
                                <div class="fw-semibold">{{ $delivery->fromTreasury?->name ?? '—' }}</div>
                                @if($delivery->from_treasury_id)
                                    <div class="small text-muted">#{{ $delivery->from_treasury_id }}</div>
                                @endif
                            </td>

                            <td>
                                <div class="fw-semibold">{{ $delivery->toTreasury?->name ?? '—' }}</div>
                                @if($delivery->to_treasury_id)
                                    <div class="small text-muted">#{{ $delivery->to_treasury_id }}</div>
                                @endif
                            </td>

                            <td class="fw-bold num">
                                {{ number_format((float)$delivery->amount, 2) }}
                            </td>

                            <td class="num">
                                {{ $delivery->receipt_no ?? '—' }}
                            </td>

                            <td>
                                {{ $delivery->counterpartyAccount?->name ?? '—' }}
                            </td>

                            <td class="num">
                                {{ $delivery->journal_entry_id ?? '—' }}
                            </td>

                            <td>
                                <div class="small">
                                    <div class="fw-semibold">{{ $delivery->reason ?? '—' }}</div>
                                    @if($delivery->notes)
                                        <div class="text-muted">{{ \Illuminate\Support\Str::limit($delivery->notes, 80) }}</div>
                                    @endif
                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                لا توجد حركات مسجلة لهذا الشفت.
                            </td>
                        </tr>
                    @endforelse

                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
