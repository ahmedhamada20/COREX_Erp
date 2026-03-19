{{-- resources/views/admin/purchase_returns/edit.blade.php --}}
@extends('admin.layouts.master')

@section('title', 'تعديل مرتجع مشتريات')

@section('css')
    <style>
        :root {
            --muted: #64748b;
            --card: #ffffff;
            --border: #e5e7eb;
            --soft: #f8fafc;
        }

        .page-title { font-weight: 900; letter-spacing: .2px; }
        .muted { color: var(--muted); }
        .num { direction: ltr; text-align: left; font-variant-numeric: tabular-nums; }

        .card-soft {
            background: var(--card);
            border: 1px solid var(--border);
            box-shadow: 0 2px 12px rgba(0,0,0,.04);
            border-radius: 14px;
        }

        .chip {
            border-radius: 999px;
            padding: 6px 10px;
            font-weight: 800;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid transparent;
            white-space: nowrap;
        }
        .chip-gray { background:#f1f5f9; border-color:#e2e8f0; color:#0f172a; }
        .chip-blue { background:#eef2ff; border-color:#c7d2fe; color:#3730a3; }
        .chip-green { background:#ecfdf5; border-color:#a7f3d0; color:#065f46; }
        .chip-yellow { background:#fffbeb; border-color:#fde68a; color:#92400e; }
        .chip-red { background:#fef2f2; border-color:#fecaca; color:#991b1b; }

        .totals-box {
            background: var(--soft);
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            padding: 14px;
        }
        .totals-box .row + .row { margin-top: 8px; }
        .totals-box .label { color: var(--muted); font-size: 13px; }
        .totals-box .value { font-weight: 900; }

        .table thead th { background:#f8fafc; white-space: nowrap; }
        .table td, .table th { vertical-align: middle; }
        .item-meta { font-size: 12px; color: var(--muted); }

        .qty-input {
            min-width: 120px;
            text-align: center;
            direction: ltr;
            font-weight: 800;
        }

        .readonly-pill {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 6px 10px;
            display: inline-block;
        }

        .danger-hint {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #9f1239;
            border-radius: 12px;
            padding: 10px 12px;
            font-weight: 700;
            font-size: 13px;
        }

        .success-hint {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            border-radius: 12px;
            padding: 10px 12px;
            font-weight: 700;
            font-size: 13px;
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

        $canEdit = ($status === 'draft');

        $inv = $invoice ?? ($return->invoice ?? $return->purchaseInvoice ?? null);

        $invDate = $inv?->invoice_date ? \Carbon\Carbon::parse($inv->invoice_date)->format('Y-m-d') : '-';
        $returnDate = $return->return_date ? \Carbon\Carbon::parse($return->return_date)->format('Y-m-d') : '-';

        // رقم الفاتورة اللي هتخزنه عندك في return_number
        $returnNumber = (string)($return->return_number ?? $inv?->invoice_number ?? '');
    @endphp

    <div class="content-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 page-title">تعديل مرتجع مشتريات</h5>
                <small class="text-muted">
                    {{ $return->purchase_return_code ?? ('PR#'.$return->id) }}
                    — مرجع فاتورة: <strong>{{ $inv?->purchase_invoice_code ?? '-' }}</strong>
                    / <strong>{{ $inv?->invoice_number ?? '-' }}</strong>
                </small>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('purchase_returns.show', $return->id) }}" class="btn btn-sm btn-light">رجوع</a>
                @if(Route::has('purchase_invoices.show') && $inv?->id)
                    <a href="{{ route('purchase_invoices.show', $inv->id) }}" class="btn btn-sm btn-light">فتح الفاتورة</a>
                @endif
            </div>
        </div>
    </div>

    @include('admin.Alerts')

    @if(!$canEdit)
        <div class="danger-hint mb-3">
            لا يمكن تعديل مرتجع مُرحّل أو مُلغى. (الحالة الحالية: {{ $statusChip[1] }})
            للتصحيح: قم بإلغاء المرتجع أو أنشئ مرتجع جديد.
        </div>
    @else
        <div class="success-hint mb-3">
            التعديل مسموح للمسودة فقط — يمكنك تعديل “الكميات” فقط ضمن المتاح، دون تغيير أصناف/أسعار/ضريبة.
        </div>
    @endif

    <form method="POST" action="{{ route('purchase_returns.update', $return->id) }}">
        @csrf
        @method('PUT')

        {{-- Hidden: الربط القانوني + return_number --}}
        <input type="hidden" name="purchase_invoice_id" value="{{ $return->purchase_invoice_id }}">
        <input type="hidden" name="supplier_id" value="{{ $return->supplier_id }}">
        <input type="hidden" name="status" value="{{ $return->status ?? 'draft' }}">
        <input type="hidden" name="return_number" value="{{ $returnNumber }}">

        {{-- Header info --}}
        <div class="card card-soft mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">بيانات المرتجع</h6>
                    <div class="mt-2 d-flex flex-wrap gap-2">
                    <span class="chip {{ $statusChip[0] }}">
                        <i class="fa fa-circle" style="font-size:8px;"></i>
                        الحالة: {{ $statusChip[1] }}
                    </span>
                        <span class="chip chip-blue">
                        <i class="fa fa-calendar"></i>
                        تاريخ الفاتورة: {{ $invDate }}
                    </span>
                        <span class="chip chip-yellow">
                        <i class="fa fa-hashtag"></i>
                        Return No: {{ $returnNumber ?: '-' }}
                    </span>
                    </div>
                </div>

                <div class="text-end">
                    <div class="muted">تاريخ المرتجع</div>
                    <div class="fw-bold">{{ $returnDate }}</div>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">تاريخ المرتجع</label>
                        <input type="date" name="return_date"
                               value="{{ old('return_date', $return->return_date ?? now()->toDateString()) }}"
                               class="form-control @error('return_date') is-invalid @enderror"
                            {{ !$canEdit ? 'disabled' : '' }}>
                        @error('return_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Transaction ID (اختياري)</label>
                        <input type="text" name="transaction_id"
                               value="{{ old('transaction_id', $return->transaction_id) }}"
                               class="form-control @error('transaction_id') is-invalid @enderror"
                               placeholder="مثال: TRX-123" {{ !$canEdit ? 'disabled' : '' }}>
                        @error('transaction_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">ملاحظات (اختياري)</label>
                        <input type="text" name="notes"
                               value="{{ old('notes', $return->notes) }}"
                               class="form-control @error('notes') is-invalid @enderror"
                               placeholder="سبب التعديل..." {{ !$canEdit ? 'disabled' : '' }}>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-3 muted" style="font-size: 13px;">
                    ⚠️ أي بند “كمية = 0” سيتم تجاهله عند الحفظ. لا يمكن إدخال كمية أكبر من المتاح.
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div class="card card-soft mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">بنود المرتجع (عدّل الكمية فقط)</h6>
                <div class="muted">
                    البنود: <strong>{{ count($items ?? []) }}</strong>
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

                            <th class="text-center" style="width:120px;">المشتراة</th>
                            <th class="text-center" style="width:120px;">مرتجع سابق</th>
                            <th class="text-center" style="width:130px;">المتاح</th>

                            <th class="text-center" style="width:170px;">كمية المرتجع (الحالية)</th>

                            <th class="text-center" style="width:130px;">سعر الوحدة</th>
                            <th class="text-center" style="width:110px;">VAT%</th>
                            <th class="text-center" style="width:150px;">إجمالي السطر</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($items as $i => $it)
                            @php
                                $available = (float)($it['qty_available'] ?? 0); // المتاح "بعد خصم مرتجعات أخرى" + يسمح بكميتك الحالية
                                $current   = (float)($it['qty_current_return'] ?? 0);

                                $unitPrice = (float)($it['unit_price'] ?? 0);
                                $taxRate   = (float)($it['tax_rate'] ?? 0);

                                // input max = available (المتاح يشمل الحالي)
                                $max = $available;

                                $initialQty = old("items.$i.quantity", $current);
                            @endphp

                            <tr data-row="1"
                                data-unit-price="{{ $unitPrice }}"
                                data-tax-rate="{{ $taxRate }}"
                                data-max="{{ $max }}">
                                <td>{{ $i + 1 }}</td>

                                <td>
                                    <div class="fw-bold">{{ $it['item_name'] ?? ('Item #'.($it['item_id'] ?? '')) }}</div>
                                    <div class="item-meta">
                                        {{ $it['code'] ?? '-' }}
                                        @if(!empty($it['barcode'])) — {{ $it['barcode'] }} @endif
                                    </div>

                                    {{-- Hidden required per item --}}
                                    <input type="hidden" name="items[{{ $i }}][item_id]" value="{{ $it['item_id'] }}">
                                    <input type="hidden" name="items[{{ $i }}][purchase_invoice_item_id]" value="{{ $it['purchase_invoice_item_id'] }}">
                                    <input type="hidden" name="items[{{ $i }}][warehouse_name_snapshot]" value="{{ $it['warehouse_name_snapshot'] ?? '' }}">
                                    <input type="hidden" name="items[{{ $i }}][unit_price]" value="{{ $unitPrice }}">
                                    <input type="hidden" name="items[{{ $i }}][tax_rate]" value="{{ $taxRate }}">
                                </td>

                                <td>{{ $it['warehouse_name_snapshot'] ?? '-' }}</td>

                                <td class="text-center num">{{ number_format((float)($it['qty_purchased'] ?? 0), 2) }}</td>
                                <td class="text-center num">{{ number_format((float)($it['qty_returned_other'] ?? 0), 2) }}</td>

                                <td class="text-center">
                                    <span class="readonly-pill num">{{ number_format($max, 2) }}</span>
                                </td>

                                <td class="text-center">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="{{ $max }}"
                                        data-available="{{ $max }}"
                                        name="items[{{ $i }}][quantity]"
                                        value="{{ $initialQty }}"
                                        class="form-control form-control-sm qty-input js-qty @error("items.$i.quantity") is-invalid @enderror"
                                        {{ !$canEdit || $max <= 0 ? 'disabled' : '' }}
                                    >

                                    {{-- Live error --}}
                                    <div class="invalid-feedback js-qty-err" style="display:none;"></div>

                                    {{-- Server error --}}
                                    @error("items.$i.quantity") <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </td>

                                <td class="text-center num">{{ number_format($unitPrice, 2) }}</td>
                                <td class="text-center num">{{ number_format($taxRate, 2) }}</td>

                                <td class="text-center num fw-bold js-line-total">0.00</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center muted py-4">لا توجد بنود</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 muted" style="font-size: 13px;">
                    ⚠️ لو كتبت كمية أكبر من المتاح سيظهر خطأ فوري ولن يسمح بالحفظ.
                </div>
            </div>
        </div>

        {{-- Totals + Save --}}
        <div class="row">
            <div class="col-lg-4 ms-auto mb-3">
                <div class="card card-soft">
                    <div class="card-header">
                        <h6 class="mb-0">إجماليات المرتجع</h6>
                    </div>
                    <div class="card-body">
                        <div class="totals-box">
                            <div class="row">
                                <div class="col-7 label">Subtotal</div>
                                <div class="col-5 value text-end num" id="rtSubtotal">0.00</div>
                            </div>
                            <div class="row">
                                <div class="col-7 label">VAT</div>
                                <div class="col-5 value text-end num" id="rtVat">0.00</div>
                            </div>

                            <hr>

                            <div class="row">
                                <div class="col-7 label">Total</div>
                                <div class="col-5 value text-end num" id="rtTotal">0.00</div>
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100" id="btnSave" {{ !$canEdit ? 'disabled' : '' }}>
                                حفظ التعديلات
                            </button>
                        </div>

                        <div class="mt-2 muted" style="font-size: 12px;">
                            الشرط للحفظ: (١) إدخال كمية مرتجع واحدة على الأقل، (٢) لا توجد أخطاء كمية.
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </form>
@endsection

@section('js')
    <script>
        (function () {
            function toNum(v) {
                const n = parseFloat(v);
                return isNaN(n) ? 0 : n;
            }

            function round2(n) {
                return Math.round((n + Number.EPSILON) * 100) / 100;
            }

            function setLiveError(input, msg) {
                input.classList.add('is-invalid');
                const err = input.closest('td')?.querySelector('.js-qty-err');
                if (err) {
                    err.textContent = msg;
                    err.style.display = 'block';
                }
            }

            function clearLiveError(input) {
                input.classList.remove('is-invalid');
                const err = input.closest('td')?.querySelector('.js-qty-err');
                if (err) {
                    err.textContent = '';
                    err.style.display = 'none';
                }
            }

            function validateInput(input) {
                const available = toNum(input.dataset.available);
                let qty = (input.value === '') ? 0 : toNum(input.value);

                if (qty < 0) {
                    setLiveError(input, 'الكمية غير صحيحة (لا يمكن أن تكون أقل من 0).');
                    return { ok:false, qty: 0, available };
                }

                if (qty > available) {
                    setLiveError(input, `كمية غير صحيحة — أقصى كمية للصنف ده: ${available.toFixed(2)}`);
                    return { ok:false, qty: available, available };
                }

                clearLiveError(input);
                return { ok:true, qty, available };
            }

            function recalc() {
                let subtotal = 0;
                let vat = 0;

                let hasError = false;
                let anyQty = false;

                document.querySelectorAll('tr[data-row="1"]').forEach(tr => {
                    const unit = toNum(tr.getAttribute('data-unit-price'));
                    const rate = toNum(tr.getAttribute('data-tax-rate'));

                    const qtyInput = tr.querySelector('.js-qty');
                    let qty = 0;

                    if (qtyInput) {
                        const v = validateInput(qtyInput);
                        if (!v.ok) hasError = true;
                        qty = v.qty;

                        if (toNum(qtyInput.value) > 0) anyQty = true;
                    }

                    const lineSub = round2(qty * unit);
                    const lineVat = round2(lineSub * (rate / 100));
                    const lineTot = round2(lineSub + lineVat);

                    subtotal = round2(subtotal + lineSub);
                    vat      = round2(vat + lineVat);

                    const tdTotal = tr.querySelector('.js-line-total');
                    if (tdTotal) tdTotal.textContent = lineTot.toFixed(2);
                });

                const total = round2(subtotal + vat);

                document.getElementById('rtSubtotal').textContent = subtotal.toFixed(2);
                document.getElementById('rtVat').textContent      = vat.toFixed(2);
                document.getElementById('rtTotal').textContent    = total.toFixed(2);

                const btn = document.getElementById('btnSave');
                if (btn) btn.disabled = (!anyQty) || hasError;
            }

            document.addEventListener('input', function (e) {
                if (e.target && e.target.classList.contains('js-qty')) {
                    recalc();
                }
            });

            document.addEventListener('blur', function (e) {
                if (!e.target || !e.target.classList.contains('js-qty')) return;

                const input = e.target;
                const available = toNum(input.dataset.available);
                let qty = (input.value === '') ? 0 : toNum(input.value);

                if (qty < 0) qty = 0;

                if (qty > available) {
                    input.value = available.toFixed(2);
                    clearLiveError(input);
                } else {
                    input.value = qty.toFixed(2);
                    clearLiveError(input);
                }

                recalc();
            }, true);

            recalc();
        })();
    </script>
@endsection
