{{-- resources/views/admin/purchase_invoices/show.blade.php --}}
@extends('admin.layouts.master')

@section('title', 'عرض فاتورة مشتريات')

@section('css')
    <style>
        :root{
            --muted:#64748b;
            --card:#ffffff;
            --border:#e5e7eb;
            --soft:#f8fafc;
            --ink:#0f172a;
        }

        .page-title{font-weight:900; letter-spacing:.2px;}
        .muted{color:var(--muted);}
        .num{direction:ltr; text-align:left; font-variant-numeric:tabular-nums;}
        .card-soft{
            background:var(--card);
            border:1px solid var(--border);
            box-shadow:0 2px 12px rgba(0,0,0,.04);
            border-radius:14px;
        }

        .chip{
            border-radius:999px;
            padding:6px 10px;
            font-weight:800;
            font-size:12px;
            display:inline-flex;
            align-items:center;
            gap:6px;
            border:1px solid transparent;
            white-space:nowrap;
        }
        .chip-gray{background:#f1f5f9; border-color:#e2e8f0; color:#0f172a;}
        .chip-blue{background:#eef2ff; border-color:#c7d2fe; color:#3730a3;}
        .chip-green{background:#ecfdf5; border-color:#a7f3d0; color:#065f46;}
        .chip-yellow{background:#fffbeb; border-color:#fde68a; color:#92400e;}
        .chip-red{background:#fef2f2; border-color:#fecaca; color:#991b1b;}

        .kv{display:flex; gap:10px; align-items:flex-start;}
        .kv .k{width:160px; color:var(--muted); font-size:13px;}
        .kv .v{font-weight:900; color:var(--ink);}

        .kpi{
            border:1px solid var(--border);
            border-radius:14px;
            padding:14px;
            background:#fff;
            height:100%;
        }
        .kpi .label{font-size:12px; color:var(--muted);}
        .kpi .value{font-size:18px; font-weight:900; color:var(--ink);}
        .kpi .sub{font-size:12px; color:var(--muted); margin-top:4px;}

        .totals-box{
            background:var(--soft);
            border:1px dashed #cbd5e1;
            border-radius:14px;
            padding:14px;
        }
        .totals-box .row + .row{margin-top:8px;}
        .totals-box .label{color:var(--muted); font-size:13px;}
        .totals-box .value{font-weight:900;}

        .table thead th{background:#f8fafc; white-space:nowrap;}
        .table td,.table th{vertical-align:middle;}
        .line-total{font-weight:900;}
        .item-meta{font-size:12px; color:var(--muted);}

        .section-title{font-weight:900; color:var(--ink);}
        .divider{height:1px; background:var(--border); margin:14px 0;}

        /* Print */
        .no-print{}
        .print-area{background:#fff;}
        @media print{
            .no-print{display:none !important;}
            .content-header,.breadcrumb,.navbar,.sidebar,.footer{display:none !important;}
            .print-area{padding:0 !important; margin:0 !important;}
            .card{box-shadow:none !important;}
            .card-soft{border:0 !important; box-shadow:none !important;}
        }
    </style>
@endsection

@section('content')
    @php
        $status = $invoice->status ?? 'draft';
        $paymentType = $invoice->payment_type ?? 'cash';

        $statusChip = match ($status) {
            'draft'     => ['chip-gray',   'مسودة'],
            'posted'    => ['chip-blue',   'مُرحّلة'],
            'paid'      => ['chip-green',  'مدفوعة'],
            'partial'   => ['chip-yellow', 'جزئي'],
            'cancelled' => ['chip-red',    'ملغاة'],
            default     => ['chip-gray',   $status],
        };

        $payChip = $paymentType === 'cash'
            ? ['chip-green', 'كاش']
            : ['chip-yellow', 'آجل'];

        $invoiceDate = $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') : '-';
        $dueDate     = $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('Y-m-d') : '-';
        $postedAt    = $invoice->posted_at ? \Carbon\Carbon::parse($invoice->posted_at)->format('Y-m-d H:i') : '-';

        $currency = $invoice->currency_code ?? 'EGP';
        $fx = (float)($invoice->exchange_rate ?? 1);

        $invDiscType  = $invoice->discount_type ?? 'none';
        $invDiscRate  = (float)($invoice->discount_rate ?? 0);
        $invDiscValue = (float)($invoice->discount_value ?? 0);

        $invDiscDisplay = '-';
        if ($invDiscType === 'percent') $invDiscDisplay = number_format($invDiscRate, 2).'%';
        elseif ($invDiscType === 'fixed') $invDiscDisplay = number_format($invDiscValue, 2);

        $canReturn = in_array($status, ['posted','paid','partial'], true) && $status !== 'cancelled';

        $returnsCount = $invoice->returns?->count() ?? 0;
        $returnsTotal = (float)($invoice->returns?->where('status','!=','cancelled')->sum('total') ?? 0);

        // محاسبة
        $je = $invoice->journalEntry ?? null;
        $jeLines = $je?->lines ?? collect();
        $jeDebit  = (float) $jeLines->sum('debit');
        $jeCredit = (float) $jeLines->sum('credit');
        $jeBalanced = $je && abs($jeDebit - $jeCredit) < 0.01;

        // Totals
        $subBefore = (float)($invoice->subtotal_before_discount ?? 0);
        $linesDisc = (float)($invoice->items?->sum('discount_value') ?? 0);
        $subAfter  = (float)($invoice->subtotal ?? 0);
        $tax       = (float)($invoice->tax_value ?? 0);
        $ship      = (float)($invoice->shipping_cost ?? 0);
        $other     = (float)($invoice->other_charges ?? 0);
        $total     = (float)($invoice->total ?? 0);
        $paid      = (float)($invoice->paid_amount ?? 0);
        $remain    = (float)($invoice->remaining_amount ?? 0);
    @endphp

    <div class="content-header mb-3 no-print">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 page-title">عرض فاتورة مشتريات</h5>
                <small class="text-muted">
                    {{ $invoice->purchase_invoice_code ?? '-' }} — {{ $invoice->invoice_number }}
                </small>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('purchase_invoices.index') }}" class="btn btn-sm btn-light">رجوع</a>

                @if(Route::has('purchase_invoices.edit') && !in_array($status, ['cancelled'], true))
                    <a href="{{ route('purchase_invoices.edit', $invoice->id) }}" class="btn btn-sm btn-primary">تعديل</a>
                @endif

                @if(Route::has('purchase_returns.create_from_invoice'))
                    @if($canReturn)
                        <a href="{{ route('purchase_returns.create_from_invoice', $invoice->id) }}" class="btn btn-sm btn-warning">
                            <i class="fa fa-undo"></i> مرتجع
                        </a>
                    @else
                        <button class="btn btn-sm btn-warning" disabled title="متاح بعد الترحيل">
                            <i class="fa fa-undo"></i> مرتجع
                        </button>
                    @endif
                @endif

                <a href="{{ route('purchase_invoices.pdf', $invoice->id) }}" class="btn btn-sm btn-danger" target="_blank">
                    <i class="fa fa-print"></i> طباعة
                </a>
            </div>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="print-area">
        {{-- ✅ Top chips + KPIs --}}
        <div class="row g-3 mb-3">
            <div class="col-12">
                <div class="card card-soft">
                    <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <div class="d-flex flex-wrap gap-2">
                            <span class="chip {{ $statusChip[0] }}">
                                <i class="fa fa-circle" style="font-size:8px;"></i>
                                الحالة: {{ $statusChip[1] }}
                            </span>

                                <span class="chip {{ $payChip[0] }}">
                                <i class="fa fa-credit-card"></i>
                                نوع الدفع: {{ $payChip[1] }}
                            </span>

                                @if((bool)$invoice->tax_included)
                                    <span class="chip chip-blue">
                                    <i class="fa fa-percent"></i>
                                    الأسعار تشمل الضريبة
                                </span>
                                @endif

                                @if($returnsCount > 0)
                                    <span class="chip chip-yellow">
                                    <i class="fa fa-undo"></i>
                                    عليها مرتجعات: {{ $returnsCount }}
                                </span>
                                @endif

                                @if($je)
                                    <span class="chip {{ $jeBalanced ? 'chip-green' : 'chip-red' }}">
                                    <i class="fa fa-book"></i>
                                    قيد اليومية: {{ $jeBalanced ? 'متوازن' : 'غير متوازن' }}
                                </span>
                                @else
                                    <span class="chip chip-gray">
                                    <i class="fa fa-book"></i>
                                    لا يوجد قيد (غير مُرحّلة)
                                </span>
                                @endif
                            </div>

                            <div class="mt-2 muted">
                                تاريخ الفاتورة: <strong class="num">{{ $invoiceDate }}</strong>
                                @if($paymentType === 'credit')
                                    — الاستحقاق: <strong class="num">{{ $dueDate }}</strong>
                                @endif
                            </div>
                        </div>

                        <div class="text-end">
                            <div class="muted">العملة</div>
                            <div class="fw-bold">{{ $currency }}</div>
                            <div class="item-meta">سعر الصرف: <span class="num">{{ number_format($fx, 6) }}</span></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KPI Row --}}
            <div class="col-lg-3 col-md-6">
                <div class="kpi">
                    <div class="label">الإجمالي النهائي</div>
                    <div class="value num">{{ number_format($total, 2) }}</div>
                    <div class="sub">يشمل ضريبة + مصاريف</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="kpi">
                    <div class="label">المدفوع</div>
                    <div class="value num">{{ number_format($paid, 2) }}</div>
                    <div class="sub">حسب السداد/نوع الدفع</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="kpi">
                    <div class="label">المتبقي</div>
                    <div class="value num">{{ number_format($remain, 2) }}</div>
                    <div class="sub">رصيد مستحق على الفاتورة</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="kpi">
                    <div class="label">VAT (مدخلات)</div>
                    <div class="value num">{{ number_format($tax, 2) }}</div>
                    <div class="sub">ضريبة قابلة للخصم</div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            {{-- بيانات المورد/الفاتورة --}}
            <div class="col-lg-8">
                <div class="card card-soft">
                    <div class="card-header">
                        <h6 class="mb-0 section-title">بيانات الفاتورة</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="kv">
                                    <div class="k">المورد</div>
                                    <div class="v">{{ $invoice->supplier->name ?? ('Supplier #' . $invoice->supplier_id) }}</div>
                                </div>
                                <div class="kv mt-2">
                                    <div class="k">هاتف المورد</div>
                                    <div class="v num">{{ $invoice->supplier->phone ?? '-' }}</div>
                                </div>
                                <div class="kv mt-2">
                                    <div class="k">كود المورد</div>
                                    <div class="v">{{ $invoice->supplier->code ?? '-' }}</div>
                                </div>

                                <div class="divider"></div>

                                <div class="kv">
                                    <div class="k">رقم الفاتورة</div>
                                    <div class="v num">{{ $invoice->invoice_number }}</div>
                                </div>
                                <div class="kv mt-2">
                                    <div class="k">كود الفاتورة</div>
                                    <div class="v">{{ $invoice->purchase_invoice_code ?? '-' }}</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="kv">
                                    <div class="k">Transaction ID</div>
                                    <div class="v num">{{ $invoice->transaction_id ?? '-' }}</div>
                                </div>
                                <div class="kv mt-2">
                                    <div class="k">Purchase Order</div>
                                    <div class="v num">{{ $invoice->purchase_order_id ?? '-' }}</div>
                                </div>
                                <div class="kv mt-2">
                                    <div class="k">تم الترحيل</div>
                                    <div class="v num">{{ $postedAt }}</div>
                                </div>

                                <div class="divider"></div>

                                <div class="kv">
                                    <div class="k">نوع خصم الفاتورة</div>
                                    <div class="v">{{ $invDiscType }}</div>
                                </div>
                                <div class="kv mt-2">
                                    <div class="k">قيمة/نسبة الخصم</div>
                                    <div class="v num">{{ $invDiscDisplay }}</div>
                                </div>
                            </div>

                            @if($invoice->notes)
                                <div class="col-12">
                                    <div class="divider"></div>
                                    <div class="muted">ملاحظات</div>
                                    <div class="fw-bold">{{ $invoice->notes }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Box الإجماليات التفصيلي --}}
            <div class="col-lg-4">
                <div class="card card-soft">
                    <div class="card-header">
                        <h6 class="mb-0 section-title">الإجماليات التفصيلية</h6>
                    </div>
                    <div class="card-body">
                        <div class="totals-box">
                            <div class="row">
                                <div class="col-7 label">قبل الخصم</div>
                                <div class="col-5 value text-end num">{{ number_format($subBefore, 2) }}</div>
                            </div>

                            <div class="row">
                                <div class="col-7 label">خصم السطور</div>
                                <div class="col-5 value text-end num">{{ number_format($linesDisc, 2) }}</div>
                            </div>

                            <div class="row">
                                <div class="col-7 label">خصم الفاتورة</div>
                                <div class="col-5 value text-end num">{{ number_format($invDiscValue, 2) }}</div>
                            </div>

                            <div class="row">
                                <div class="col-7 label">بعد الخصم</div>
                                <div class="col-5 value text-end num">{{ number_format($subAfter, 2) }}</div>
                            </div>

                            <div class="row">
                                <div class="col-7 label">VAT</div>
                                <div class="col-5 value text-end num">{{ number_format($tax, 2) }}</div>
                            </div>

                            <div class="row">
                                <div class="col-7 label">شحن/نقل</div>
                                <div class="col-5 value text-end num">{{ number_format($ship, 2) }}</div>
                            </div>

                            <div class="row">
                                <div class="col-7 label">مصاريف أخرى</div>
                                <div class="col-5 value text-end num">{{ number_format($other, 2) }}</div>
                            </div>

                            <hr>

                            <div class="row">
                                <div class="col-7 label">الإجمالي النهائي</div>
                                <div class="col-5 value text-end num">{{ number_format($total, 2) }}</div>
                            </div>

                            <div class="row">
                                <div class="col-7 label">المدفوع</div>
                                <div class="col-5 value text-end num">{{ number_format($paid, 2) }}</div>
                            </div>

                            <div class="row">
                                <div class="col-7 label">المتبقي</div>
                                <div class="col-5 value text-end num">{{ number_format($remain, 2) }}</div>
                            </div>

                            @if($returnsCount > 0)
                                <hr>
                                <div class="row">
                                    <div class="col-7 label">إجمالي المرتجعات</div>
                                    <div class="col-5 value text-end num">{{ number_format($returnsTotal, 2) }}</div>
                                </div>
                            @endif
                        </div>

                        <div class="mt-3 muted">
                            آخر تعديل بواسطة: <strong>{{ $invoice->updated_by ?? '-' }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ✅ قيد اليومية / الحركات المحاسبية --}}
            <div class="col-12">
                <div class="card card-soft">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="mb-0 section-title">الحركات المحاسبية (قيد اليومية)</h6>

                        @if(!$je)
                            <span class="chip chip-gray">لا يوجد قيد (لم يتم الترحيل بعد)</span>
                        @else
                            <div class="d-flex flex-wrap gap-2">
                            <span class="chip chip-blue">
                                <i class="fa fa-hashtag"></i>
                                رقم القيد: <span class="num">{{ $je->entry_number }}</span>
                            </span>
                                <span class="chip chip-gray">
                                <i class="fa fa-calendar"></i>
                                {{ \Carbon\Carbon::parse($je->entry_date)->format('Y-m-d') }}
                            </span>
                                <span class="chip {{ $jeBalanced ? 'chip-green' : 'chip-red' }}">
                                <i class="fa fa-balance-scale"></i>
                                {{ $jeBalanced ? 'متوازن' : 'غير متوازن' }}
                            </span>
                            </div>
                        @endif
                    </div>

                    <div class="card-body">
                        @if(!$je)
                            <div class="text-center muted py-3">
                                لا توجد حركات محاسبية لأن الفاتورة مازالت (Draft) أو لم تُرحّل.
                            </div>
                        @else
                            <div class="row g-3 mb-3">
                                <div class="col-md-7">
                                    <div class="kv">
                                        <div class="k">الوصف</div>
                                        <div class="v">{{ $je->description ?? '-' }}</div>
                                    </div>
                                    <div class="kv mt-2">
                                        <div class="k">المصدر</div>
                                        <div class="v">{{ $je->source ?? 'purchase' }}</div>
                                    </div>
                                    <div class="kv mt-2">
                                        <div class="k">Reference</div>
                                        <div class="v muted">{{ $je->reference_type ?? '-' }} #{{ $je->reference_id ?? '-' }}</div>
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <div class="totals-box">
                                        <div class="row">
                                            <div class="col-7 label">إجمالي مدين</div>
                                            <div class="col-5 value text-end num">{{ number_format($jeDebit, 2) }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-7 label">إجمالي دائن</div>
                                            <div class="col-5 value text-end num">{{ number_format($jeCredit, 2) }}</div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <div class="col-7 label">الفرق</div>
                                            <div class="col-5 value text-end num">{{ number_format(abs($jeDebit - $jeCredit), 2) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead>
                                    <tr>
                                        <th style="width:55px;">#</th>
                                        <th>الحساب</th>
                                        <th style="width:130px;">رقم الحساب</th>
                                        <th style="width:140px;" class="text-center">مدين</th>
                                        <th style="width:140px;" class="text-center">دائن</th>
                                        <th style="width:110px;" class="text-center">عملة</th>
                                        <th style="width:110px;" class="text-center">FX</th>
                                        <th>Memo / أبعاد</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($jeLines as $i => $ln)
                                        @php
                                            $dims = [];
                                            if ($ln->branch_id) $dims[] = 'Branch: '.$ln->branch_id;
                                            if ($ln->warehouse_id) $dims[] = 'WH: '.$ln->warehouse_id;
                                            if ($ln->cost_center_id) $dims[] = 'CC: '.$ln->cost_center_id;
                                            if ($ln->project_id) $dims[] = 'PRJ: '.$ln->project_id;
                                        @endphp
                                        <tr>

                                            <td>{{ $i + 1 }}</td>

                                            <td class="fw-bold" ><a href="{{route('accounts.show',$ln->account->id)}}">{{ $ln->account->name ?? ('Account #' . $ln->account_id) }}</a></td>
                                            <td class="num">{{ $ln->account->account_number ?? '-' }}</td>
                                            <td class="text-center num">{{ $ln->debit > 0 ? number_format((float)$ln->debit, 2) : '-' }}</td>
                                            <td class="text-center num">{{ $ln->credit > 0 ? number_format((float)$ln->credit, 2) : '-' }}</td>
                                            <td class="text-center">
                                                <span class="chip chip-gray">{{ $ln->currency_code ?? $currency }}</span>
                                            </td>
                                            <td class="text-center num">{{ $ln->fx_rate ? number_format((float)$ln->fx_rate, 6) : '-' }}</td>
                                            <td class="muted">
                                                {{ $ln->memo ?? '-' }}
                                                @if(count($dims))
                                                    <div class="item-meta mt-1">{{ implode(' • ', $dims) }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ✅ مرتجعات الفاتورة --}}
            <div class="col-12">
                <div class="card card-soft">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="mb-0 section-title">مرتجعات على هذه الفاتورة</h6>
                        <div class="muted">
                            عدد المرتجعات: <strong>{{ $returnsCount }}</strong>
                            @if($returnsCount > 0)
                                — إجمالي المرتجع: <strong class="num">{{ number_format($returnsTotal, 2) }}</strong>
                            @endif
                        </div>
                    </div>

                    <div class="card-body">
                        @if($returnsCount === 0)
                            <div class="text-center muted py-3">لا يوجد مرتجعات مرتبطة بهذه الفاتورة</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead>
                                    <tr>
                                        <th style="width:60px;">#</th>
                                        <th>كود المرتجع</th>
                                        <th style="width:140px;">التاريخ</th>
                                        <th style="width:120px;">الحالة</th>
                                        <th style="width:160px;">الإجمالي</th>
                                        <th style="width:140px;">إجراءات</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($invoice->returns as $i => $r)
                                        @php
                                            $rChip = match ($r->status) {
                                                'draft' => ['chip-gray','مسودة'],
                                                'posted' => ['chip-blue','مُرحّل'],
                                                'cancelled' => ['chip-red','ملغى'],
                                                default => ['chip-gray',$r->status],
                                            };
                                            $rDate = $r->return_date ? \Carbon\Carbon::parse($r->return_date)->format('Y-m-d') : '-';
                                        @endphp
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td class="fw-bold">{{ $r->purchase_return_code ?? ('#'.$r->id) }}</td>
                                            <td class="num">{{ $rDate }}</td>
                                            <td>
                                            <span class="chip {{ $rChip[0] }}">
                                                <i class="fa fa-circle" style="font-size:8px;"></i>
                                                {{ $rChip[1] }}
                                            </span>
                                            </td>
                                            <td class="num fw-bold">{{ number_format((float)($r->total ?? 0), 2) }}</td>
                                            <td class="no-print">
                                                @if(Route::has('purchase_returns.show'))
                                                    <a href="{{ route('purchase_returns.show', $r->id) }}" class="btn btn-sm btn-light">
                                                        <i class="fa fa-eye"></i> عرض
                                                    </a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ✅ بنود الفاتورة --}}
            <div class="col-12 mb-2">
                <div class="card card-soft">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 section-title">بنود الفاتورة</h6>
                        <div class="muted">عدد البنود: <strong>{{ $invoice->items?->count() ?? 0 }}</strong></div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead>
                                <tr>
                                    <th style="width:60px;">#</th>
                                    <th>الصنف</th>
                                    <th style="width:160px;">المخزن (نص)</th>
                                    <th class="text-center" style="width:110px;">الكمية</th>
                                    <th class="text-center" style="width:130px;">سعر الوحدة</th>
                                    <th class="text-center" style="width:160px;">الخصم</th>
                                    <th class="text-center" style="width:120px;">الضريبة</th>
                                    <th class="text-center" style="width:150px;">إجمالي السطر</th>
                                </tr>
                                </thead>

                                <tbody>
                                @forelse($invoice->items as $idx => $it)
                                    @php
                                        $discType = $it->discount_type ?? 'none';
                                        $discText = '-';
                                        if ($discType === 'percent') $discText = number_format((float)($it->discount_rate ?? 0), 2).'%';
                                        elseif ($discType === 'fixed') $discText = number_format((float)($it->discount_value ?? 0), 2);

                                        $taxText = ($it->tax_rate !== null) ? number_format((float)$it->tax_rate, 2).'%' : '-';
                                    @endphp
                                    <tr>
                                        <td>{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $it->item->name ?? ('Item #' . $it->item_id) }}</div>
                                            <div class="item-meta">
                                                {{ $it->item->items_code ?? '-' }}
                                                @if(($it->item->barcode ?? null)) — {{ $it->item->barcode }} @endif
                                            </div>
                                        </td>
                                        <td>{{ $it->warehouse_name_snapshot ?? '-' }}</td>
                                        <td class="text-center num">{{ number_format((float)$it->quantity, 2) }}</td>
                                        <td class="text-center num">{{ number_format((float)$it->unit_price, 2) }}</td>
                                        <td class="text-center num">{{ $discText }}</td>
                                        <td class="text-center num">{{ $taxText }}</td>
                                        <td class="text-center num line-total">{{ number_format((float)($it->line_total ?? 0), 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center muted py-4">لا يوجد بنود</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($invoice->notes)
                            <div class="mt-3">
                                <div class="muted">ملاحظات</div>
                                <div class="fw-bold">{{ $invoice->notes }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
