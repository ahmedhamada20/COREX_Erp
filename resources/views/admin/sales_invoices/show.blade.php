{{-- resources/views/admin/sales_invoices/show.blade.php --}}
@extends('admin.layouts.master')

@section('title', 'عرض فاتورة مبيعات')

@section('css')
    <style>
        :root{
            --c-blue:#0b5ed7;
            --c-dark:#0f172a;
            --c-muted:#64748b;
            --c-bg:#f6f8fb;
            --c-card:#ffffff;
            --c-border:#e2e8f0;
            --c-soft:#f8fafc;
            --c-danger:#dc3545;
            --c-success:#16a34a;
            --c-warning:#f59e0b;
            --c-info:#0ea5e9;
        }

        .page-wrap{ background:var(--c-bg); border-radius:18px; padding:14px; }
        .topbar{
            background: linear-gradient(135deg, rgba(11,94,215,.95), rgba(11,94,215,.72));
            color:#fff; border-radius:18px; padding:14px 16px;
        }
        .topbar .muted{ color: rgba(255,255,255,.86); }

        .cardx{ background:var(--c-card); border:1px solid var(--c-border); border-radius:18px; overflow:hidden; }
        .cardx-hd{
            padding:12px 14px; border-bottom:1px solid var(--c-border);
            display:flex; align-items:center; justify-content:space-between; gap:10px;
            background: linear-gradient(180deg, #fff, #fbfdff);
        }
        .cardx-bd{ padding:12px 14px; }

        .btnx{ border-radius:14px; padding:10px 12px; font-weight:950; }
        .btn-icon{
            width:40px; height:40px;
            display:inline-flex; align-items:center; justify-content:center;
            border-radius:14px;
        }

        .badge-soft{
            border-radius:999px; padding:6px 10px; font-weight:950; font-size:12px; white-space:nowrap;
            border:1px solid rgba(148,163,184,.45);
            background: rgba(148,163,184,.12);
            color: var(--c-dark);
        }
        .badge-soft.primary{ border-color: rgba(11,94,215,.22); background: rgba(11,94,215,.12); color: var(--c-blue); }
        .badge-soft.success{ border-color: rgba(22,163,74,.22); background: rgba(22,163,74,.12); color: var(--c-success); }
        .badge-soft.warn{ border-color: rgba(245,158,11,.22); background: rgba(245,158,11,.14); color:#a16207; }
        .badge-soft.danger{ border-color: rgba(220,53,69,.22); background: rgba(220,53,69,.10); color:#b02a37; }
        .badge-soft.info{ border-color: rgba(14,165,233,.22); background: rgba(14,165,233,.12); color:#0369a1; }

        .hint{ font-size:12px; color:var(--c-muted); }

        .money{ direction:ltr; unicode-bidi:bidi-override; display:inline-block; font-variant-numeric: tabular-nums; }
        .kpi{
            border:1px solid var(--c-border);
            border-radius:18px;
            padding:12px;
            background:#fff;
            height:100%;
        }
        .kpi .label{ font-size:12px; color:var(--c-muted); }
        .kpi .value{ font-size:20px; font-weight:950; color:var(--c-dark); }
        .kpi .sub{ font-size:12px; color:var(--c-muted); margin-top:6px; }

        .meta-row{ display:flex; flex-wrap:wrap; gap:10px; }
        .meta-chip{
            border:1px solid var(--c-border);
            background:#fff;
            border-radius:14px;
            padding:10px 12px;
            min-width: 210px;
            flex: 1 1 210px;
        }
        .meta-chip .t{ font-size:12px; color:var(--c-muted); }
        .meta-chip .v{ font-weight:950; color:var(--c-dark); margin-top:4px; }
        .meta-chip .v small{ font-weight:800; color:var(--c-muted); }

        .table thead th{
            font-size:12px;
            color:var(--c-muted);
            font-weight:950;
            border-bottom:1px solid var(--c-border) !important;
        }
        .table tbody td{
            vertical-align:middle;
            border-top:1px solid var(--c-border) !important;
        }
        .table-wrap{
            border:1px solid var(--c-border);
            border-radius:18px;
            overflow:hidden;
            background:#fff;
        }

        .rowline{
            display:flex; align-items:center; justify-content:space-between; gap:10px;
            padding:10px 12px;
            border-top:1px solid var(--c-border);
        }
        .rowline:first-child{ border-top:0; }
        .rowline .l{ color:var(--c-muted); font-size:12px; font-weight:900; }
        .rowline .r{ font-weight:950; color:var(--c-dark); }

        .divider{ height:1px; background:var(--c-border); margin:10px 0; }

        .sticky{ position: sticky; top: 12px; }
        @media (max-width: 992px){ .sticky{ position: static; } }
    </style>
@endsection

@section('content')
    @php
        $fmt  = fn($n, $d=2) => number_format((float)($n ?? 0), $d);
        $date = fn($v) => $v ? \Carbon\Carbon::parse($v)->format('Y-m-d') : '-';

        $status = $invoice->status ?? 'draft';
        $statusBadge = match($status){
            'draft'     => ['primary', 'Draft'],
            'posted'    => ['info', 'Posted'],
            'paid'      => ['success', 'Paid'],
            'partial'   => ['warn', 'Partial'],
            'cancelled' => ['danger', 'Cancelled'],
            default     => ['primary', $status],
        };

        $payType  = $invoice->payment_type ?? 'cash';
        $payBadge = $payType === 'cash' ? ['success','Cash'] : ['warn','Credit'];

        $customer   = $invoice->customer;
        $hasReturns = !empty($invoice->returns) && $invoice->returns->count() > 0;
        $payments   = $invoice->payments ?? collect();

        // ✅ Journal Entry relation (make sure loaded)
        $je    = $invoice->journalEntry ?? null;
        $lines = $je?->lines ?? collect();

        $jeStatus = $je?->status ?? null;
        $jeBadge = $jeStatus ? match($jeStatus){
            'posted'   => ['success','Posted'],
            'draft'    => ['warn','Draft'],
            'reversed' => ['danger','Reversed'],
            default    => ['primary',$jeStatus],
        } : null;
    @endphp

    {{-- Topbar --}}
    <div class="topbar mb-3">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h4 class="mb-0">عرض فاتورة مبيعات</h4>
                    <span class="badge-soft {{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span>
                    <span class="badge-soft {{ $payBadge[0] }}">{{ $payBadge[1] }}</span>
                    <span class="badge-soft">#{{ $invoice->invoice_code ?? $invoice->invoice_number }}</span>
                </div>
                <div class="muted small mt-1">
                    تاريخ: <b>{{ $date($invoice->invoice_date) }}</b>
                    @if($invoice->due_date)
                        — استحقاق: <b>{{ $date($invoice->due_date) }}</b>
                    @endif
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('sales_invoices.index') }}" class="btn btn-light btnx">
                    <i class="ti ti-arrow-right"></i> رجوع
                </a>

                @if(Route::has('sales_invoices.pdf'))
                    <a href="{{ route('sales_invoices.pdf', $invoice->id) }}" target="_blank" class="btn btn-outline-dark btnx">
                        <i class="ti ti-file-type-pdf"></i> PDF
                    </a>
                @endif

                {{-- ✅ Create Sales Return --}}
                @if(Route::has('sales_returns.create_from_invoice'))
                    @php
                        $canReturnStatus = in_array($status, ['posted','paid','partial'], true);

                        // مجموع المرتجعات الفعلية (استبعد الملغي لو total=0)
                        $returnedTotal = (float) ($invoice->returns?->sum('total') ?? 0);

                        $invoiceTotal = (float) ($invoice->total ?? 0);

                        $returnableAmount = max(0, $invoiceTotal - $returnedTotal);

                        $canReturnAmount = $returnableAmount > 0.0001;

                        $canReturn = $canReturnStatus && $canReturnAmount;
                    @endphp

                    @if($canReturn)
                        <a href="{{ route('sales_returns.create_from_invoice', $invoice->id) }}"
                           class="btn btn-warning btnx text-dark">
                            <i class="ti ti-rotate-2"></i>
                            عمل مرتجع مبيعات
                            <small class="ms-2">
                                (المتاح:
                                <span class="money">{{ number_format($returnableAmount, 2) }}</span>)
                            </small>
                        </a>
                    @elseif($canReturnStatus && !$canReturnAmount)
                        <button class="btn btn-secondary btnx" disabled
                                title="تم عمل مرتجع كامل لهذه الفاتورة">
                            <i class="ti ti-rotate-2"></i>
                            تم عمل مرتجع كامل
                        </button>
                    @endif
                @endif

                @if($status !== 'cancelled')
                    {{-- ترحيل --}}
                    @if(Route::has('sales_invoices.post') && empty($invoice->journal_entry_id))
                        <form method="POST" action="{{ route('sales_invoices.post', $invoice->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btnx"
                                    onclick="return confirm('ترحيل الفاتورة وإنشاء قيد يومية؟');">
                                <i class="ti ti-check"></i> ترحيل (Post)
                            </button>
                        </form>
                    @endif

                    {{-- إلغاء --}}
                    @if(Route::has('sales_invoices.cancel'))
                        <form method="POST" action="{{ route('sales_invoices.cancel', $invoice->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btnx"
                                    onclick="return confirm('إلغاء الفاتورة؟ لو مترحّلة هيتم عمل قيد عكسي.');">
                                <i class="ti ti-ban"></i> إلغاء
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="page-wrap">
        <div class="row g-3">

            {{-- LEFT: Details --}}
            <div class="col-12 col-lg-8">

                {{-- Invoice Meta --}}
                <div class="cardx mb-3">
                    <div class="cardx-hd">
                        <div class="fw-bold">بيانات الفاتورة</div>
                        <div class="d-flex gap-2 flex-wrap">
                            @if(!empty($invoice->journal_entry_id))
                                <span class="badge-soft success">JE ID: #{{ $invoice->journal_entry_id }}</span>
                            @else
                                <span class="badge-soft warn">No Journal Entry</span>
                            @endif
                            @if($invoice->posted_at)
                                <span class="badge-soft info">Posted: {{ \Carbon\Carbon::parse($invoice->posted_at)->format('Y-m-d H:i') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="cardx-bd">
                        <div class="meta-row">
                            <div class="meta-chip">
                                <div class="t">رقم الفاتورة</div>
                                <div class="v">{{ $invoice->invoice_number }}</div>
                            </div>
                            <div class="meta-chip">
                                <div class="t">كود الفاتورة</div>
                                <div class="v">{{ $invoice->invoice_code ?? '-' }}</div>
                            </div>
                            <div class="meta-chip">
                                <div class="t">الحالة</div>
                                <div class="v"><span class="badge-soft {{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span></div>
                            </div>
                            <div class="meta-chip">
                                <div class="t">نوع الدفع</div>
                                <div class="v"><span class="badge-soft {{ $payBadge[0] }}">{{ $payBadge[1] }}</span></div>
                            </div>
                        </div>

                        <div class="divider"></div>

                        <div class="meta-row">
                            <div class="meta-chip">
                                <div class="t">العميل</div>
                                <div class="v">
                                    {{ $customer?->name ?? '-' }}
                                    @if($customer?->code)
                                        <small>— #{{ $customer->code }}</small>
                                    @endif
                                </div>
                                <div class="hint mt-1">
                                    {{ $customer?->phone ? ('📞 '.$customer->phone) : '' }}
                                </div>
                            </div>

                            <div class="meta-chip">
                                <div class="t">تاريخ الفاتورة</div>
                                <div class="v">{{ $date($invoice->invoice_date) }}</div>
                            </div>

                            <div class="meta-chip">
                                <div class="t">الاستحقاق</div>
                                <div class="v">{{ $date($invoice->due_date) }}</div>
                                <div class="hint mt-1">للآجل فقط غالبًا.</div>
                            </div>

                            <div class="meta-chip">
                                <div class="t">آخر تحديث بواسطة</div>
                                <div class="v">{{ $invoice->updated_by ?? '-' }}</div>
                                <div class="hint mt-1">Posted By: {{ $invoice->posted_by ?? '-' }}</div>
                            </div>
                        </div>

                        @if($invoice->notes)
                            <div class="divider"></div>
                            <div class="meta-chip" style="min-width:100%;">
                                <div class="t">ملاحظات</div>
                                <div class="v" style="font-weight:800;">{{ $invoice->notes }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ✅ Journal Entry (Posting) - FULL CARD --}}
                {{-- Journal Entry (Inline) --}}
                {{-- All Journal Entries --}}
                <div class="cardx mb-3">
                    <div class="cardx-hd">
                        <div class="fw-bold">القيود المرتبطة بالفاتورة</div>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge-soft">Count: {{ ($journalEntries ?? collect())->count() }}</span>
                        </div>
                    </div>

                    <div class="cardx-bd">
                        @if(($journalEntries ?? collect())->count())
                            @foreach($journalEntries as $row)
                                @php
                                    $je = $row['je'];
                                    $lines = $je->lines ?? collect();

                                    $jeStatus = $je->status ?? 'posted';
                                    $jeBadge = match($jeStatus){
                                        'posted'   => ['success','Posted'],
                                        'draft'    => ['warn','Draft'],
                                        'reversed' => ['danger','Reversed'],
                                        default    => ['primary',$jeStatus],
                                    };
                                @endphp

                                <div class="cardx mb-3" style="border-radius:16px;">
                                    <div class="cardx-hd">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="badge-soft info">{{ $row['label'] }}</span>
                                            <span class="badge-soft primary">{{ $je->entry_number ?? ('#'.$je->id) }}</span>
                                            <span class="badge-soft">{{ $date($je->entry_date) }}</span>
                                            <span class="badge-soft {{ $jeBadge[0] }}">{{ $jeBadge[1] }}</span>
                                            <span class="badge-soft">{{ $je->source ?? '-' }}</span>

                                            @if(($row['type'] ?? '') === 'receipt' && !empty($row['payment']))
                                                <span class="badge-soft">
                                    خزنة: {{ $row['payment']->treasury?->name ?? ('#'.$row['payment']->treasury_id) }}
                                </span>
                                                <span class="badge-soft success">
                                    Amount: <span class="money">{{ $fmt($row['payment']->amount, 2) }}</span>
                                </span>
                                                <span class="badge-soft">
                                    Method: {{ $row['payment']->method }}
                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="cardx-bd">
                                        <div class="meta-row mb-2">
                                            <div class="meta-chip">
                                                <div class="t">الوصف</div>
                                                <div class="v" style="font-weight:800;">{{ $je->description ?? '-' }}</div>
                                            </div>
                                            <div class="meta-chip">
                                                <div class="t">إجمالي مدين</div>
                                                <div class="v"><span class="money">{{ $fmt($je->total_debit, 2) }}</span></div>
                                            </div>
                                            <div class="meta-chip">
                                                <div class="t">إجمالي دائن</div>
                                                <div class="v"><span class="money">{{ $fmt($je->total_credit, 2) }}</span></div>
                                            </div>
                                        </div>

                                        <div class="table-wrap">
                                            <div class="table-responsive">
                                                <table class="table mb-0">
                                                    <thead>
                                                    <tr>
                                                        <th style="width:44%;">الحساب</th>
                                                        <th style="width:10%;">#</th>
                                                        <th class="text-end" style="width:16%;">مدين</th>
                                                        <th class="text-end" style="width:16%;">دائن</th>
                                                        <th style="width:14%;">Memo</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @forelse($lines as $l)
                                                        <tr>
                                                            <td>
                                                                <div class="fw-bold" style="color:var(--c-dark);">
                                                                    {{ $l->account?->name ?? ('#'.$l->account_id) }}
                                                                </div>
                                                                <div class="hint">
                                                                    {{ $l->account?->account_number ? ('#'.$l->account->account_number) : '' }}
                                                                    @if($l->currency_code)
                                                                        <span class="mx-1">—</span> {{ $l->currency_code }}
                                                                    @endif
                                                                    @if($l->branch_id)
                                                                        <span class="mx-1">—</span> Branch: {{ $l->branch_id }}
                                                                    @endif
                                                                </div>
                                                            </td>
                                                            <td class="hint">#{{ $l->line_no }}</td>
                                                            <td class="text-end"><span class="money fw-bold">{{ $fmt($l->debit, 2) }}</span></td>
                                                            <td class="text-end"><span class="money fw-bold">{{ $fmt($l->credit, 2) }}</span></td>
                                                            <td class="hint">{{ $l->memo ?? '-' }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-center p-4">
                                                                <div class="hint">لا توجد سطور للقيد.</div>
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="alert alert-light mb-0" style="border-radius:14px; border:1px solid var(--c-border);">
                                <div class="fw-bold">لا توجد قيود مرتبطة</div>
                                <div class="hint">قيد الفاتورة يظهر بعد الترحيل (Post)، وقيود التحصيل تظهر بعد تسجيل الدفعات في sales_payments.</div>
                            </div>
                        @endif
                    </div>
                </div>



                {{-- Items --}}
                <div class="cardx mb-3">
                    <div class="cardx-hd">
                        <div class="fw-bold">الأصناف</div>
                        <div class="hint">عدد السطور: <b>{{ $invoice->items?->count() ?? 0 }}</b></div>
                    </div>
                    <div class="cardx-bd">
                        <div class="table-wrap">
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead>
                                    <tr>
                                        <th style="width:38%;">الصنف</th>
                                        <th style="width:14%;">الكود/باركود</th>
                                        <th class="text-center" style="width:10%;">Qty</th>
                                        <th class="text-end" style="width:12%;">Price</th>
                                        <th class="text-end" style="width:10%;">Disc</th>
                                        <th class="text-end" style="width:10%;">VAT</th>
                                        <th class="text-end" style="width:16%;">Total</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($invoice->items as $line)
                                        @php
                                            $it = $line->item;
                                            $code = $it?->items_code ?? $it?->code ?? null;
                                            $barcode = $it?->barcode ?? null;
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-bold" style="color:var(--c-dark);">
                                                    {{ $it?->name ?? ('#'.$line->item_id) }}
                                                </div>
                                                <div class="hint">
                                                    {{ $it?->type ? ('نوع: '.$it->type) : '' }}
                                                </div>
                                            </td>
                                            <td class="hint">
                                                @if($code) #{{ $code }} @endif
                                                @if($code && $barcode) <span class="mx-1">—</span> @endif
                                                @if($barcode) {{ $barcode }} @endif
                                                @if(!$code && !$barcode) - @endif
                                            </td>
                                            <td class="text-center"><span class="money">{{ $fmt($line->quantity, 4) }}</span></td>
                                            <td class="text-end"><span class="money">{{ $fmt($line->price, 4) }}</span></td>
                                            <td class="text-end"><span class="money text-danger">{{ $fmt($line->discount, 4) }}</span></td>
                                            <td class="text-end"><span class="money text-primary">{{ $fmt($line->vat, 4) }}</span></td>
                                            <td class="text-end"><span class="money fw-bold">{{ $fmt($line->total, 4) }}</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center p-4">
                                                <div class="hint">لا توجد أصناف داخل الفاتورة.</div>
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="hint mt-2">
                            * إجمالي السطر عندك في الجدول = (بعد الخصم + VAT) — مطابق لطريقة recalcInvoice.
                        </div>
                    </div>
                </div>

                {{-- Payments --}}
                <div class="cardx mb-3">
                    <div class="cardx-hd">
                        <div class="fw-bold">المدفوعات</div>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge-soft">Count: {{ $payments->count() }}</span>
                            <span class="badge-soft success">Sum: <span class="money">{{ $fmt($payments->sum('amount'), 2) }}</span></span>
                        </div>
                    </div>
                    <div class="cardx-bd">
                        @if($payments->count())
                            <div class="table-wrap">
                                <div class="table-responsive">
                                    <table class="table mb-0">
                                        <thead>
                                        <tr>
                                            <th>التاريخ</th>
                                            <th>الخزنة</th>
                                            <th class="text-end">المبلغ</th>
                                            <th>مرجع</th>
                                            <th class="text-center">JE</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($payments as $p)
                                            <tr>
                                                <td>{{ $date($p->payment_date) }}</td>
                                                <td>{{ $p->treasury?->name ?? ('#'.$p->treasury_id) }}</td>
                                                <td class="text-end"><span class="money fw-bold">{{ $fmt($p->amount, 2) }}</span></td>
                                                <td class="hint">{{ $p->reference ?? '-' }}</td>
                                                <td class="text-center">
                                                    @if($p->journal_entry_id)
                                                        <span class="badge-soft success">#{{ $p->journal_entry_id }}</span>
                                                    @else
                                                        <span class="badge-soft">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-light mb-0" style="border-radius:14px; border:1px solid var(--c-border);">
                                <div class="fw-bold">لا توجد مدفوعات مسجلة</div>
                                <div class="hint">لو الفاتورة Cash وبتسجل Split Payment، المفروض يتخزن هنا (sales_payments) علشان يبقى مصدر الحقيقة للحسابات.</div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Returns --}}
                <div class="cardx">
                    <div class="cardx-hd">
                        <div class="fw-bold">المرتجعات</div>
                        <div class="hint">{{ $hasReturns ? 'يوجد مرتجعات' : 'لا يوجد' }}</div>
                    </div>
                    <div class="cardx-bd">
                        @if($hasReturns)
                            <div class="table-wrap">
                                <div class="table-responsive">
                                    <table class="table mb-0">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>تاريخ المرتجع</th>
                                            <th class="text-end">الإجمالي</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($invoice->returns as $r)
                                            <tr>
                                                <td>#{{ $r->id }}</td>
                                                <td>{{ $date($r->return_date) }}</td>
                                                <td class="text-end"><span class="money fw-bold">{{ $fmt($r->total, 2) }}</span></td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="hint">—</div>
                        @endif
                    </div>
                </div>

            </div>

            {{-- RIGHT: Totals / Status --}}
            <div class="col-12 col-lg-4">
                <div class="sticky">

                    <div class="cardx mb-3">
                        <div class="cardx-hd">
                            <div class="fw-bold">الإجماليات</div>
                            <span class="badge-soft primary">Auto</span>
                        </div>
                        <div class="cardx-bd">
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="kpi">
                                        <div class="label">Subtotal</div>
                                        <div class="value"><span class="money">{{ $fmt($invoice->subtotal, 2) }}</span></div>
                                        <div class="sub">بعد خصم الفاتورة (discount_amount) وقبل VAT.</div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="kpi">
                                        <div class="label">Discount</div>
                                        <div class="value text-danger"><span class="money">{{ $fmt($invoice->discount_amount, 2) }}</span></div>
                                        <div class="sub">خصم على مستوى الفاتورة.</div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="kpi">
                                        <div class="label">VAT</div>
                                        <div class="value text-primary"><span class="money">{{ $fmt($invoice->vat_amount, 2) }}</span></div>
                                        <div class="sub">مجموع VAT من السطور.</div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="kpi">
                                        <div class="label">Total</div>
                                        <div class="value"><span class="money">{{ $fmt($invoice->total, 2) }}</span></div>
                                        <div class="sub">Total = subtotal + VAT.</div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="kpi">
                                        <div class="label">Paid</div>
                                        <div class="value text-success"><span class="money">{{ $fmt($invoice->paid_amount, 2) }}</span></div>
                                        <div class="sub">مدفوع (من payments إن وجدت).</div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="kpi">
                                        <div class="label">Remaining</div>
                                        <div class="value text-danger"><span class="money">{{ $fmt($invoice->remaining_amount, 2) }}</span></div>
                                        <div class="sub">متبقي.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="divider"></div>

                            <div class="rowline">
                                <div class="l">الحالة الحالية</div>
                                <div class="r"><span class="badge-soft {{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span></div>
                            </div>

                            <div class="rowline">
                                <div class="l">نوع الدفع</div>
                                <div class="r"><span class="badge-soft {{ $payBadge[0] }}">{{ $payBadge[1] }}</span></div>
                            </div>



                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
