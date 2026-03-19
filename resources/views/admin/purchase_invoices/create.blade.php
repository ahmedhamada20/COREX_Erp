{{-- resources/views/admin/purchase_invoices/create.blade.php --}}
@extends('admin.layouts.master')

@section('title', 'إضافة فاتورة مشتريات')

@section('css')
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <style>
        .form-hint { font-size: 12px; color: #64748b; }
        .required:after { content: " *"; color: #dc2626; font-weight: 700; }
        .table td, .table th { vertical-align: middle; }
        .item-row .form-control, .item-row .form-select { min-width: 120px; }
        .money { direction: ltr; text-align: left; }
        .select2-container--bootstrap-5 .select2-selection { min-height: 38px; }
    </style>
@endsection

@section('content')
    <div class="content-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">إضافة فاتورة مشتريات</h5>
                <small class="text-muted">إنشاء فاتورة مشتريات جديدة</small>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('purchase_invoices.index') }}" class="btn btn-sm btn-light">رجوع</a>
            </div>
        </div>
    </div>

    @include('admin.Alerts')

    <form action="{{ route('purchase_invoices.store') }}" method="POST" id="purchaseInvoiceForm">
        @csrf

        <div class="row">
            {{-- بيانات الفاتورة --}}
            <div class="col-lg-8 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">بيانات الفاتورة</h6>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">

                            {{-- المورد (بنفس الاستايل) --}}
                            <div class="col-md-6">
                                <label class="form-label required" for="supplier_id">المورد</label>

                                <select name="supplier_id"
                                        id="supplier_id"
                                        class="form-select select2 @error('supplier_id') is-invalid @enderror"
                                        data-placeholder="اختر المورد">
                                    <option value=""></option>
                                    @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}" @selected((string)old('supplier_id') === (string)$s->id)>
                                            {{ $s->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-hint">اختر المورد المرتبط بفاتورة الشراء.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required" for="invoice_number">رقم الفاتورة (المورد)</label>
                                <input type="text"
                                       name="invoice_number"
                                       id="invoice_number"
                                       class="form-control @error('invoice_number') is-invalid @enderror"
                                       value="{{ old('invoice_number') }}"
                                       placeholder="مثال: INV-2026-001">
                                @error('invoice_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-hint">رقم فاتورة المورد كما هو في الورق/النظام.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="invoice_date">تاريخ الفاتورة</label>
                                <input type="date"
                                       name="invoice_date"
                                       id="invoice_date"
                                       class="form-control @error('invoice_date') is-invalid @enderror"
                                       value="{{ old('invoice_date', now()->toDateString()) }}">
                                @error('invoice_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-hint">لو فاضي هيتسجل بتاريخ اليوم.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required" for="payment_type">نوع السداد</label>
                                <select name="payment_type"
                                        id="payment_type"
                                        class="form-select @error('payment_type') is-invalid @enderror">
                                    <option value="cash"   @selected(old('payment_type','cash') === 'cash')>كاش</option>
                                    <option value="credit" @selected(old('payment_type') === 'credit')>آجل</option>
                                </select>
                                @error('payment_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-hint">لو آجل هيظهر تاريخ الاستحقاق.</div>
                            </div>

                            <div class="col-md-6" id="due_date_wrap" style="display:none;">
                                <label class="form-label" for="due_date">تاريخ الاستحقاق</label>
                                <input type="date"
                                       name="due_date"
                                       id="due_date"
                                       class="form-control @error('due_date') is-invalid @enderror"
                                       value="{{ old('due_date') }}">
                                @error('due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-hint">لو آجل وتركته فاضي، السيرفر هيحط +30 يوم.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="tax_included">
                                    الضريبة
                                </label>
                                <div class="form-check mt-2">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="tax_included"
                                           name="tax_included"
                                           value="1"
                                        @checked((bool)old('tax_included'))>
                                    <label class="form-check-label" for="tax_included">
                                        الأسعار تشمل الضريبة (معلومة فقط)
                                    </label>
                                </div>
                                <div class="form-hint">حالياً الحسابات هنا مبنية على ضريبة السطر (tax_rate).</div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label" for="notes">ملاحظات</label>
                                <textarea name="notes"
                                          id="notes"
                                          rows="2"
                                          class="form-control @error('notes') is-invalid @enderror"
                                          placeholder="أي ملاحظات تخص الفاتورة...">{{ old('notes') }}</textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-hint">اختياري.</div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            {{-- ملخص الإجماليات --}}
            <div class="col-lg-4 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">ملخص</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">إجمالي قبل الخصم</span>
                            <strong class="money" id="sum_subtotal_before_discount">0.00</strong>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">خصم السطور</span>
                            <strong class="money" id="sum_lines_discount">0.00</strong>
                        </div>

                        <hr>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label mb-1" for="discount_type">خصم الفاتورة</label>
                                <select name="discount_type" id="discount_type" class="form-select">
                                    <option value="none"    @selected(old('discount_type','none')==='none')>بدون</option>
                                    <option value="percent" @selected(old('discount_type')==='percent')>نسبة %</option>
                                    <option value="fixed"   @selected(old('discount_type')==='fixed')>قيمة</option>
                                </select>
                            </div>

                            <div class="col-6">
                                <label class="form-label mb-1" for="discount_rate">القيمة/النسبة</label>
                                <input type="number" step="0.01"
                                       name="discount_rate"
                                       id="discount_rate"
                                       class="form-control money"
                                       value="{{ old('discount_rate', 0) }}">
                            </div>
                            <div class="col-12">
                                <div class="form-hint">لو “نسبة” اكتب %، لو “قيمة” اكتب مبلغ.</div>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label mb-1" for="shipping_cost">الشحن</label>
                                <input type="number" step="0.01"
                                       name="shipping_cost"
                                       id="shipping_cost"
                                       class="form-control money"
                                       value="{{ old('shipping_cost', 0) }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label mb-1" for="other_charges">مصروفات أخرى</label>
                                <input type="number" step="0.01"
                                       name="other_charges"
                                       id="other_charges"
                                       class="form-control money"
                                       value="{{ old('other_charges', 0) }}">
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">ضريبة السطور</span>
                            <strong class="money" id="sum_tax">0.00</strong>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span class="text-muted">الإجمالي النهائي</span>
                            <strong class="money fs-5" id="sum_total">0.00</strong>
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa fa-save me-1"></i> حفظ الفاتورة
                        </button>

                        <div class="form-hint mt-2">
                            هيتم الحفظ كـ <b>Draft</b> تلقائيًا (لو عايز تعمل workflow للترحيل بعدين).
                        </div>

                        <input type="hidden" name="status" value="{{ old('status','draft') }}">
                    </div>
                </div>
            </div>

            {{-- بنود الفاتورة --}}
            <div class="col-12 mb-3">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">بنود الفاتورة</h6>

                        <button type="button" class="btn btn-sm btn-success" id="btnAddRow">
                            <i class="fa fa-plus"></i> إضافة بند
                        </button>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="itemsTable">
                                <thead class="table-light">
                                <tr>
                                    <th style="width: 300px;">الصنف</th>
                                    <th style="width: 120px;">الكمية</th>
                                    <th style="width: 140px;">سعر الوحدة</th>
                                    <th style="width: 140px;">إجمالي قبل خصم</th>

                                    <th style="width: 160px;">خصم السطر</th>
                                    <th style="width: 140px;">قيمة الخصم</th>

                                    <th style="width: 120px;">ضريبة %</th>
                                    <th style="width: 140px;">قيمة الضريبة</th>

                                    <th style="width: 160px;">إجمالي السطر</th>
                                    <th style="width: 60px;"></th>
                                </tr>
                                </thead>

                                <tbody id="itemsTbody">
                                {{-- لو في old items بعد validation --}}
                                @php($oldItems = old('items', []))
                                @if(!empty($oldItems))
                                    @foreach($oldItems as $i => $row)
                                        <tr class="item-row" data-index="{{ $i }}">
                                            <td>
                                                <select name="items[{{ $i }}][item_id]"
                                                        class="form-select select2 item_id @error("items.$i.item_id") is-invalid @enderror"
                                                        data-placeholder="اختر الصنف">
                                                    <option value=""></option>
                                                </select>
                                                @error("items.$i.item_id") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                <div class="form-hint">اختر الصنف من القائمة.</div>
                                            </td>

                                            <td>
                                                <input type="number" step="0.01" min="0.01"
                                                       name="items[{{ $i }}][quantity]"
                                                       class="form-control money qty @error("items.$i.quantity") is-invalid @enderror"
                                                       value="{{ $row['quantity'] ?? 1 }}">
                                                @error("items.$i.quantity") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </td>

                                            <td>
                                                <input type="number" step="0.01" min="0"
                                                       name="items[{{ $i }}][unit_price]"
                                                       class="form-control money unit_price @error("items.$i.unit_price") is-invalid @enderror"
                                                       value="{{ $row['unit_price'] ?? 0 }}">
                                                @error("items.$i.unit_price") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </td>

                                            <td><strong class="money line_subtotal_text">0.00</strong></td>

                                            <td>
                                                <div class="d-flex gap-1">
                                                    <select name="items[{{ $i }}][discount_type]" class="form-select discount_type">
                                                        <option value="none"    @selected(($row['discount_type'] ?? 'none')==='none')>بدون</option>
                                                        <option value="percent" @selected(($row['discount_type'] ?? '')==='percent')>%</option>
                                                        <option value="fixed"   @selected(($row['discount_type'] ?? '')==='fixed')>قيمة</option>
                                                    </select>
                                                    <input type="number" step="0.01" min="0"
                                                           name="items[{{ $i }}][discount_rate]"
                                                           class="form-control money discount_rate"
                                                           value="{{ $row['discount_rate'] ?? 0 }}"
                                                           placeholder="%">
                                                </div>
                                                <div class="form-hint">لو قيمة: اكتبها في (قيمة الخصم) تحت.</div>
                                            </td>

                                            <td>
                                                <input type="number" step="0.01" min="0"
                                                       name="items[{{ $i }}][discount_value]"
                                                       class="form-control money discount_value"
                                                       value="{{ $row['discount_value'] ?? 0 }}">
                                            </td>

                                            <td>
                                                <input type="number" step="0.01" min="0" max="100"
                                                       name="items[{{ $i }}][tax_rate]"
                                                       class="form-control money tax_rate"
                                                       value="{{ $row['tax_rate'] ?? 0 }}">
                                            </td>

                                            <td><strong class="money tax_value_text">0.00</strong></td>
                                            <td><strong class="money line_total_text">0.00</strong></td>

                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-danger btnRemoveRow">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                                </tbody>
                            </table>
                        </div>

                        <div class="form-hint">
                            * لازم تضيف على الأقل بند واحد. الضريبة والخصم يتم حسابهم من السطور.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        // items from controller
        const ITEMS = @json($itemsJs ?? []);

        // index counter
        let rowIndex = (() => {
            const last = $('#itemsTbody tr').last().data('index');
            return Number.isFinite(last) ? (parseInt(last) + 1) : 0;
        })();

        function initSelect2(scope) {
            (scope || $(document)).find('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: function () {
                    return $(this).data('placeholder') || 'اختر';
                },
                allowClear: true
            });
        }

        function setDueDateVisibility() {
            const type = $('#payment_type').val();
            if (type === 'credit') $('#due_date_wrap').show();
            else $('#due_date_wrap').hide();
        }

        function buildItemOptions(selectedId = null) {
            let html = `<option value=""></option>`;
            ITEMS.forEach(it => {
                const label = `${it.name} — ${it.code ?? ''}${it.barcode ? ' — ' + it.barcode : ''}`;
                const sel = (selectedId && String(selectedId) === String(it.id)) ? 'selected' : '';
                html += `<option value="${it.id}" ${sel}>${escapeHtml(label)}</option>`;
            });
            return html;
        }

        function escapeHtml(str) {
            return String(str ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function addRow(prefill = {}) {
            const i = rowIndex++;

            const tr = $(`
                <tr class="item-row" data-index="${i}">
                    <td>
                        <select name="items[${i}][item_id]" class="form-select select2 item_id" data-placeholder="اختر الصنف">
                            ${buildItemOptions(prefill.item_id || null)}
                        </select>
                        <div class="form-hint">اختر الصنف من القائمة.</div>
                    </td>

                    <td>
                        <input type="number" step="0.01" min="0.01"
                               name="items[${i}][quantity]"
                               class="form-control money qty"
                               value="${prefill.quantity ?? 1}">
                    </td>

                    <td>
                        <input type="number" step="0.01" min="0"
                               name="items[${i}][unit_price]"
                               class="form-control money unit_price"
                               value="${prefill.unit_price ?? 0}">
                    </td>

                    <td><strong class="money line_subtotal_text">0.00</strong></td>

                    <td>
                        <div class="d-flex gap-1">
                            <select name="items[${i}][discount_type]" class="form-select discount_type">
                                <option value="none">بدون</option>
                                <option value="percent">%</option>
                                <option value="fixed">قيمة</option>
                            </select>
                            <input type="number" step="0.01" min="0"
                                   name="items[${i}][discount_rate]"
                                   class="form-control money discount_rate"
                                   value="${prefill.discount_rate ?? 0}"
                                   placeholder="%">
                        </div>
                        <div class="form-hint">لو قيمة: اكتبها في (قيمة الخصم) تحت.</div>
                    </td>

                    <td>
                        <input type="number" step="0.01" min="0"
                               name="items[${i}][discount_value]"
                               class="form-control money discount_value"
                               value="${prefill.discount_value ?? 0}">
                    </td>

                    <td>
                        <input type="number" step="0.01" min="0" max="100"
                               name="items[${i}][tax_rate]"
                               class="form-control money tax_rate"
                               value="${prefill.tax_rate ?? 0}">
                    </td>

                    <td><strong class="money tax_value_text">0.00</strong></td>
                    <td><strong class="money line_total_text">0.00</strong></td>

                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger btnRemoveRow">
                            <i class="fa fa-times"></i>
                        </button>
                    </td>
                </tr>
            `);

            $('#itemsTbody').append(tr);

            initSelect2(tr);

            // لو فيها selected item_id already، select2 محتاج trigger
            if (prefill.item_id) {
                tr.find('.item_id').val(String(prefill.item_id)).trigger('change');
            }

            recalcAll();
        }

        function parseNum(v) {
            const n = parseFloat(v);
            return isNaN(n) ? 0 : n;
        }

        function money(n) {
            return (Math.round((n + Number.EPSILON) * 100) / 100).toFixed(2);
        }

        function recalcRow(tr) {
            const qty  = Math.max(0, parseNum(tr.find('.qty').val()));
            const unit = Math.max(0, parseNum(tr.find('.unit_price').val()));
            const lineSub = qty * unit;

            const dType = tr.find('.discount_type').val() || 'none';
            const dRate = Math.max(0, parseNum(tr.find('.discount_rate').val()));
            const dValInput = Math.max(0, parseNum(tr.find('.discount_value').val()));

            let dValue = 0;
            if (dType === 'percent') {
                dValue = lineSub * (dRate / 100);
            } else if (dType === 'fixed') {
                dValue = Math.min(lineSub, dValInput);
                // keep discount_rate irrelevant in fixed; optional: force it 0
            }

            let afterDiscount = Math.max(0, lineSub - dValue);

            const tRate = Math.max(0, parseNum(tr.find('.tax_rate').val()));
            const tValue = afterDiscount * (tRate / 100);

            const lineTotal = afterDiscount + tValue;

            tr.find('.line_subtotal_text').text(money(lineSub));
            tr.find('.tax_value_text').text(money(tValue));
            tr.find('.line_total_text').text(money(lineTotal));

            return {
                lineSub,
                dValue,
                tValue,
                lineTotal
            };
        }

        function invoiceLevelDiscount(subtotalBeforeDiscountMinusLineDiscounts) {
            const type = $('#discount_type').val() || 'none';
            const rate = Math.max(0, parseNum($('#discount_rate').val()));

            if (type === 'percent') {
                return subtotalBeforeDiscountMinusLineDiscounts * (rate / 100);
            }
            if (type === 'fixed') {
                return Math.min(subtotalBeforeDiscountMinusLineDiscounts, rate);
            }
            return 0;
        }

        function recalcAll() {
            let subtotalBeforeDiscount = 0;
            let linesDiscount = 0;
            let taxTotal = 0;

            $('#itemsTbody tr.item-row').each(function () {
                const r = recalcRow($(this));
                subtotalBeforeDiscount += r.lineSub;
                linesDiscount += r.dValue;
                taxTotal += r.tValue;
            });

            const subtotalAfterLineDiscounts = Math.max(0, subtotalBeforeDiscount - linesDiscount);
            const invDisc = invoiceLevelDiscount(subtotalAfterLineDiscounts);

            const subtotalAfterAllDiscounts = Math.max(0, subtotalAfterLineDiscounts - invDisc);

            const shipping = Math.max(0, parseNum($('#shipping_cost').val()));
            const other = Math.max(0, parseNum($('#other_charges').val()));

            const total = subtotalAfterAllDiscounts + taxTotal + shipping + other;

            $('#sum_subtotal_before_discount').text(money(subtotalBeforeDiscount));
            $('#sum_lines_discount').text(money(linesDiscount));
            $('#sum_tax').text(money(taxTotal));
            $('#sum_total').text(money(total));
        }

        $(document).ready(function () {
            initSelect2();

            // لو مفيش old items خالص: ابدأ بسطر واحد
            if ($('#itemsTbody tr').length === 0) {
                addRow();
            } else {
                // اعمل options للـ old rows اللي اتعملت في Blade (كانت فاضية options)
                $('#itemsTbody tr.item-row').each(function () {
                    const tr = $(this);
                    const i = tr.data('index');
                    const selected = @json($oldItems ?? []);
                    // fill options
                    const sel = tr.find('.item_id');
                    const oldSelectedId = sel.attr('data-old') || null;

                    // rebuild options and keep selection by reading old() from input name
                    const currentVal = sel.val();
                    sel.html(buildItemOptions(currentVal || null));
                });

                initSelect2($('#itemsTbody'));
                recalcAll();
            }

            setDueDateVisibility();

            $('#payment_type').on('change', function () {
                setDueDateVisibility();
            });

            $('#btnAddRow').on('click', function () {
                addRow();
            });

            $(document).on('click', '.btnRemoveRow', function () {
                const tr = $(this).closest('tr');
                tr.remove();
                recalcAll();
            });

            $(document).on('input change', '#itemsTbody .qty, #itemsTbody .unit_price, #itemsTbody .discount_type, #itemsTbody .discount_rate, #itemsTbody .discount_value, #itemsTbody .tax_rate', function () {
                recalcAll();
            });

            $(document).on('input change', '#discount_type, #discount_rate, #shipping_cost, #other_charges', function () {
                recalcAll();
            });

            // optional: prevent submit if no rows
            $('#purchaseInvoiceForm').on('submit', function (e) {
                if ($('#itemsTbody tr.item-row').length === 0) {
                    e.preventDefault();
                    toastr.error('لازم تضيف بند واحد على الأقل');
                }
            });
        });
    </script>
@endsection
