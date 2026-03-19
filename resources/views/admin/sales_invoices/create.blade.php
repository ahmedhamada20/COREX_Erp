{{-- resources/views/admin/sales_invoices/create.blade.php --}}
@extends('admin.layouts.master')

@section('title', 'نقطة بيع - فاتورة مبيعات')

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <style>
        :root{
            --pos-blue:#0b5ed7;
            --pos-dark:#0f172a;
            --pos-muted:#64748b;
            --pos-bg:#f6f8fb;
            --pos-card:#ffffff;
            --pos-border:#e2e8f0;
            --pos-soft:#f8fafc;
            --pos-danger:#dc3545;
            --pos-success:#16a34a;
            --pos-warning:#f59e0b;
        }

        .pos-wrap{ background:var(--pos-bg); border-radius:18px; padding:14px; }
        .pos-topbar{
            background: linear-gradient(135deg, rgba(11,94,215,.95), rgba(11,94,215,.70));
            color:#fff; border-radius:18px; padding:14px 16px;
        }
        .pos-topbar .muted{ color: rgba(255,255,255,.86); }

        .pos-card{ background:var(--pos-card); border:1px solid var(--pos-border); border-radius:18px; overflow:hidden; }
        .pos-card .pos-card-hd{
            padding:12px 14px; border-bottom:1px solid var(--pos-border);
            display:flex; align-items:center; justify-content:space-between; gap:10px;
            background: linear-gradient(180deg, #fff, #fbfdff);
        }
        .pos-card .pos-card-bd{ padding:12px 14px; }

        .pos-btn{ border-radius:14px; padding:10px 12px; font-weight:950; }
        .btn-icon{
            width:40px; height:40px;
            display:inline-flex; align-items:center; justify-content:center;
            border-radius:14px;
        }

        .badge-soft{
            background: rgba(11,94,215,.12);
            color: var(--pos-blue);
            border:1px solid rgba(11,94,215,.18);
            border-radius:999px;
            padding:6px 10px;
            font-weight:950;
            font-size:12px;
            white-space: nowrap;
        }
        .badge-soft.success{ background: rgba(22,163,74,.12); color: var(--pos-success); border-color: rgba(22,163,74,.18); }
        .badge-soft.warn{ background: rgba(245,158,11,.14); color: #a16207; border-color: rgba(245,158,11,.18); }
        .badge-soft.danger{ background: rgba(220,53,69,.10); color:#b02a37; border-color: rgba(220,53,69,.18); }

        .hint{ font-size:12px; color:var(--pos-muted); }
        .required:after{ content:" *"; color:var(--pos-danger); font-weight:900; }

        .sticky{ position: sticky; top: 12px; }
        @media (max-width: 992px){ .sticky{ position: static; } }

        .money{ direction:ltr; unicode-bidi:bidi-override; display:inline-block; font-variant-numeric: tabular-nums; }

        .kpi{
            border:1px solid var(--pos-border);
            border-radius:18px;
            padding:12px;
            background:#fff;
        }
        .kpi .label{ font-size:12px; color:var(--pos-muted); }
        .kpi .value{ font-size:20px; font-weight:950; color:var(--pos-dark); }

        .line{ height:1px; background:var(--pos-border); margin:10px 0; }

        /* Cart */
        .cart-table{ width:100%; border-collapse:separate; border-spacing:0 10px; }
        .cart-row{
            background:#fff;
            border:1px solid var(--pos-border);
            border-radius:18px;
            overflow:hidden;
        }
        .cart-row td{ padding:10px; vertical-align:top; }
        .cart-row .name{ font-weight:950; color:var(--pos-dark); }
        .cart-row .sub{ font-size:12px; color:var(--pos-muted); }

        .cart-row input, .cart-row select{
            height:40px;
            border-radius:14px;
            border:1px solid var(--pos-border);
            padding:0 10px;
        }

        .qty-wrap{ display:flex; gap:6px; align-items:center; }
        .qty-wrap .btn-icon{ width:40px; height:40px; border-radius:14px; }
        .qty-wrap input{ text-align:center; }

        /* Select2 */
        .select2-container{ width:100% !important; }
        .select2-container--bootstrap-5 .select2-selection--single{
            min-height:40px !important;
            padding:.45rem .75rem !important;
            border-radius:14px !important;
            border-color: var(--pos-border) !important;
        }
        .select2-container--bootstrap-5 .select2-dropdown{
            border-radius:14px !important;
            overflow:hidden;
            border-color: var(--pos-border) !important;
        }
        .select2-results__option{ padding:10px 12px !important; }
        .select2-container--bootstrap-5 .select2-selection__clear{
            margin-left:0 !important;
            margin-right:.35rem !important;
        }

        .kbd{
            font-size:12px; font-weight:950;
            border:1px solid rgba(255,255,255,.35);
            border-bottom-width:2px;
            padding:2px 8px;
            border-radius:10px;
            color:#fff;
            background: rgba(255,255,255,.12);
        }

        .stock-pill{
            display:inline-flex; align-items:center; gap:6px;
            font-size:12px; font-weight:900;
            padding:6px 10px; border-radius:999px;
            border:1px solid var(--pos-border);
            background:#fff;
            color:var(--pos-dark);
            margin-top:8px;
        }
        .stock-pill.low{ border-color: rgba(220,53,69,.25); background: rgba(220,53,69,.06); color:#b02a37; }
        .stock-pill.warn{ border-color: rgba(245,158,11,.25); background: rgba(245,158,11,.08); color:#a16207; }

        .offer-pill{
            display:inline-flex; align-items:center; gap:6px;
            border:1px solid rgba(245,158,11,.20);
            background: rgba(245,158,11,.10);
            color:#92400e;
            border-radius:999px;
            padding:6px 10px;
            font-weight:900;
            font-size:12px;
        }

        .park-list{
            max-height: 280px;
            overflow:auto;
            border:1px solid var(--pos-border);
            border-radius:14px;
            background:#fff;
        }
        .park-item{
            padding:10px 12px;
            border-bottom:1px solid var(--pos-border);
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:10px;
            cursor:pointer;
        }
        .park-item:last-child{ border-bottom:0; }
        .park-item:hover{ background: var(--pos-soft); }

        /* barcode bar in cart header */
        .barcode-bar{
            display:flex; align-items:center; gap:10px; flex-wrap: wrap;
            background: var(--pos-soft);
            border:1px solid var(--pos-border);
            padding:10px;
            border-radius:16px;
        }
        .barcode-bar input{
            border:1px solid var(--pos-border);
            border-radius:14px;
            height:40px;
            padding:0 10px;
            background:#fff;
        }
        .barcode-bar .grow{ flex: 1 1 260px; }
        .barcode-bar .small{ width: 120px; }

        /* Line edit modal header */
        .modal-soft-hd{
            background:linear-gradient(135deg, rgba(15,23,42,.95), rgba(15,23,42,.78));
            color:#fff;
        }

        /* Line compact meta (static - does not change titles) */
        .line-meta{
            display:flex;
            gap:8px;
            flex-wrap:wrap;
            margin-top:10px;
        }
        .line-chip{
            border:1px solid var(--pos-border);
            background:#fff;
            border-radius:999px;
            padding:6px 10px;
            font-size:12px;
            font-weight:900;
            color:var(--pos-dark);
        }
        .line-chip .muted{ color:var(--pos-muted); font-weight:800; margin-inline-start:6px; }

        .action-col{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            flex-direction:column;
            gap:10px;
        }
        .action-btns{
            display:flex;
            gap:8px;
            justify-content:flex-start;
        }
    </style>
@endsection

@section('content')
    @php
        $today       = now()->timezone('Africa/Cairo')->toDateString();
        $defaultDate = old('invoice_date', $today);
        $defaultPay  = old('payment_type', 'cash');

        $warehouses  = $warehouses ?? collect();
        $treasuries  = $treasuries ?? collect();
        $terminals   = $terminals ?? collect();

        $defaultWarehouse = old('warehouse_id', $warehouses->first()->id ?? '');

        $openShiftTreasuryId   = $openShiftTreasuryId ?? null;
        $openShiftTreasuryName = $openShiftTreasuryName ?? null;

        $defaultGlobalDiscType  = old('global_discount_type', 'amount');
        $defaultGlobalDiscValue = old('global_discount_value', 0);

        $defaultVatRate = old('global_vat_rate', 0);
    @endphp

    {{-- Top Bar --}}
    <div class="pos-topbar mb-3">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h4 class="mb-0">نقطة بيع (POS) — فاتورة مبيعات</h4>
                    <span class="badge-soft">Draft</span>
                    <span class="kbd">F4: عميل</span>
                    <span class="kbd">F6: باركود</span>
                    <span class="kbd">F9: دفع</span>
                    <span class="kbd">F7: Hold</span>
                    <span class="kbd">Del: تصفير</span>

                    @if($openShiftTreasuryId)
                        <span class="badge-soft success">Shift: Open</span>
                        <span class="badge-soft">خزنة الشِفت: {{ $openShiftTreasuryName ?? ('#'.$openShiftTreasuryId) }}</span>
                    @else
                        <span class="badge-soft danger">Shift: Not Open</span>
                    @endif
                </div>
                <div class="muted small mt-1">
                    الأسعار/الخصم/الضريبة داخل مودال السطر فقط (ثابتة ومش بتتزحلق).
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('sales_invoices.index') }}" class="btn btn-light pos-btn">
                    <i class="ti ti-arrow-right"></i> رجوع
                </a>
                <button type="submit" form="posForm" class="btn btn-dark pos-btn" id="topSubmitBtn">
                    <i class="ti ti-device-floppy"></i> حفظ الفاتورة
                </button>
            </div>
        </div>
    </div>

    @include('admin.Alerts')

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-bold mb-1">فيه أخطاء لازم تتصلح:</div>
            <ul class="mb-0">
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form id="posForm" method="POST" action="{{ route('sales_invoices.store') }}">
        @csrf

        <input type="hidden" id="prevent_negative_stock" value="1">

        {{-- Totals --}}
        <input type="hidden" name="subtotal"         id="subtotal_input"  value="{{ old('subtotal', 0) }}">
        <input type="hidden" name="discount_amount"  id="discount_input"  value="{{ old('discount_amount', 0) }}">
        <input type="hidden" name="vat_amount"       id="vat_input"       value="{{ old('vat_amount', 0) }}">
        <input type="hidden" name="total"            id="total_input"     value="{{ old('total', 0) }}">
        <input type="hidden" name="remaining_amount" id="remaining_input" value="{{ old('remaining_amount', 0) }}">

        {{-- Global Discount --}}
        <input type="hidden" name="global_discount_type"  id="global_discount_type_input" value="{{ $defaultGlobalDiscType }}">
        <input type="hidden" name="global_discount_value" id="global_discount_value_input" value="{{ $defaultGlobalDiscValue }}">

        {{-- Payment (split) --}}
        <input type="hidden" name="payment[mode]"        id="payment_mode" value="split">
        <input type="hidden" name="payment[cash]"        id="pay_cash" value="{{ old('payment.cash', 0) }}">
        <input type="hidden" name="payment[card]"        id="pay_card" value="{{ old('payment.card', 0) }}">
        <input type="hidden" name="payment[wallet]"      id="pay_wallet" value="{{ old('payment.wallet', 0) }}">
        <input type="hidden" name="payment[treasury_id]" id="pay_treasury_id" value="{{ old('payment.treasury_id', $openShiftTreasuryId ?? '') }}">
        <input type="hidden" name="payment[terminal_id]" id="pay_terminal_id" value="{{ old('payment.terminal_id', '') }}">

        {{-- Pricing context --}}
        <input type="hidden" name="pricing[customer_category_id]" id="pricing_customer_category_id" value="">
        <input type="hidden" name="pricing[price_list_id]" id="pricing_price_list_id" value="">

        <div class="pos-wrap">
            <div class="row g-3">

                {{-- بيانات الفاتورة --}}
                <div class="col-12">
                    <div class="pos-card">
                        <div class="pos-card-hd">
                            <div class="fw-bold">بيانات الفاتورة</div>
                            <div class="d-flex gap-2 flex-wrap">
                                <span class="badge-soft success">POS Ready</span>
                                <span class="badge-soft warn">Hold/Park</span>
                                <span class="badge-soft">Split Payment</span>
                            </div>
                        </div>

                        <div class="pos-card-bd">
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="form-label required">تاريخ الفاتورة</label>
                                    <input type="date" name="invoice_date" class="form-control"
                                           value="{{ $defaultDate }}" required style="border-radius:14px;height:40px;">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">الاستحقاق</label>
                                    <input type="date" name="due_date" id="due_date" class="form-control"
                                           value="{{ old('due_date') }}" style="border-radius:14px;height:40px;">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label required">الدفع</label>
                                    <select name="payment_type" id="payment_type" class="form-select" required style="border-radius:14px;height:40px;">
                                        <option value="cash" @selected($defaultPay==='cash')>كاش</option>
                                        <option value="credit" @selected($defaultPay==='credit')>آجل</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label required">المخزن</label>
                                    <select name="warehouse_id" id="warehouse_id" class="form-select" required style="border-radius:14px;height:40px;">
                                        <option value="">اختر مخزن</option>
                                        @foreach($warehouses as $w)
                                            <option value="{{ $w->id }}" @selected((string)$defaultWarehouse === (string)$w->id)>{{ $w->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="hint">المخزن يؤثر على المخزون المتاح ومنع السالب.</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label required">العميل</label>
                                    <select name="customer_id" id="customer_id" class="form-select"
                                            data-placeholder="ابحث بالاسم / الهاتف / الكود" required>
                                        <option value=""></option>
                                        @if(old('customer_id') && old('customer_text'))
                                            <option value="{{ old('customer_id') }}" selected>{{ old('customer_text') }}</option>
                                        @endif
                                    </select>
                                    <input type="hidden" name="customer_text" id="customer_text" value="{{ old('customer_text') }}">
                                    <div class="hint">F4 لفتح اختيار العميل بسرعة.</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">ملاحظات</label>
                                    <textarea name="notes" class="form-control" rows="2" style="border-radius:14px;"
                                              placeholder="ملاحظات داخلية...">{{ old('notes') }}</textarea>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex gap-2 flex-wrap align-items-center">
                                        <button type="button" id="parkBtn" class="btn btn-outline-dark pos-btn">
                                            <i class="ti ti-player-pause"></i> Hold / Park (F7)
                                        </button>
                                        <button type="button" id="openParkModal" class="btn btn-light pos-btn">
                                            <i class="ti ti-list"></i> عرض المعلّق
                                        </button>

                                        <div class="offer-pill">
                                            <i class="ti ti-gift"></i>
                                            عروض: Buy X Get Y (Auto)
                                        </div>

                                        <div class="hint d-flex align-items-center">
                                            منع السالب:
                                            <span class="badge-soft warn ms-1" id="negStockBadge">مفعل</span>
                                        </div>

                                        <button type="button" id="clearCart" class="btn btn-outline-danger pos-btn ms-auto">
                                            <i class="ti ti-trash"></i> تصفير السلة (Del)
                                        </button>
                                    </div>

                                    <div class="line"></div>

                                    @if($openShiftTreasuryId)
                                        <div class="alert alert-info mb-0">
                                            <div class="fw-bold">الخزنة مرتبطة بالشِفت المفتوح</div>
                                            <div class="small">
                                                سيتم استخدام خزنة: <b>{{ $openShiftTreasuryName ?? ('#'.$openShiftTreasuryId) }}</b> تلقائيًا في شاشة الدفع.
                                            </div>
                                        </div>
                                    @else
                                        <div class="alert alert-warning mb-0">
                                            <div class="fw-bold">لا يوجد شِفت مفتوح</div>
                                            <div class="small">
                                                لن تستطيع تطبيق الدفع حتى تفتح شِفت على خزنة (أو فعل صلاحية استثنائية).
                                            </div>
                                        </div>
                                    @endif
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                {{-- CART --}}
                <div class="col-12">
                    <div class="pos-card">
                        <div class="pos-card-hd">
                            <div class="fw-bold">السلة</div>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" id="addEmptyLine" class="btn btn-light btn-sm pos-btn">
                                    <i class="ti ti-plus"></i> سطر جديد
                                </button>
                                <span class="badge-soft">Edit داخل مودال السطر</span>
                            </div>
                        </div>

                        <div class="pos-card-bd">

                            {{-- Barcode --}}
                            <div class="barcode-bar mb-2">
                                <div class="fw-bold d-flex align-items-center gap-2">
                                    <i class="ti ti-barcode"></i> Scan Barcode
                                    <span class="hint">(F6 للتركيز)</span>
                                </div>

                                <input type="text" id="barcodeInput" class="grow" placeholder="امسح الباركود / اكتب الكود ثم Enter...">
                                <input type="number" id="barcodeQty" class="small" value="1" min="1" step="1" title="Qty">
                                <button type="button" id="barcodeBtn" class="btn btn-primary pos-btn">
                                    <i class="ti ti-search"></i> إضافة
                                </button>

                                <div class="ms-auto hint">سيتم دمج الصنف لو موجود مسبقًا.</div>
                            </div>

                            <div class="table-responsive">
                                <table class="cart-table">
                                    <tbody id="cartBody">
                                    <tr class="cart-row">
                                        {{-- Item --}}
                                        <td style="width:52%;">
                                            <div class="name">اختر صنف</div>
                                            <div class="sub">اسم/كود/باركود</div>

                                            <select class="form-select item-select mt-2"
                                                    data-placeholder="ابحث عن صنف..."
                                                    data-name="items[__i__][item_id]">
                                                <option value=""></option>
                                            </select>

                                            <input type="hidden" data-name="items[__i__][item_text]" value="">
                                            <input type="hidden" class="stock_val" data-name="items[__i__][stock]" value="">
                                            <input type="hidden" class="reorder_level_val" data-name="items[__i__][reorder_level]" value="">
                                            <input type="hidden" class="free_qty" data-name="items[__i__][free_qty]" value="0">

                                            {{-- قيم السطر (موجودة في المودال فقط لكن لازم تتبعت) --}}
                                            <input type="hidden" class="price"          value="0" data-name="items[__i__][price]">
                                            <input type="hidden" class="discount_value" value="0" data-name="items[__i__][discount_value]">
                                            <input type="hidden" class="discount_type"  value="amount" data-name="items[__i__][discount_type]">
                                            <input type="hidden" class="vat_rate"       value="{{ (float)$defaultVatRate }}" data-name="items[__i__][vat_rate]">
                                            <input type="hidden" class="cost_price"     value="0" data-name="items[__i__][cost_price]">

                                            <div class="stock-pill d-none">
                                                <i class="ti ti-box"></i>
                                                <span>متاح:</span>
                                                <span class="money stock_view">0</span>
                                            </div>

                                            <div class="hint mt-2">
                                                <span class="offer-pill d-none offer_view">
                                                    <i class="ti ti-gift"></i> هدية: <span class="money free_view">0</span>
                                                </span>
                                            </div>

                                            {{-- عرض ثابت صغير للقيم (اختياري) — بدون ما “يتزحلق” أي Title --}}
                                            <div class="line-meta">
                                                <span class="line-chip">السعر <span class="muted money price_view">0.00</span></span>
                                                <span class="line-chip">الخصم <span class="muted disc_view">0.00 ج</span></span>
                                                <span class="line-chip">VAT% <span class="muted money vat_view">0</span></span>
                                            </div>
                                        </td>

                                        {{-- Qty --}}
                                        <td style="width:24%;">
                                            <div class="sub mb-1">كمية</div>
                                            <div class="qty-wrap">
                                                <button type="button" class="btn btn-light btn-icon qty-minus"><i class="ti ti-minus"></i></button>
                                                <input type="number" min="0" step="0.0001" class="w-100 qty" value="1" data-name="items[__i__][qty]">
                                                <button type="button" class="btn btn-light btn-icon qty-plus"><i class="ti ti-plus"></i></button>
                                            </div>
                                            <div class="hint mt-1">لو الكمية > المخزون هيطلع تحذير/منع.</div>
                                        </td>

                                        {{-- Actions + Net --}}
                                        <td style="width:24%;">
                                            <div class="action-col">
                                                <div>
                                                    <div class="sub mb-1">الصافي</div>
                                                    <div class="money fw-bold line_total_view">0.00</div>
                                                    <input type="hidden" class="line_total" value="0" data-name="items[__i__][line_total]">
                                                </div>

                                                <div class="action-btns">
                                                    <button type="button" class="btn btn-outline-primary btn-icon edit-line" title="تعديل السعر/الخصم/VAT">
                                                        <i class="ti ti-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger btn-icon remove-line" title="حذف">
                                                        <i class="ti ti-x"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="hint mt-2">
                                ✅ الأسعار/الخصومات/VAT مش هتظهر كمدخلات في السطر… كله داخل مودال “Edit”.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TOTALS / PAYMENT --}}
                <div class="col-12">
                    <div class="sticky">
                        <div class="pos-card">
                            <div class="pos-card-hd">
                                <div class="fw-bold">الإجماليات والدفع</div>
                                <span class="badge-soft">Auto</span>
                            </div>

                            <div class="pos-card-bd">
                                <div class="row g-2">
                                    <div class="col-12">
                                        <div class="kpi">
                                            <div class="label">Subtotal (قبل الخصومات والضريبة)</div>
                                            <div class="value"><span class="money" id="subtotal_view">0.00</span></div>
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label">خصم عام</label>
                                        <select id="global_discount_type" class="form-select" style="border-radius:14px;height:40px;">
                                            <option value="amount" @selected($defaultGlobalDiscType==='amount')>قيمة</option>
                                            <option value="percent" @selected($defaultGlobalDiscType==='percent')>نسبة %</option>
                                        </select>
                                        <div class="hint">قيمة أو نسبة على إجمالي الفاتورة.</div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Value</label>
                                        <input type="number" step="0.0001" min="0" id="global_discount_value" class="form-control"
                                               value="{{ $defaultGlobalDiscValue }}" style="border-radius:14px;height:40px;">
                                        <div class="hint">لو نسبة: اكتب 10 = 10%.</div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">VAT% افتراضي</label>
                                        <input type="number" step="0.0001" min="0" id="global_vat_rate" class="form-control"
                                               value="{{ $defaultVatRate }}" style="border-radius:14px;height:40px;">
                                        <div class="hint">يُستخدم كافتراضي للسطور الجديدة/لو السطر VAT=0.</div>
                                    </div>

                                    <div class="col-6">
                                        <div class="kpi">
                                            <div class="label">إجمالي الخصومات</div>
                                            <div class="value text-danger"><span class="money" id="discount_view">0.00</span></div>
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="kpi">
                                            <div class="label">إجمالي الضريبة</div>
                                            <div class="value text-primary"><span class="money" id="vat_view">0.00</span></div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="kpi">
                                            <div class="label">الإجمالي النهائي</div>
                                            <div class="value"><span class="money" id="total_view">0.00</span></div>
                                        </div>
                                    </div>

                                    <div class="col-12 d-grid gap-2">
                                        <button type="button" id="openPayModal" class="btn btn-primary pos-btn">
                                            <i class="ti ti-credit-card"></i> شاشة الدفع (Split) — F9
                                        </button>
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label">المدفوع</label>
                                        <input type="number" step="0.0001" min="0" name="paid_amount" id="paid_amount"
                                               class="form-control" value="{{ old('paid_amount', 0) }}"
                                               style="border-radius:14px;height:40px;" readonly>
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label">المتبقي</label>
                                        <input type="number" step="0.0001" min="0" id="remaining_amount_view"
                                               class="form-control" value="0" readonly style="border-radius:14px;height:40px;">
                                    </div>

                                    <div class="col-12 d-grid gap-2 mt-1">
                                        <button type="submit" class="btn btn-dark pos-btn" id="submitBtn">
                                            <i class="ti ti-device-floppy"></i> حفظ الفاتورة
                                        </button>

                                        <a href="{{ route('sales_invoices.index') }}" class="btn btn-light pos-btn">
                                            إلغاء
                                        </a>
                                    </div>

                                    @if(!$openShiftTreasuryId)
                                        <div class="alert alert-danger mt-2 mb-0">
                                            <div class="fw-bold">ممنوع الدفع بدون شِفت</div>
                                            <div class="small">افتح شِفت على خزنة أولاً.</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>

    {{-- مودال تعديل السطر (سعر + خصم + VAT) --}}
    <div class="modal fade" id="lineEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content" style="border-radius:18px; overflow:hidden;">
                <div class="modal-header modal-soft-hd">
                    <div>
                        <h5 class="modal-title mb-0">تعديل السطر</h5>
                        <div class="small" id="lem_item_name" style="opacity:.9;">—</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">السعر</label>
                            <input type="number" min="0" step="0.0001" id="lem_price" class="form-control" style="border-radius:14px;height:40px;">
                            <div class="hint">items[i][price]</div>
                        </div>

                        <div class="col-8">
                            <label class="form-label">قيمة الخصم</label>
                            <input type="number" min="0" step="0.0001" id="lem_disc_value" class="form-control" style="border-radius:14px;height:40px;">
                            <div class="hint">items[i][discount_value]</div>
                        </div>

                        <div class="col-4">
                            <label class="form-label">نوع الخصم</label>
                            <select id="lem_disc_type" class="form-select" style="border-radius:14px;height:40px;">
                                <option value="amount">ج</option>
                                <option value="percent">%</option>
                            </select>
                            <div class="hint">items[i][discount_type]</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">VAT%</label>
                            <input type="number" min="0" step="0.0001" id="lem_vat_rate" class="form-control" style="border-radius:14px;height:40px;">
                            <div class="hint">items[i][vat_rate]</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label required">سعر التكلفة</label>
                            <input type="number" min="0" step="0.0001" id="lem_cost_price" class="form-control" style="border-radius:14px;height:40px;" required>
                            <div class="hint">items[i][cost_price]</div>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                <div class="fw-bold">ثابت</div>
                                <div class="small">كل التعديل هنا فقط… السطر نفسه مفيهوش حقول سعر/خصم/VAT عشان العناوين متتحركش.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light pos-btn" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary pos-btn" id="lem_apply">
                        <i class="ti ti-check"></i> تطبيق
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Pay Modal (Split Payment) --}}
    <div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius:18px; overflow:hidden;">
                <div class="modal-header" style="background:linear-gradient(135deg, rgba(11,94,215,.95), rgba(11,94,215,.70)); color:#fff;">
                    <h5 class="modal-title mb-0">شاشة الدفع — Split Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="kpi">
                                <div class="label">الإجمالي</div>
                                <div class="value"><span class="money" id="pm_total">0.00</span></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="kpi">
                                <div class="label">المتبقي</div>
                                <div class="value text-danger"><span class="money" id="pm_remaining">0.00</span></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="kpi">
                                <div class="label">المجموع المدفوع</div>
                                <div class="value"><span class="money" id="pm_paid_sum">0.00</span></div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">كاش</label>
                            <input type="number" step="0.01" min="0" id="pm_cash" class="form-control" style="border-radius:14px;height:40px;" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">فيزا/بطاقة</label>
                            <input type="number" step="0.01" min="0" id="pm_card" class="form-control" style="border-radius:14px;height:40px;" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">محفظة</label>
                            <input type="number" step="0.01" min="0" id="pm_wallet" class="form-control" style="border-radius:14px;height:40px;" value="0">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label required">الخزنة (من الشِفت المفتوح)</label>
                            <select id="pm_treasury" class="form-select" style="border-radius:14px;height:40px;"
                                    @if($openShiftTreasuryId) disabled @endif>
                                <option value="">اختر خزنة</option>
                                @foreach($treasuries as $t)
                                    <option value="{{ $t->id }}" @selected((string)old('payment.treasury_id', $openShiftTreasuryId ?? '') === (string)$t->id)>
                                        {{ $t->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="hint">
                                @if($openShiftTreasuryId)
                                    تم قفل الخزنة على خزنة الشِفت المفتوح.
                                @else
                                    إجباري عند وجود مدفوعات.
                                @endif
                            </div>
                        </div>

                        <div class="col-12 d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-light pos-btn" id="pm_auto_cash">
                                <i class="ti ti-cash"></i> دفع كله كاش
                            </button>
                            <button type="button" class="btn btn-light pos-btn" id="pm_zero">
                                <i class="ti ti-refresh"></i> تصفير
                            </button>
                            <div class="ms-auto"></div>
                            <button type="button" class="btn btn-primary pos-btn" id="pm_apply">
                                <i class="ti ti-check"></i> تطبيق
                            </button>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-warning mt-2 mb-0" id="pm_warn" style="display:none;">
                                <div class="fw-bold">تنبيه</div>
                                <div class="small" id="pm_warn_text"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <div class="hint me-auto">لو الدفع آجل: ممكن تسيب المدفوع 0 وتطبق.</div>
                    <button type="button" class="btn btn-light pos-btn" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Park Modal --}}
    <div class="modal fade" id="parkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius:18px; overflow:hidden;">
                <div class="modal-header" style="background:linear-gradient(135deg, rgba(15,23,42,.95), rgba(15,23,42,.75)); color:#fff;">
                    <h5 class="modal-title mb-0">الفواتير المعلقة (Hold/Park)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="d-flex gap-2 mb-2">
                        <button type="button" id="parkRefresh" class="btn btn-light pos-btn">
                            <i class="ti ti-refresh"></i> تحديث
                        </button>
                        <button type="button" id="parkClearAll" class="btn btn-outline-danger pos-btn">
                            <i class="ti ti-trash"></i> مسح الكل
                        </button>
                        <div class="ms-auto"></div>
                        <div class="hint d-flex align-items-center">اضغط على أي فاتورة لاسترجاعها</div>
                    </div>

                    <div class="park-list" id="parkList"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light pos-btn" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-left",
            timeOut: "2200"
        };

        const SHIFT_TREASURY_ID = "{{ old('payment.treasury_id', $openShiftTreasuryId ?? '') }}";

        function fmt(n){
            n = Number(n || 0);
            return n.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
        function num(v){
            v = (v ?? '').toString().replace(/,/g,'').trim();
            const n = parseFloat(v);
            return isNaN(n) ? 0 : n;
        }
        function normalizeSelect2Response(data){
            if (!data) return { results: [], pagination: { more: false } };
            if (Array.isArray(data)) return { results: data, pagination: { more: false } };
            if (Array.isArray(data.results)) return { results: data.results, pagination: data.pagination || { more: false } };
            if (Array.isArray(data.data)) return { results: data.data, pagination: data.pagination || { more: false } };
            return { results: [], pagination: { more: false } };
        }

        function reindexLines(){
            $('#cartBody .cart-row').each(function(idx){
                $(this).find('[data-name]').each(function(){
                    const base = $(this).data('name');
                    $(this).attr('name', base.replace('__i__', idx));
                });
            });
        }

        function currentWarehouseId(){ return $('#warehouse_id').val() || ''; }
        function preventNegativeStockEnabled(){ return String($('#prevent_negative_stock').val() || '0') === '1'; }

        function computeFreeQty(qty, offerBuy, offerGet){
            qty = Math.max(0, num(qty));
            offerBuy = Math.max(0, num(offerBuy));
            offerGet = Math.max(0, num(offerGet));
            if (!offerBuy || !offerGet) return 0;
            return Math.floor(qty / offerBuy) * offerGet;
        }

        function syncLineMetaViews($row){
            const p = Math.max(0, num($row.find('.price').val()));
            const dv = Math.max(0, num($row.find('.discount_value').val()));
            const dt = ($row.find('.discount_type').val() || 'amount');
            const vr = Math.max(0, num($row.find('.vat_rate').val()));

            $row.find('.price_view').text(fmt(p));
            $row.find('.vat_view').text(vr.toFixed(2).replace(/\.00$/,''));

            let discTxt = fmt(dv) + (dt === 'percent' ? ' %' : ' ج');
            $row.find('.disc_view').text(discTxt);
        }

        function itemTemplate(item){
            if (!item || !item.id) return item.text || '';
            const code = item.code ? `#${item.code}` : '';
            const bc   = item.barcode ? `${item.barcode}` : '';
            const stock = (item.stock != null && item.stock !== '') ? `متاح: ${fmt(item.stock)}` : '';
            const rl    = (item.reorder_level != null && item.reorder_level !== '') ? `حد إعادة الطلب: ${fmt(item.reorder_level)}` : '';

            const price = (item.price_for_customer ?? item.price_for_category ?? item.price ?? item.sale_price);
            const pTxt = (price != null && price !== '') ? `${fmt(price)}` : '';
            return $(`
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px;">
                    <div style="min-width:0;">
                        <div style="font-weight:950; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${item.text || ''}</div>
                        <div style="font-size:12px; color:#64748b; margin-top:2px;">
                            ${[code, bc, stock, rl].filter(Boolean).join(' — ')}
                        </div>
                    </div>
                    <div class="money" style="font-weight:950; color:#0b5ed7;">${pTxt}</div>
                </div>
            `);
        }

        function initCustomer(){
            const $customer = $('#customer_id');
            $customer.select2({
                theme:'bootstrap-5', width:'100%', dir:'rtl',
                allowClear:true,
                placeholder: $customer.data('placeholder') || 'ابحث...',
                dropdownParent: $('body'),
                ajax:{
                    url: "{{ route('customers.select2') }}",
                    dataType:'json', delay:300,
                    data: (params)=>({ q: params.term || '', page: params.page || 1 }),
                    processResults: (data)=> normalizeSelect2Response(data),
                    cache:true
                },
                minimumInputLength: 1
            });

            $customer.on('select2:select', function(e){
                const d = e.params.data || {};
                $('#customer_text').val(d.text || '');
                $('#pricing_customer_category_id').val(d.customer_category_id || '');
                $('#pricing_price_list_id').val(d.price_list_id || '');
            });

            $customer.on('select2:clear', function(){
                $('#customer_text').val('');
                $('#pricing_customer_category_id').val('');
                $('#pricing_price_list_id').val('');
            });
        }

        function initItemSelect2($el){
            $el.select2({
                theme:'bootstrap-5', width:'100%', dir:'rtl',
                allowClear:true,
                placeholder: $el.data('placeholder') || 'اختر صنف...',
                dropdownParent: $('body'),
                templateResult: itemTemplate,
                templateSelection: (item)=> item?.text || '',
                ajax:{
                    url: "{{ route('items.select2') }}",
                    dataType:'json', delay:250,
                    data: function(params){
                        return {
                            q: params.term || '',
                            page: params.page || 1,
                            warehouse_id: currentWarehouseId(),
                            customer_id: $('#customer_id').val() || ''
                        };
                    },
                    processResults: (data)=> normalizeSelect2Response(data),
                    cache:true,
                    error: ()=> toastr.error('مشكلة في تحميل الأصناف. راجع Route/JSON.')
                },
                minimumInputLength: 1
            });

            $el.on('select2:select', function(e){
                const data = e.params.data || {};
                const $row = $(this).closest('.cart-row');

                $row.find('input[name$="[item_text]"]').val(data.text || '');
                $row.find('.name').text(data.text || 'صنف');

                const subParts = [];
                if (data.code) subParts.push('#'+data.code);
                if (data.barcode) subParts.push(data.barcode);
                if (subParts.length) {
                    $row.find('.sub').text(subParts.join(' — '));
                }
// لو مفيش بيانات، سيب العنوان زي ما هو (اسم/كود/باركود)

                // Stock + reorder
                const stock = (data.stock != null && data.stock !== '') ? Number(data.stock) : null;
                const reorderLevel = (data.reorder_level != null && data.reorder_level !== '') ? Number(data.reorder_level) : null;

                if (stock != null && !isNaN(stock)) {
                    $row.find('.stock_val').val(stock);
                    $row.find('.stock_view').text(fmt(stock));
                    $row.find('.stock-pill').removeClass('d-none low warn');
                } else {
                    $row.find('.stock_val').val('');
                    $row.find('.stock-pill').addClass('d-none').removeClass('low warn');
                }

                if (reorderLevel != null && !isNaN(reorderLevel)) $row.find('.reorder_level_val').val(reorderLevel);
                else $row.find('.reorder_level_val').val('');

                // Price default (BUT still hidden only)
                const p = (data.price_for_customer ?? data.price_for_category ?? data.price ?? data.sale_price);
                if (p != null && p !== '') $row.find('.price').val(Number(p));

                // VAT default: لو السطر صفر
                const vatDefault = Math.max(0, num($('#global_vat_rate').val()));
                const currentVat = num($row.find('.vat_rate').val());
                if (!currentVat && vatDefault) $row.find('.vat_rate').val(vatDefault);

                // Offer
                $row.data('offer_buy', data.offer_buy || 0);
                $row.data('offer_get', data.offer_get || 0);

                // Merge same item
                const id = String(data.id || '');
                if (id) {
                    let foundSame = null;
                    $('#cartBody .cart-row').each(function(){
                        if (this === $row[0]) return;
                        const otherId = $(this).find('.item-select').val();
                        if (String(otherId) === id) foundSame = $(this);
                    });

                    if (foundSame) {
                        const q1 = num(foundSame.find('.qty').val());
                        const q2 = num($row.find('.qty').val());
                        foundSame.find('.qty').val((q1 + (q2 || 1)).toFixed(4));
                        $row.remove();
                        reindexLines();
                        calcAll();
                        toastr.success('تم دمج الصنف وزيادة الكمية.');
                        return;
                    }
                }

                syncLineMetaViews($row);
                calcAll();
            });

            $el.on('select2:clear', function(){
                const $row = $(this).closest('.cart-row');
                $row.find('input[name$="[item_text]"]').val('');
                $row.find('.name').text('اختر صنف');
                $row.find('.sub').text('اسم/كود/باركود');

                // reset hidden values
                $row.find('.price').val(0);
                $row.find('.discount_value').val(0);
                $row.find('.discount_type').val('amount');
                $row.find('.vat_rate').val(Math.max(0, num($('#global_vat_rate').val())) || 0);

                $row.find('.free_qty').val(0);
                $row.find('.offer_view').addClass('d-none');
                $row.data('offer_buy', 0);
                $row.data('offer_get', 0);

                $row.find('.stock_val').val('');
                $row.find('.reorder_level_val').val('');
                $row.find('.stock-pill').addClass('d-none').removeClass('low warn');

                syncLineMetaViews($row);
                calcAll();
            });
        }

        function lineHtml(){
            const vatDefault = Math.max(0, num($('#global_vat_rate').val())) || 0;
            return `
            <tr class="cart-row">
                <td style="width:52%;">
                    <div class="name">اختر صنف</div>
                    <div class="sub">اسم/كود/باركود</div>

                    <select class="form-select item-select mt-2"
                            data-placeholder="ابحث عن صنف..."
                            data-name="items[__i__][item_id]">
                        <option value=""></option>
                    </select>

                    <input type="hidden" data-name="items[__i__][item_text]" value="">
                    <input type="hidden" class="stock_val" data-name="items[__i__][stock]" value="">
                    <input type="hidden" class="reorder_level_val" data-name="items[__i__][reorder_level]" value="">
                    <input type="hidden" class="free_qty" data-name="items[__i__][free_qty]" value="0">

                    <input type="hidden" class="price" value="0" data-name="items[__i__][price]">
                    <input type="hidden" class="discount_value" value="0" data-name="items[__i__][discount_value]">
                    <input type="hidden" class="discount_type" value="amount" data-name="items[__i__][discount_type]">
                    <input type="hidden" class="vat_rate" value="${vatDefault}" data-name="items[__i__][vat_rate]">
                    <input type="hidden" class="cost_price" value="0" data-name="items[__i__][cost_price]">

                    <div class="stock-pill d-none">
                        <i class="ti ti-box"></i>
                        <span>متاح:</span>
                        <span class="money stock_view">0</span>
                    </div>

                    <div class="hint mt-2">
                        <span class="offer-pill d-none offer_view"><i class="ti ti-gift"></i> هدية: <span class="money free_view">0</span></span>
                    </div>

                    <div class="line-meta">
                        <span class="line-chip">السعر <span class="muted money price_view">0.00</span></span>
                        <span class="line-chip">الخصم <span class="muted disc_view">0.00 ج</span></span>
                        <span class="line-chip">VAT% <span class="muted money vat_view">0</span></span>
                    </div>
                </td>

                <td style="width:24%;">
                    <div class="sub mb-1">كمية</div>
                    <div class="qty-wrap">
                        <button type="button" class="btn btn-light btn-icon qty-minus"><i class="ti ti-minus"></i></button>
                        <input type="number" min="0" step="0.0001" class="w-100 qty" value="1" data-name="items[__i__][qty]">
                        <button type="button" class="btn btn-light btn-icon qty-plus"><i class="ti ti-plus"></i></button>
                    </div>
                    <div class="hint mt-1">لو الكمية > المخزون هيطلع تحذير/منع.</div>
                </td>

                <td style="width:24%;">
                    <div class="action-col">
                        <div>
                            <div class="sub mb-1">الصافي</div>
                            <div class="money fw-bold line_total_view">0.00</div>
                            <input type="hidden" class="line_total" value="0" data-name="items[__i__][line_total]">
                        </div>

                        <div class="action-btns">
                            <button type="button" class="btn btn-outline-primary btn-icon edit-line" title="تعديل السعر/الخصم/VAT">
                                <i class="ti ti-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-icon remove-line" title="حذف">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                    </div>
                </td>
            </tr>`;
        }

        function calcLine($row){
            const qty   = Math.max(0, num($row.find('.qty').val()));
            const price = Math.max(0, num($row.find('.price').val()));
            const gross = qty * price;

            const discVal = Math.max(0, num($row.find('.discount_value').val()));
            const discType = ($row.find('.discount_type').val() || 'amount');

            let disc = 0;
            if (discType === 'percent') disc = gross * Math.min(100, discVal) / 100;
            else disc = discVal;

            disc = Math.min(disc, gross);
            const base = Math.max(0, gross - disc);

            const vatRate = Math.max(0, num($row.find('.vat_rate').val()));
            const vat = base * (vatRate / 100);
            const net = base + vat;

            const offerBuy = Math.max(0, num($row.data('offer_buy') || 0));
            const offerGet = Math.max(0, num($row.data('offer_get') || 0));
            const freeQty = computeFreeQty(qty, offerBuy, offerGet);

            $row.find('.free_qty').val(freeQty);
            if (freeQty > 0) {
                $row.find('.offer_view').removeClass('d-none');
                $row.find('.free_view').text(fmt(freeQty));
            } else {
                $row.find('.offer_view').addClass('d-none');
            }

            const stockRaw = $row.find('.stock_val').val();
            const reorderRaw = $row.find('.reorder_level_val').val();
            const $pill = $row.find('.stock-pill');
            let neg = false;

            if (stockRaw !== '' && stockRaw != null) {
                const stock = Number(stockRaw);
                if (!isNaN(stock)) {
                    $pill.removeClass('d-none');
                    $row.find('.stock_view').text(fmt(stock));

                    if (qty > stock) {
                        $pill.addClass('low').removeClass('warn');
                        neg = true;
                    } else {
                        $pill.removeClass('low');
                        const rl = Number(reorderRaw);
                        if (!isNaN(rl) && rl > 0 && stock <= rl) $pill.addClass('warn');
                        else $pill.removeClass('warn');
                    }
                }
            }

            return { gross, disc, vat, net, neg };
        }

        function calcAll(){
            let subtotal = 0;
            let discLines = 0;
            let vatTotal = 0;
            let netLines = 0;
            let hasNegative = false;

            $('#cartBody .cart-row').each(function(){
                const $r = $(this);
                syncLineMetaViews($r);

                const r = calcLine($r);
                subtotal += r.gross;
                discLines += r.disc;
                vatTotal += r.vat;
                netLines += r.net;
                if (r.neg) hasNegative = true;

                $r.find('.line_total').val(r.net.toFixed(4));
                $r.find('.line_total_view').text(fmt(r.net));
            });

            const gdType = ($('#global_discount_type').val() || 'amount');
            const gdVal  = Math.max(0, num($('#global_discount_value').val()));

            let globalDiscApplied = 0;
            if (gdType === 'percent') globalDiscApplied = netLines * Math.min(100, gdVal) / 100;
            else globalDiscApplied = gdVal;

            globalDiscApplied = Math.min(globalDiscApplied, netLines);

            $('#global_discount_type_input').val(gdType);
            $('#global_discount_value_input').val(gdVal);

            const total = Math.max(0, netLines - globalDiscApplied);

            const paid = Math.max(0, num($('#pay_cash').val()) + num($('#pay_card').val()) + num($('#pay_wallet').val()));
            const remaining = Math.max(0, total - paid);

            $('#subtotal_view').text(fmt(subtotal));
            $('#discount_view').text(fmt(discLines + globalDiscApplied));
            $('#vat_view').text(fmt(vatTotal));
            $('#total_view').text(fmt(total));
            $('#paid_amount').val(paid.toFixed(2));
            $('#remaining_amount_view').val(remaining.toFixed(2));

            $('#subtotal_input').val(subtotal.toFixed(4));
            $('#discount_input').val((discLines + globalDiscApplied).toFixed(4));
            $('#vat_input').val(vatTotal.toFixed(4));
            $('#total_input').val(total.toFixed(4));
            $('#remaining_input').val(remaining.toFixed(4));

            if (hasNegative) {
                if (preventNegativeStockEnabled()) {
                    $('#submitBtn, #topSubmitBtn').prop('disabled', true);
                    $('#negStockBadge').text('منع السالب (مقفول)');
                } else {
                    $('#submitBtn, #topSubmitBtn').prop('disabled', false);
                    $('#negStockBadge').text('تحذير فقط');
                }
            } else {
                $('#submitBtn, #topSubmitBtn').prop('disabled', false);
                $('#negStockBadge').text(preventNegativeStockEnabled() ? 'مفعل' : 'تحذير فقط');
            }
        }

        // ---------- Park/Hold ----------
        const PARK_KEY = 'pos_park_sales_v1';
        function readPark(){ try{ return JSON.parse(localStorage.getItem(PARK_KEY) || '[]'); }catch(e){ return []; } }
        function writePark(list){ localStorage.setItem(PARK_KEY, JSON.stringify(list || [])); }

        function collectCurrentSale(){
            const rows = [];
            $('#cartBody .cart-row').each(function(){
                const $row = $(this);
                const item_id = $row.find('.item-select').val();
                if (!item_id) return;
                rows.push({
                    item_id: item_id,
                    item_text: $row.find('input[name$="[item_text]"]').val() || '',
                    qty: num($row.find('.qty').val()),
                    price: num($row.find('.price').val()),
                    discount_value: num($row.find('.discount_value').val()),
                    discount_type: $row.find('.discount_type').val() || 'amount',
                    vat_rate: num($row.find('.vat_rate').val()),
                    stock: $row.find('.stock_val').val(),
                    reorder_level: $row.find('.reorder_level_val').val(),
                    offer_buy: num($row.data('offer_buy') || 0),
                    offer_get: num($row.data('offer_get') || 0),
                });
            });

            return {
                id: 'P' + Date.now(),
                created_at: new Date().toISOString(),
                invoice_date: $('input[name="invoice_date"]').val() || '',
                due_date: $('#due_date').val() || '',
                payment_type: $('#payment_type').val() || 'cash',
                warehouse_id: $('#warehouse_id').val() || '',
                customer_id: $('#customer_id').val() || '',
                customer_text: $('#customer_text').val() || '',
                notes: $('textarea[name="notes"]').val() || '',
                global_discount_type: $('#global_discount_type').val() || 'amount',
                global_discount_value: num($('#global_discount_value').val()),
                global_vat_rate: num($('#global_vat_rate').val()),
                payment: {
                    cash: num($('#pay_cash').val()),
                    card: num($('#pay_card').val()),
                    wallet: num($('#pay_wallet').val()),
                    treasury_id: $('#pay_treasury_id').val() || '',
                    terminal_id: $('#pay_terminal_id').val() || '',
                },
                rows: rows
            };
        }

        function loadSaleToUI(sale){
            $('input[name="invoice_date"]').val(sale.invoice_date || '');
            $('#due_date').val(sale.due_date || '');
            $('#payment_type').val(sale.payment_type || 'cash').trigger('change');
            $('#warehouse_id').val(sale.warehouse_id || '').trigger('change');

            if (sale.customer_id) {
                const opt = new Option(sale.customer_text || 'Customer', String(sale.customer_id), true, true);
                $('#customer_id').append(opt).trigger('change');
                $('#customer_text').val(sale.customer_text || '');
            } else {
                $('#customer_id').val(null).trigger('change');
                $('#customer_text').val('');
            }

            $('textarea[name="notes"]').val(sale.notes || '');
            $('#global_discount_type').val(sale.global_discount_type || 'amount');
            $('#global_discount_value').val(num(sale.global_discount_value || 0));
            $('#global_vat_rate').val(num(sale.global_vat_rate || 0));

            $('#pay_cash').val(num(sale.payment?.cash || 0).toFixed(2));
            $('#pay_card').val(num(sale.payment?.card || 0).toFixed(2));
            $('#pay_wallet').val(num(sale.payment?.wallet || 0).toFixed(2));

            const tId = SHIFT_TREASURY_ID || (sale.payment?.treasury_id || '');
            $('#pay_treasury_id').val(tId);
            $('#pay_terminal_id').val(sale.payment?.terminal_id || '');

            $('#cartBody').html('');

            if (sale.rows && sale.rows.length) {
                sale.rows.forEach(r => {
                    $('#cartBody').append(lineHtml());
                    reindexLines();

                    const $row = $('#cartBody .cart-row:last');
                    const $sel = $row.find('.item-select');
                    initItemSelect2($sel);

                    const opt = new Option(r.item_text || 'Item', String(r.item_id), true, true);
                    $sel.append(opt).trigger('change');

                    $row.find('input[name$="[item_text]"]').val(r.item_text || '');
                    $row.find('.qty').val(Number(r.qty || 1).toFixed(4));

                    $row.find('.price').val(Number(r.price || 0).toFixed(4));
                    $row.find('.discount_value').val(Number(r.discount_value || 0).toFixed(4));
                    $row.find('.discount_type').val(r.discount_type || 'amount');
                    $row.find('.vat_rate').val(Number(r.vat_rate || 0).toFixed(4));

                    if (r.stock !== '' && r.stock != null) {
                        $row.find('.stock_val').val(r.stock);
                        $row.find('.stock_view').text(fmt(r.stock));
                        $row.find('.stock-pill').removeClass('d-none');
                    }
                    if (r.reorder_level !== '' && r.reorder_level != null) $row.find('.reorder_level_val').val(r.reorder_level);

                    $row.data('offer_buy', num(r.offer_buy || 0));
                    $row.data('offer_get', num(r.offer_get || 0));

                    syncLineMetaViews($row);
                });
            } else {
                $('#cartBody').html(lineHtml());
                reindexLines();
                initItemSelect2($('#cartBody .cart-row:first .item-select'));
            }

            calcAll();
            toastr.success('تم استرجاع الفاتورة المعلقة.');
        }

        function renderParkList(){
            const list = readPark().sort((a,b)=> (b.created_at||'').localeCompare(a.created_at||''));
            const $wrap = $('#parkList');
            $wrap.html('');

            if (!list.length){
                $wrap.html(`<div class="p-3 text-center hint">لا توجد فواتير معلقة.</div>`);
                return;
            }

            list.forEach(s => {
                const total = (s.rows||[]).reduce((acc, r)=> acc + (num(r.qty)*num(r.price)), 0);
                const customer = s.customer_text || 'بدون عميل';
                const wh = s.warehouse_id ? ('مخزن: '+ s.warehouse_id) : 'بدون مخزن';
                const lines = (s.rows||[]).length;
                $wrap.append(`
                    <div class="park-item" data-id="${s.id}">
                        <div style="min-width:0;">
                            <div style="font-weight:950; color:#0f172a;">${customer}</div>
                            <div class="hint">${wh} — سطور: ${lines} — ${new Date(s.created_at).toLocaleString()}</div>
                        </div>
                        <div class="money" style="font-weight:950; color:#0b5ed7;">${fmt(total)}</div>
                    </div>
                `);
            });
        }

        // ---------- Payment modal ----------
        function pmUpdate(){
            const total = Math.max(0, num($('#total_input').val()));
            const cash = Math.max(0, num($('#pm_cash').val()));
            const card = Math.max(0, num($('#pm_card').val()));
            const wallet = Math.max(0, num($('#pm_wallet').val()));
            const sum = cash + card + wallet;
            const remaining = Math.max(0, total - sum);

            $('#pm_total').text(fmt(total));
            $('#pm_paid_sum').text(fmt(sum));
            $('#pm_remaining').text(fmt(remaining));

            $('#pm_warn').hide();
            $('#pm_warn_text').text('');

            const payType = $('#payment_type').val();
            if (payType === 'cash' && remaining > 0.01) {
                $('#pm_warn').show();
                $('#pm_warn_text').text('نوع الدفع كاش، المفروض المدفوع يساوي الإجمالي (أو غيّر الدفع لآجل).');
            }
            if (sum > total + 0.01) {
                $('#pm_warn').show();
                $('#pm_warn_text').text('المجموع المدفوع أكبر من الإجمالي.');
            }
        }

        function applyPaymentFromModal(){
            const total = Math.max(0, num($('#total_input').val()));
            const cash = Math.max(0, num($('#pm_cash').val()));
            const card = Math.max(0, num($('#pm_card').val()));
            const wallet = Math.max(0, num($('#pm_wallet').val()));
            const sum = cash + card + wallet;

            const treasury = SHIFT_TREASURY_ID || ($('#pm_treasury').val() || '');
            const terminal = $('#pm_terminal').val() || '';

            const payType = $('#payment_type').val();

            if (!treasury && sum > 0.01) { toastr.error('لا توجد خزنة (لا يوجد شِفت مفتوح). افتح شِفت أولاً.'); return false; }
            if (sum > total + 0.01) { toastr.error('المجموع المدفوع أكبر من الإجمالي.'); return false; }
            if (payType === 'cash' && Math.abs(sum - total) > 0.01) { toastr.error('الدفع كاش: لازم المدفوع يساوي الإجمالي (أو غيّر الدفع لآجل).'); return false; }

            $('#pay_cash').val(cash.toFixed(2));
            $('#pay_card').val(card.toFixed(2));
            $('#pay_wallet').val(wallet.toFixed(2));
            $('#pay_treasury_id').val(treasury);
            $('#pay_terminal_id').val(terminal);

            calcAll();
            toastr.success('تم تطبيق بيانات الدفع.');
            return true;
        }

        function openPayModal(){
            $('#pm_cash').val(num($('#pay_cash').val()).toFixed(2));
            $('#pm_card').val(num($('#pay_card').val()).toFixed(2));
            $('#pm_wallet').val(num($('#pay_wallet').val()).toFixed(2));

            const t = SHIFT_TREASURY_ID || ($('#pay_treasury_id').val() || '');
            $('#pm_treasury').val(t);

            $('#pm_terminal').val($('#pay_terminal_id').val() || '');

            pmUpdate();
            const modalEl = document.getElementById('payModal');
            new bootstrap.Modal(modalEl).show();
            setTimeout(()=> $('#pm_cash').focus(), 250);
        }

        // ---------- Barcode ----------
        async function fetchFirstItemByQuery(q){
            try{
                const res = await $.get("{{ route('items.select2') }}", {
                    q:q, page:1,
                    warehouse_id: currentWarehouseId(),
                    customer_id: $('#customer_id').val() || ''
                });
                const normalized = normalizeSelect2Response(res);
                return (normalized.results && normalized.results.length) ? normalized.results[0] : null;
            }catch(e){ return null; }
        }

        function addToCartByData(id, text, price, qty, stock, offerBuy, offerGet, reorderLevel){
            qty = Math.max(1, num(qty || 1));

            let found = null;
            $('#cartBody .cart-row').each(function(){
                if (String($(this).find('.item-select').val()) === String(id)) found = $(this);
            });

            if (found) {
                const oldQ = num(found.find('.qty').val());
                found.find('.qty').val((oldQ + qty).toFixed(4));
                if (offerBuy) found.data('offer_buy', offerBuy);
                if (offerGet) found.data('offer_get', offerGet);
                if (reorderLevel != null) found.find('.reorder_level_val').val(reorderLevel);
                calcAll();
                toastr.success('تمت زيادة الكمية في السلة.');
                return;
            }

            $('#cartBody').append(lineHtml());
            reindexLines();

            const $row = $('#cartBody .cart-row:last');
            const $sel = $row.find('.item-select');
            initItemSelect2($sel);

            const opt = new Option(text || 'Item', String(id), true, true);
            $sel.append(opt).trigger('change');

            $row.find('.qty').val(qty.toFixed(4));
            if (price != null && price !== '') $row.find('.price').val(Number(price));

            if (stock != null && stock !== '' && !isNaN(Number(stock))) {
                $row.find('.stock_val').val(Number(stock));
                $row.find('.stock_view').text(fmt(stock));
                $row.find('.stock-pill').removeClass('d-none');
            }
            if (reorderLevel != null && reorderLevel !== '' && !isNaN(Number(reorderLevel))) {
                $row.find('.reorder_level_val').val(Number(reorderLevel));
            }

            $row.data('offer_buy', offerBuy || 0);
            $row.data('offer_get', offerGet || 0);

            syncLineMetaViews($row);
            calcAll();
        }

        async function handleBarcode(){
            const q = ($('#barcodeInput').val() || '').trim();
            const qty = Math.max(1, num($('#barcodeQty').val()));
            if (!q) return;

            const first = await fetchFirstItemByQuery(q);
            if (!first || !first.id){ toastr.warning('لم يتم العثور على صنف بهذا الباركود/الكود.'); return; }

            const price = (first.price_for_customer ?? first.price_for_category ?? first.price ?? first.sale_price);
            addToCartByData(first.id, first.text, price, qty, first.stock, first.offer_buy, first.offer_get, first.reorder_level);

            $('#barcodeInput').val('').focus();
            $('#barcodeQty').val(1);
        }

        // ---------- Line Edit Modal ----------
        let CURRENT_EDIT_ROW = null;

        function openLineEditModal($row){
            CURRENT_EDIT_ROW = $row;

            const itemId = $row.find('.item-select').val();
            if (!itemId){ toastr.info('اختر صنف أولاً قبل التعديل.'); return; }

            const itemName = ($row.find('input[name$="[item_text]"]').val() || $row.find('.name').text() || '—');
            $('#lem_item_name').text(itemName);

            $('#lem_price').val(num($row.find('.price').val()).toFixed(4));
            $('#lem_disc_value').val(num($row.find('.discount_value').val()).toFixed(4));
            $('#lem_disc_type').val($row.find('.discount_type').val() || 'amount');
            $('#lem_vat_rate').val(num($row.find('.vat_rate').val()).toFixed(4));
            $('#lem_cost_price').val(num($row.find('.cost_price').val()).toFixed(4));

            const modalEl = document.getElementById('lineEditModal');
            new bootstrap.Modal(modalEl).show();
            setTimeout(()=> $('#lem_price').focus().select(), 200);
        }

        function applyLineEditModal(){
            if (!CURRENT_EDIT_ROW) return;

            const cp = Math.max(0, num($('#lem_cost_price').val()));

            // التحقق من أن cost_price مطلوب
            if (cp <= 0) {
                toastr.error('سعر التكلفة مطلوب ويجب أن يكون أكبر من صفر.');
                return;
            }

            const p  = Math.max(0, num($('#lem_price').val()));
            const dv = Math.max(0, num($('#lem_disc_value').val()));
            const dt = ($('#lem_disc_type').val() || 'amount');
            const vr = Math.max(0, num($('#lem_vat_rate').val()));

            CURRENT_EDIT_ROW.find('.price').val(p.toFixed(4));
            CURRENT_EDIT_ROW.find('.discount_value').val(dv.toFixed(4));
            CURRENT_EDIT_ROW.find('.discount_type').val(dt);
            CURRENT_EDIT_ROW.find('.vat_rate').val(vr.toFixed(4));
            CURRENT_EDIT_ROW.find('.cost_price').val(cp.toFixed(4));

            syncLineMetaViews(CURRENT_EDIT_ROW);
            calcAll();
            toastr.success('تم تعديل السطر.');

            const modalEl = document.getElementById('lineEditModal');
            bootstrap.Modal.getInstance(modalEl)?.hide();
        }

        // ---------- init ----------
        $(function(){
            initCustomer();

            initItemSelect2($('#cartBody .cart-row:first .item-select'));
            reindexLines();

            if (SHIFT_TREASURY_ID) $('#pay_treasury_id').val(SHIFT_TREASURY_ID);

            $('#global_discount_type_input').val($('#global_discount_type').val() || 'amount');
            $('#global_discount_value_input').val(num($('#global_discount_value').val()));

            syncLineMetaViews($('#cartBody .cart-row:first'));
            calcAll();

            setTimeout(()=> $('#barcodeInput').focus(), 200);
        });

        // Add empty line
        $('#addEmptyLine').on('click', function(){
            $('#cartBody').append(lineHtml());
            reindexLines();
            const $new = $('#cartBody .cart-row:last .item-select');
            initItemSelect2($new);
            syncLineMetaViews($('#cartBody .cart-row:last'));
            setTimeout(()=> $new.select2('open'), 120);
        });

        // Remove line
        $(document).on('click', '.remove-line', function(){
            const $rows = $('#cartBody .cart-row');
            if ($rows.length <= 1) { toastr.info('لا يمكن حذف آخر سطر.'); return; }
            $(this).closest('.cart-row').remove();
            reindexLines();
            calcAll();
        });

        // Edit line
        $(document).on('click', '.edit-line', function(){
            openLineEditModal($(this).closest('.cart-row'));
        });
        $('#lem_apply').on('click', applyLineEditModal);
        $(document).on('keydown', '#lineEditModal input, #lineEditModal select', function(e){
            if (e.key === 'Enter'){ e.preventDefault(); applyLineEditModal(); }
        });

        // Clear cart
        $('#clearCart').on('click', function(){
            if (!confirm('تصفير السلة بالكامل؟')) return;
            $('#cartBody').html(lineHtml());
            reindexLines();
            initItemSelect2($('#cartBody .cart-row:first .item-select'));
            syncLineMetaViews($('#cartBody .cart-row:first'));

            $('#pay_cash,#pay_card,#pay_wallet').val('0');
            $('#pay_terminal_id').val('');
            if (SHIFT_TREASURY_ID) $('#pay_treasury_id').val(SHIFT_TREASURY_ID);
            else $('#pay_treasury_id').val('');

            calcAll();
            $('#barcodeInput').focus();
        });

        // Qty +/- buttons
        $(document).on('click', '.qty-plus', function(){
            const $row = $(this).closest('.cart-row');
            const $q = $row.find('.qty');
            $q.val((num($q.val()) + 1).toFixed(4));
            calcAll();
        });
        $(document).on('click', '.qty-minus', function(){
            const $row = $(this).closest('.cart-row');
            const $q = $row.find('.qty');
            const v = Math.max(0, num($q.val()) - 1);
            $q.val(v.toFixed(4));
            calcAll();
        });

        // Recalc triggers
        $(document).on('input change', '.qty, #global_discount_type, #global_discount_value, #global_vat_rate', function(){
            // لو غيرت VAT default، مش هنغير سطور قديمة تلقائيًا (اختياري)
            calcAll();
        });

        // Payment type helper (auto due date)
        $(document).on('change', '#payment_type', function(){
            const v = $(this).val();
            const $due = $('#due_date');
            if (v === 'credit' && !$due.val()) {
                const d = new Date();
                d.setDate(d.getDate() + 7);
                const yyyy = d.getFullYear();
                const mm = String(d.getMonth()+1).padStart(2,'0');
                const dd = String(d.getDate()).padStart(2,'0');
                $due.val(`${yyyy}-${mm}-${dd}`);
            }
        });

        // Barcode handlers
        $('#barcodeBtn').on('click', handleBarcode);
        $('#barcodeInput').on('keydown', function(e){
            if (e.key === 'Enter'){ e.preventDefault(); handleBarcode(); }
      });

        // Open payment modal
        $('#openPayModal').on('click', openPayModal);
        $(document).on('input', '#pm_cash,#pm_card,#pm_wallet', pmUpdate);

        $('#pm_auto_cash').on('click', function(){
            const total = Math.max(0, num($('#total_input').val()));
            $('#pm_cash').val(total.toFixed(2));
            $('#pm_card').val('0');
            $('#pm_wallet').val('0');
            pmUpdate();
        });
        $('#pm_zero').on('click', function(){
            $('#pm_cash,#pm_card,#pm_wallet').val('0');
            pmUpdate();
        });
        $('#pm_apply').on('click', function(){
            if (applyPaymentFromModal()){
                const modalEl = document.getElementById('payModal');
                bootstrap.Modal.getInstance(modalEl).hide();
            }
        });

        // Hold/Park
        $('#parkBtn').on('click', function(){
            const sale = collectCurrentSale();
            if (!sale.rows.length){ toastr.warning('السلة فاضية. أضف أصناف أولاً.'); return; }
            const list = readPark();
            list.push(sale);
            writePark(list);
            toastr.success('تم تعليق الفاتورة (Park).');
        });

        $('#openParkModal').on('click', function(){
            renderParkList();
            new bootstrap.Modal(document.getElementById('parkModal')).show();
        });
        $('#parkRefresh').on('click', renderParkList);
        $('#parkClearAll').on('click', function(){
            if (!confirm('مسح كل الفواتير المعلقة؟')) return;
            writePark([]);
            renderParkList();
            toastr.success('تم مسح الكل.');
        });
        $(document).on('click', '.park-item', function(){
            const id = $(this).data('id');
            const list = readPark();
            const sale = list.find(x => x.id === id);
            if (!sale) return;

            writePark(list.filter(x => x.id !== id));

            const modalEl = document.getElementById('parkModal');
            bootstrap.Modal.getInstance(modalEl).hide();

            loadSaleToUI(sale);
        });

        // Prevent submit if negative stock
        $('#posForm').on('submit', function(e){
            if ($('#submitBtn').prop('disabled')) {
                e.preventDefault();
                toastr.error('لا يمكن الحفظ: يوجد أصناف كميتها أكبر من المخزون المتاح.');
                return false;
            }


            let hasMissingCostPrice = false;
            $('#cartBody .cart-row').each(function(){
                const cp = num($(this).find('.cost_price').val());
                if (cp <= 0) {
                    hasMissingCostPrice = true;
                    return false; // break
                }
            });

            if (hasMissingCostPrice) {
                e.preventDefault();
                toastr.error('جميع الأصناف يجب أن تملك سعر تكلفة أكبر من صفر.');
                return false;
            }

            return true;
        });

        // Keyboard shortcuts
        $(document).on('keydown', function(e){
            const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';

            if (e.key === 'F4') { e.preventDefault(); $('#customer_id').select2('open'); }
            if (e.key === 'F6') { e.preventDefault(); $('#barcodeInput').focus().select(); }
            if (e.key === 'F9') { e.preventDefault(); openPayModal(); }
            if (e.key === 'F7') { e.preventDefault(); $('#parkBtn').click(); }

            if (e.key === 'Delete') {
                if (['input','textarea','select'].includes(tag)) return;
                e.preventDefault();
                $('#clearCart').click();
            }
        });
    </script>
@endsection
