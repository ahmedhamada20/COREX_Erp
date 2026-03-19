{{-- resources/views/admin/purchase_returns/show.blade.php --}}
@extends('admin.layouts.master')

@section('title', 'عرض مرتجع مشتريات')

@section('css')
    <style>
        :root{
            --muted:#64748b;
            --card:#ffffff;
            --border:#e5e7eb;
            --soft:#f8fafc;
            --ink:#0f172a;
        }

        .page-title{ font-weight:900; letter-spacing:.2px; }
        .muted{ color:var(--muted); }
        .num{ direction:ltr; text-align:left; font-variant-numeric:tabular-nums; }
        .fw-black{ font-weight:900; color:var(--ink); }

        .card-soft{
            background:var(--card);
            border:1px solid var(--border);
            box-shadow:0 2px 12px rgba(0,0,0,.04);
            border-radius:14px;
        }
        .card-soft .card-header{ background:transparent; border-bottom:1px solid var(--border); }

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
        .chip-gray{ background:#f1f5f9; border-color:#e2e8f0; color:#0f172a; }
        .chip-blue{ background:#eef2ff; border-color:#c7d2fe; color:#3730a3; }
        .chip-green{ background:#ecfdf5; border-color:#a7f3d0; color:#065f46; }
        .chip-yellow{ background:#fffbeb; border-color:#fde68a; color:#92400e; }
        .chip-red{ background:#fef2f2; border-color:#fecaca; color:#991b1b; }

        .kv{ display:flex; gap:10px; align-items:flex-start; }
        .kv .k{ width:170px; color:var(--muted); font-size:13px; }
        .kv .v{ font-weight:900; color:var(--ink); }

        .totals-box{
            background:var(--soft);
            border:1px dashed #cbd5e1;
            border-radius:14px;
            padding:14px;
        }
        .totals-box .row + .row{ margin-top:8px; }
        .totals-box .label{ color:var(--muted); font-size:13px; }
        .totals-box .value{ font-weight:900; }

        .table thead th{ background:#f8fafc; white-space:nowrap; }
        .table td,.table th{ vertical-align:middle; }
        .line-total{ font-weight:900; }
        .item-meta{ font-size:12px; color:var(--muted); }

        .section-title{ font-weight:900; color:var(--ink); }
        .subtle-sep{ height:1px; background:var(--border); margin:12px 0; }

        .no-print{}
        .print-area{ background:#fff; }

        @media print {
            .no-print{ display:none !important; }
            .content-header, .breadcrumb, .navbar, .sidebar, .footer{ display:none !important; }
            .print-area{ padding:0 !important; margin:0 !important; }
            .card{ box-shadow:none !important; }
            .card-soft{ border:0 !important; }
        }
    </style>
@endsection

@section('content')

    @php
        $status = $return->status ?? 'draft';

        $statusChip = match ($status) {
            'draft'     => ['chip-gray',   'مسودة'],
            'posted'    => ['chip-blue',   'مُرحّل'],
            'cancelled' => ['chip-red',    'ملغى'],
            default     => ['chip-gray',   $status],
        };

        $returnDate = $return->return_date
            ? \Carbon\Carbon::parse($return->return_date)->format('Y-m-d')
            : ($return->date ? \Carbon\Carbon::parse($return->date)->format('Y-m-d') : '-');

        // مرجع الفاتورة
        $inv = $return->invoice ?? $return->purchaseInvoice ?? null;
        $invCode = $inv?->purchase_invoice_code ?? '-';
        $invNo   = $inv?->invoice_number ?? ($return->return_number ?? '-');

        // totals
        $subtotal = (float)($return->subtotal ?? 0);
        $tax      = (float)($return->tax_value ?? 0);
        $total    = (float)($return->total ?? 0);

        $postedAt = $return->posted_at
            ? \Carbon\Carbon::parse($return->posted_at)->format('Y-m-d H:i')
            : '-';

        $canEdit   = ($status === 'draft');
        $canPost   = ($status === 'draft');
        $canCancel = ($status !== 'cancelled');

        // Journal Entry (لو عامل relation return->journalEntry)
        $je = $return->journalEntry ?? null;

        $jeStatus = $je?->status ?? null;
        $jeChip = match($jeStatus) {
            'posted'   => ['chip-blue','مُرحّل'],
            'draft'    => ['chip-gray','مسودة'],
            'reversed' => ['chip-red','معكوس'],
            default    => ['chip-gray', $jeStatus ?: 'لا يوجد قيد'],
        };

        $jeDate = $je?->entry_date ? \Carbon\Carbon::parse($je->entry_date)->format('Y-m-d') : '-';
        $sumDebit  = (float)($je?->total_debit ?? 0);
        $sumCredit = (float)($je?->total_credit ?? 0);
        $diff = round($sumDebit - $sumCredit, 2);

        // Flags
        $hasJE = (bool)$je;
    @endphp

    <div class="content-header mb-3 no-print">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 page-title">عرض مرتجع مشتريات</h5>
                <small class="text-muted">
                    {{ $return->purchase_return_code ?? ('PR#'.$return->id) }}
                    — مرجع فاتورة: {{ $invCode }} / {{ $invNo }}
                </small>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('purchase_returns.index') }}" class="btn btn-sm btn-light">رجوع</a>

                @if(Route::has('purchase_returns.edit') && $canEdit)
                    <a href="{{ route('purchase_returns.edit', $return->id) }}" class="btn btn-sm btn-primary">
                        تعديل
                    </a>
                @endif

                @if(Route::has('purchase_returns.pdf'))
                    <a href="{{ route('purchase_returns.pdf', $return->id) }}" class="btn btn-sm btn-danger" target="_blank">
                        طباعه
                    </a>
                @else
                    <button type="button" class="btn btn-sm btn-danger" onclick="window.print()">طباعه</button>
                @endif

                @if(Route::has('purchase_returns.post'))
                    <form action="{{ route('purchase_returns.post', $return->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit"
                                class="btn btn-sm btn-success"
                                onclick="return confirm('تأكيد ترحيل المرتجع؟ سيتم إنشاء قيد يومية.');"
                            {{ !$canPost ? 'disabled' : '' }}>
                            ترحيل
                        </button>
                    </form>
                @endif

                @if(Route::has('purchase_returns.cancel'))
                    <form action="{{ route('purchase_returns.cancel', $return->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit"
                                class="btn btn-sm btn-warning"
                                onclick="return confirm('هل أنت متأكد من إلغاء المرتجع؟');"
                            {{ !$canCancel ? 'disabled' : '' }}>
                            إلغاء
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="print-area">
        <div class="row">

            {{-- =======================
                Header Card (Return Info)
            ======================== --}}
            <div class="col-lg-8 mb-3">
                <div class="card card-soft">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <div class="section-title">بيانات المرتجع</div>

                            <div class="mt-2 d-flex flex-wrap gap-2">
                            <span class="chip {{ $statusChip[0] }}">
                                <i class="fa fa-circle" style="font-size:8px;"></i>
                                الحالة: {{ $statusChip[1] }}
                            </span>

                                <span class="chip chip-blue">
                                <i class="fa fa-calendar"></i>
                                التاريخ: {{ $returnDate }}
                            </span>

                                <span class="chip chip-yellow">
                                <i class="fa fa-hashtag"></i>
                                رقم المرتجع: <span class="num">{{ $return->return_number ?? '-' }}</span>
                            </span>

                                @if($hasJE)
                                    <span class="chip {{ $jeChip[0] }}">
                                    <i class="fa fa-book"></i>
                                    قيد يومية: {{ $jeChip[1] }}
                                </span>
                                @endif
                            </div>
                        </div>

                        <div class="text-end">
                            <div class="muted">مرجع الفاتورة</div>
                            <div class="fw-black">
                                {{ $invCode }} — {{ $invNo }}
                            </div>

                            @if(Route::has('purchase_invoices.show') && $inv?->id)
                                <a class="small no-print" href="{{ route('purchase_invoices.show', $inv->id) }}">
                                    فتح الفاتورة
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <div class="kv">
                                    <div class="k">المورد</div>
                                    <div class="v">{{ $return->supplier->name ?? ('Supplier #'.$return->supplier_id) }}</div>
                                </div>

                                <div class="kv mt-2">
                                    <div class="k">هاتف المورد</div>
                                    <div class="v">{{ $return->supplier->phone ?? '-' }}</div>
                                </div>

                                <div class="kv mt-2">
                                    <div class="k">كود المورد</div>
                                    <div class="v">{{ $return->supplier->code ?? '-' }}</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="kv">
                                    <div class="k">Transaction ID</div>
                                    <div class="v">{{ $return->transaction_id ?? '-' }}</div>
                                </div>

                                <div class="kv mt-2">
                                    <div class="k">تم الترحيل</div>
                                    <div class="v">{{ $postedAt }}</div>
                                </div>

                                <div class="kv mt-2">
                                    <div class="k">آخر تعديل بواسطة</div>
                                    <div class="v">{{ $return->updated_by ?? '-' }}</div>
                                </div>
                            </div>

                            @if($return->notes)
                                <div class="col-12">
                                    <div class="subtle-sep"></div>
                                    <div class="muted">ملاحظات</div>
                                    <div class="fw-black">{{ $return->notes }}</div>
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            </div>

            {{-- =======================
                Totals Card
            ======================== --}}
            <div class="col-lg-4 mb-3">
                <div class="card card-soft">
                    <div class="card-header">
                        <div class="section-title">الإجماليات</div>
                    </div>
                    <div class="card-body">
                        <div class="totals-box">

                            <div class="row">
                                <div class="col-7 label">Subtotal</div>
                                <div class="col-5 value text-end num">{{ number_format($subtotal, 2) }}</div>
                            </div>

                            <div class="row">
                                <div class="col-7 label">VAT</div>
                                <div class="col-5 value text-end num">{{ number_format($tax, 2) }}</div>
                            </div>

                            <hr>

                            <div class="row">
                                <div class="col-7 label">Total</div>
                                <div class="col-5 value text-end num">{{ number_format($total, 2) }}</div>
                            </div>

                        </div>

                        <div class="mt-3 muted">
                            آخر تعديل بواسطة: <strong>{{ $return->updated_by ?? '-' }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            {{-- =======================
                Journal Entry (Ledger Movements)
            ======================== --}}
            <div class="col-12 mb-3">
                <div class="card card-soft">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <div class="section-title">الحركات المحاسبية (قيد اليومية)</div>
                            <div class="mt-2 d-flex flex-wrap gap-2">
                            <span class="chip {{ $jeChip[0] }}">
                                <i class="fa fa-circle" style="font-size:8px;"></i>
                                الحالة: {{ $jeChip[1] }}
                            </span>

                                @if($hasJE)
                                    <span class="chip chip-blue">
                                    <i class="fa fa-hashtag"></i>
                                    رقم القيد: <span class="num">{{ $je->entry_number }}</span>
                                </span>

                                    <span class="chip chip-yellow">
                                    <i class="fa fa-calendar"></i>
                                    تاريخ القيد: {{ $jeDate }}
                                </span>

                                    @if($je->source)
                                        <span class="chip chip-gray">
                                        <i class="fa fa-tag"></i>
                                        المصدر: {{ $je->source }}
                                    </span>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <div class="text-end">
                            @if($hasJE)
                                <div class="muted">إجمالي مدين / دائن</div>
                                <div class="fw-black num">
                                    {{ number_format($sumDebit, 2) }} / {{ number_format($sumCredit, 2) }}
                                </div>
                                @if($diff != 0)
                                    <div class="small text-danger">
                                        غير متزن: فرق <span class="num">{{ number_format($diff, 2) }}</span>
                                    </div>
                                @endif
                            @else
                                <div class="muted">لا يوجد قيد</div>
                                <div class="fw-black">سيتم إنشاء القيد عند ترحيل المرتجع</div>
                            @endif
                        </div>
                    </div>

                    <div class="card-body">
                        @if(!$hasJE)
                            <div class="text-center muted py-3">
                                هذا المرتجع <strong>غير مُرحّل</strong> أو لم يتم إنشاء قيد يومية له بعد.
                            </div>
                        @else

                            @if($je->description)
                                <div class="mb-3">
                                    <div class="muted">وصف القيد</div>
                                    <div class="fw-black">{{ $je->description }}</div>
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead>
                                    <tr>
                                        <th style="width:60px;">#</th>
                                        <th>الحساب</th>
                                        <th style="width:160px;">رقم الحساب</th>
                                        <th class="text-center" style="width:180px;">مدين</th>
                                        <th class="text-center" style="width:180px;">دائن</th>
                                        <th>مذكرة</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    @forelse(($je->lines ?? []) as $i => $ln)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>

                                            <td class="fw-black">
                                                <a href="{{route('accounts.show',$ln->account->id)}}">


                                                {{ $ln->account->name ?? ('Account #'.$ln->account_id) }}
                                                </a>
                                            </td>

                                            <td class="num">
                                                {{ $ln->account->account_number ?? '-' }}
                                            </td>

                                            <td class="text-center num">
                                                {{ number_format((float)($ln->debit ?? 0), 2) }}
                                            </td>

                                            <td class="text-center num">
                                                {{ number_format((float)($ln->credit ?? 0), 2) }}
                                            </td>

                                            <td>{{ $ln->memo ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center muted py-3">لا توجد سطور بالقيد</td>
                                        </tr>
                                    @endforelse
                                    </tbody>

                                    <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">الإجمالي</th>
                                        <th class="text-center num">{{ number_format($sumDebit, 2) }}</th>
                                        <th class="text-center num">{{ number_format($sumCredit, 2) }}</th>
                                        <th></th>
                                    </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="mt-3 muted">
                                ملاحظة محاسبية: مرتجع المشتريات عادةً يقلّل <strong>مستحقات المورد</strong> ويعكس <strong>المخزون/مصروف الشراء</strong> و<strong>VAT مدخلات</strong> حسب البنود.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- =======================
                Items Table
            ======================== --}}
            <div class="col-12 mb-3">
                <div class="card card-soft">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="section-title">بنود المرتجع</div>
                        <div class="muted">
                            عدد البنود: <strong>{{ $return->items?->count() ?? 0 }}</strong>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead>
                                <tr>
                                    <th style="width:60px;">#</th>
                                    <th>الصنف</th>
                                    <th style="width:160px;">المخزن</th>
                                    <th class="text-center" style="width:110px;">الكمية</th>
                                    <th class="text-center" style="width:130px;">سعر الوحدة</th>
                                    <th class="text-center" style="width:110px;">VAT%</th>
                                    <th class="text-center" style="width:120px;">VAT</th>
                                    <th class="text-center" style="width:140px;">Subtotal</th>
                                    <th class="text-center" style="width:160px;">إجمالي السطر</th>
                                </tr>
                                </thead>

                                <tbody>
                                @forelse($return->items as $idx => $it)
                                    @php
                                        $taxRate = $it->tax_rate !== null ? number_format((float)$it->tax_rate, 2).'%' : '-';
                                    @endphp
                                    <tr>
                                        <td>{{ $idx + 1 }}</td>

                                        <td>
                                            <div class="fw-black">
                                                {{ $it->item->name ?? ('Item #'.$it->item_id) }}
                                            </div>
                                            <div class="item-meta">
                                                {{ $it->item->items_code ?? '-' }}
                                                @if(($it->item->barcode ?? null))
                                                    — {{ $it->item->barcode }}
                                                @endif
                                            </div>

                                            @if($it->purchase_invoice_item_id)
                                                <div class="item-meta">
                                                    مرجع سطر فاتورة: <span class="num">#{{ $it->purchase_invoice_item_id }}</span>
                                                </div>
                                            @endif
                                        </td>

                                        <td>{{ $it->warehouse_name_snapshot ?? '-' }}</td>

                                        <td class="text-center num">{{ number_format((float)$it->quantity, 2) }}</td>
                                        <td class="text-center num">{{ number_format((float)$it->unit_price, 2) }}</td>

                                        <td class="text-center num">{{ $taxRate }}</td>
                                        <td class="text-center num">{{ number_format((float)($it->tax_value ?? 0), 2) }}</td>

                                        <td class="text-center num">{{ number_format((float)($it->line_subtotal ?? 0), 2) }}</td>

                                        <td class="text-center num line-total">
                                            {{ number_format((float)($it->line_total ?? 0), 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center muted py-4">لا يوجد بنود</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($return->notes)
                            <div class="mt-3">
                                <div class="muted">ملاحظات</div>
                                <div class="fw-black">{{ $return->notes }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
