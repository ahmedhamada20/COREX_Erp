@extends('admin.layouts.master')

@section('title', 'تفاصيل حركة خزنة')

@section('css')
    <style>
        .num { font-variant-numeric: tabular-nums; }
        .pill { border-radius: 999px; padding: 4px 10px; font-size: 12px; }
    </style>
@endsection

@section('content')

    <div class="content-header d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">تفاصيل حركة - خزنة: {{ $treasury->name }}</h5>
            <small class="text-muted">#{{ $delivery->id }} — سند: <span class="num">{{ $delivery->receipt_no ?? '—' }}</span></small>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('treasuries.deliveries.edit', [$treasury->id, $delivery->id]) }}" class="btn btn-sm btn-primary">
                <i class="ti ti-pencil"></i>
                تعديل
            </a>
            <a href="{{ route('treasuries.deliveries.index', $treasury->id) }}" class="btn btn-sm btn-light">
                <i class="ti ti-arrow-right"></i>
                رجوع
            </a>
        </div>
    </div>

    @include('admin.Alerts')

    @php
        $typeLabel = $delivery->type === 'collection'
            ? '<span class="badge bg-success pill">تحصيل</span>'
            : ($delivery->type === 'payment'
                ? '<span class="badge bg-danger pill">صرف</span>'
                : '<span class="badge bg-info pill">تحويل</span>');
    @endphp

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="fw-semibold">بيانات الحركة</div>
            <span class="badge bg-info">ERP</span>
        </div>

        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-3">
                    <div class="text-muted small">النوع</div>
                    <div class="mt-1">{!! $typeLabel !!}</div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">التاريخ</div>
                    <div class="fw-semibold num mt-1">{{ optional($delivery->doc_date)->format('Y-m-d') ?? '—' }}</div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">المبلغ</div>
                    <div class="fw-semibold num mt-1">{{ number_format((float)$delivery->amount, 2) }}</div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">رقم السند</div>
                    <div class="fw-semibold num mt-1">{{ $delivery->receipt_no ?? '—' }}</div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted small">من خزنة</div>
                    <div class="fw-semibold mt-1">{{ $delivery->fromTreasury?->name ?? '—' }}</div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted small">إلى خزنة</div>
                    <div class="fw-semibold mt-1">{{ $delivery->toTreasury?->name ?? '—' }}</div>
                </div>

                @if(in_array($delivery->type, ['collection','payment']) && $delivery->counterpartyAccount)
                    <div class="col-12">
                        <div class="text-muted small">حساب الطرف المقابل</div>
                        <div class="fw-semibold mt-1">{{ $delivery->counterpartyAccount->name }}</div>
                    </div>
                @endif

                @if($delivery->journal_entry_id)
                    <div class="col-12">
                        <div class="text-muted small">القيد المحاسبي</div>
                        <div class="fw-semibold mt-1">
                            #{{ $delivery->journal_entry_id }}
                            {{-- لو عندك Route للقيد:
                            <a class="ms-2" href="{{ route('journal_entries.show', $delivery->journal_entry_id) }}">عرض</a>
                            --}}
                        </div>
                    </div>
                @endif

                <div class="col-md-6">
                    <div class="text-muted small">السبب</div>
                    <div class="mt-1">{{ $delivery->reason ?: '—' }}</div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted small">ملاحظات</div>
                    <div class="mt-1">{{ $delivery->notes ?: '—' }}</div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted small">المستخدم</div>
                    <div class="mt-1">{{ $delivery->actor?->name ?? '—' }}</div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted small">الشفت</div>
                    <div class="mt-1">{{ $delivery->shift_id ?? '—' }}</div>
                </div>

            </div>
        </div>
    </div>

@endsection
