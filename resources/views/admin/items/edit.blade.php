@extends('admin.layouts.master')

@section('title', 'تعديل صنف')

@section('css')
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <style>
        .section-title { font-size: .95rem; font-weight: 600; }
        .img-preview { width: 132px; height: 132px; border-radius: 14px; overflow: hidden; border: 1px dashed rgba(0,0,0,.15); background: rgba(0,0,0,.02); }
        .img-preview img { width: 100%; height: 100%; object-fit: cover; }
        .form-hint { font-size: .825rem; }
        .btn-icon { display: inline-flex; align-items: center; gap: .35rem; }
        .modal .invalid-feedback { display: block; }
        .money { direction:ltr; text-align:left; font-variant-numeric: tabular-nums; }
        .is-invalid + .select2-container .select2-selection { border-color: #dc3545 !important; }
    </style>
@endsection

@section('content')

    <div class="content-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">تعديل صنف</h5>
                <small class="text-muted">{{ $item->name }} — {{ $item->items_code }}</small>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('items.index') }}" class="btn btn-sm btn-light">رجوع</a>
                <a href="{{ route('items.show', $item->id) }}" class="btn btn-sm btn-outline-secondary">عرض</a>
            </div>
        </div>
    </div>

    @include('admin.Alerts')

    @php
        $defaultImg = asset('images/no-image.png');
        $currentImg = $item->image ? asset('storage/'.$item->image) : $defaultImg;
    @endphp

    <div class="row">
        <div class="col-lg-12">

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">بيانات الصنف</h6>
                    <span class="badge bg-light text-muted">Edit</span>
                </div>

                <div class="card-body">
                    <form action="{{ route('items.update', $item->id) }}" method="POST" id="itemForm" enctype="multipart/form-data" novalidate>
                        @csrf
                        @method('PUT')

                        {{-- Basic --}}
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="section-title">البيانات الأساسية</div>
                            <div class="text-muted form-hint">الحقول المطلوبة مميزة *</div>
                        </div>

                        <div class="row">

                            <div class="col-md-4 mb-3">
                                <label class="form-label">كود الصنف <span class="text-danger">*</span></label>
                                <input type="text" name="items_code"
                                       value="{{ old('items_code', $item->items_code) }}"
                                       class="form-control @error('items_code') is-invalid @enderror" readonly>
                                @error('items_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label d-flex justify-content-between align-items-center">
                                    <span>الباركود <span class="text-danger">*</span></span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-icon" id="btnGenerateBarcodeMain">
                                        <i class="ti ti-refresh"></i> توليد
                                    </button>
                                </label>
                                <input type="text" name="barcode" id="barcode_input"
                                       value="{{ old('barcode', $item->barcode) }}"
                                       class="form-control @error('barcode') is-invalid @enderror" required inputmode="numeric">
                                @error('barcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted form-hint">12 رقم ويبدأ بـ 20 (ويكون فريد).</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">التاريخ</label>
                                <input type="date" name="date"
                                       value="{{ old('date', $item->date ? \Carbon\Carbon::parse($item->date)->format('Y-m-d') : '') }}"
                                       class="form-control @error('date') is-invalid @enderror">
                                @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-8 mb-3">
                                <label class="form-label">اسم الصنف <span class="text-danger">*</span></label>
                                <input type="text" name="name"
                                       value="{{ old('name', $item->name) }}"
                                       class="form-control @error('name') is-invalid @enderror" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">نوع الصنف <span class="text-danger">*</span></label>
                                <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="">اختر</option>
                                    <option value="store" @selected(old('type', $item->type) === 'store')>مخزني</option>
                                    <option value="consumption" @selected(old('type', $item->type) === 'consumption')>استهلاكي</option>
                                    <option value="custody" @selected(old('type', $item->type) === 'custody')>عهدة</option>
                                </select>
                                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Image --}}
                            <div class="col-md-12 mb-4">
                                <div class="section-title mb-2">صورة الصنف</div>

                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="img-preview">
                                        <img id="imagePreview" src="{{ $currentImg }}" alt="preview">
                                    </div>

                                    <div class="flex-grow-1">
                                        <input type="file" name="image" id="imageInput"
                                               class="form-control @error('image') is-invalid @enderror" accept="image/*">
                                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror

                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted form-hint">JPG / PNG / WEBP — حد أقصى 2MB</small>

                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-sm btn-light" id="btnResetImage">رجوع للأصل</button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" id="btnClearImage">مسح الاختيار</button>
                                            </div>
                                        </div>

                                        {{-- لو هتدعم حذف الصورة من السيرفر --}}
                                        {{-- <input type="hidden" name="remove_image" id="remove_image" value="0"> --}}
                                    </div>
                                </div>
                            </div>

                            {{-- Relations --}}
                            <div class="col-12 mb-2">
                                <div class="section-title">التصنيفات والربط</div>
                            </div>

                            {{-- Category --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label d-flex justify-content-between align-items-center">
                                    <span>التصنيف <span class="text-danger">*</span></span>
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-icon"
                                            data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                                        <i class="ti ti-plus"></i> إضافة
                                    </button>
                                </label>

                                <select name="item_category_id" id="category_select"
                                        class="form-select select2 @error('item_category_id') is-invalid @enderror" required>
                                    <option value="">-- اختر التصنيف --</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" @selected(old('item_category_id', $item->item_category_id) == $cat->id)>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('item_category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            {{-- Parent --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label d-flex justify-content-between align-items-center">
                                    <span>الصنف الأب (اختياري)</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-icon"
                                            data-bs-toggle="modal" data-bs-target="#createParentItemModal">
                                        <i class="ti ti-plus"></i> إضافة
                                    </button>
                                </label>

                                <select name="item_id" id="parent_item_select" class="form-select select2 @error('item_id') is-invalid @enderror">
                                    <option value="">-- بدون --</option>
                                    @foreach($parents as $p)
                                        @continue($p->id == $item->id)
                                        <option value="{{ $p->id }}" @selected(old('item_id', $item->item_id) == $p->id)>
                                            {{ $p->name }} ({{ $p->items_code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('item_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <small class="text-muted form-hint">اختيار الأب يساعد في تجميع الأصناف.</small>
                            </div>

                            {{-- Unit --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">وحدة القياس الأساسية <span class="text-danger">*</span></label>
                                <input type="text" name="unit_id"
                                       value="{{ old('unit_id', $item->unit_id) }}"
                                       class="form-control @error('unit_id') is-invalid @enderror" required>
                                @error('unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted form-hint">مثال: Box / Carton / Pack</small>
                            </div>

                            {{-- Status --}}
                            <div class="col-md-6 mb-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="status" value="1" id="status"
                                        @checked(old('status', $item->status))>
                                    <label class="form-check-label" for="status">نشط</label>
                                </div>
                            </div>

                            {{-- Prices --}}
                            <div class="col-12 mb-2">
                                <div class="section-title">الأسعار</div>
                                <small class="text-muted form-hint">عدّل الأسعار كما تريد.</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">السعر القطاعي (وحدة الأب) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="price"
                                       value="{{ old('price', $item->price) }}"
                                       class="form-control money @error('price') is-invalid @enderror" required>
                                @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">سعر نص الجملة (وحدة الأب) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="nos_egomania_price"
                                       value="{{ old('nos_egomania_price', $item->nos_egomania_price) }}"
                                       class="form-control money @error('nos_egomania_price') is-invalid @enderror" required>
                                @error('nos_egomania_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">سعر الجملة (وحدة الأب) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="egomania_price"
                                       value="{{ old('egomania_price', $item->egomania_price) }}"
                                       class="form-control money @error('egomania_price') is-invalid @enderror" required>
                                @error('egomania_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">السعر القطاعي (التجزئة) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="price_retail"
                                       value="{{ old('price_retail', $item->price_retail) }}"
                                       class="form-control money @error('price_retail') is-invalid @enderror" required>
                                @error('price_retail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">سعر نص الجملة (التجزئة) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="nos_gomla_price_retail"
                                       value="{{ old('nos_gomla_price_retail', $item->nos_gomla_price_retail) }}"
                                       class="form-control money @error('nos_gomla_price_retail') is-invalid @enderror" required>
                                @error('nos_gomla_price_retail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">سعر الجملة (التجزئة) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="gomla_price_retail"
                                       value="{{ old('gomla_price_retail', $item->gomla_price_retail) }}"
                                       class="form-control money @error('gomla_price_retail') is-invalid @enderror" required>
                                @error('gomla_price_retail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Retail --}}
                            <div class="col-12 mb-2 mt-2">
                                <div class="section-title">التجزئة</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label d-block">هل للصنف وحدة تجزئة؟</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="does_has_retail_unit" value="1"
                                           id="does_has_retail_unit" @checked(old('does_has_retail_unit', $item->does_has_retail_unit))>
                                    <label class="form-check-label" for="does_has_retail_unit">نعم، يوجد وحدة تجزئة</label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3" id="retail_unit_wrap">
                                <label class="form-label">وحدة التجزئة</label>
                                <input type="text" name="retail_unit" id="retail_unit"
                                       value="{{ old('retail_unit', $item->retail_unit) }}"
                                       class="form-control @error('retail_unit') is-invalid @enderror">
                                @error('retail_unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted form-hint">مثال: Piece</small>
                            </div>

                            <div class="col-md-6 mb-3" id="retail_ratio_wrap">
                                <label class="form-label">معامل التحويل (التجزئة إلى الأب)</label>
                                <input type="number" step="0.01" min="1" name="retail_uom_quintToParent" id="retail_uom_quintToParent"
                                       value="{{ old('retail_uom_quintToParent', $item->retail_uom_quintToParent) }}"
                                       class="form-control @error('retail_uom_quintToParent') is-invalid @enderror">
                                @error('retail_uom_quintToParent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted form-hint">مثال: 12 (يعني 12 قطعة = 1 وحدة أساسية)</small>
                            </div>

                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a href="{{ route('items.index') }}" class="btn btn-light">إلغاء</a>
                            <button type="submit" class="btn btn-primary btn-icon" id="btnSubmit">
                                <i class="ti ti-device-floppy"></i> تحديث
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>

    {{-- Category Modal --}}
    <div class="modal fade" id="createCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">إضافة تصنيف جديد</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">اسم التصنيف</label>
                        <input type="text" id="new_category_name" class="form-control" placeholder="مثال: مواد غذائية">
                        <div class="invalid-feedback" id="new_category_name_err"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" id="btnCreateCategory">حفظ</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Parent Modal --}}
    <div class="modal fade" id="createParentItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">إضافة صنف أب جديد</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label class="form-label">كود الصنف</label>
                            <input type="text" id="new_parent_code" class="form-control" placeholder="مثال: ITM-001">
                            <div class="invalid-feedback" id="new_parent_code_err"></div>
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>الباركود</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnGenerateParentBarcode">توليد</button>
                            </label>
                            <input type="text" id="new_parent_barcode" class="form-control" placeholder="سيتم توليده تلقائيًا">
                            <div class="invalid-feedback" id="new_parent_barcode_err"></div>
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">اسم الصنف</label>
                            <input type="text" id="new_parent_name" class="form-control" placeholder="مثال: كرتونة مياه">
                            <div class="invalid-feedback" id="new_parent_name_err"></div>
                        </div>
                    </div>

                    <small class="text-muted form-hint">ملاحظة: يمكن تعديل الباركود يدويًا لو احتجت.</small>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" id="btnCreateParentItem">حفظ</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        // ===== Toastr =====
        toastr.options = { closeButton: true, progressBar: true, positionClass: "toast-top-left", timeOut: "2500" };

        // ===== Barcode =====
        function generateBarcode() {
            let barcode = '20';
            for (let i = 0; i < 10; i++) barcode += Math.floor(Math.random() * 10);
            return barcode;
        }

        // ===== Select2 =====
        $(document).ready(function () {
            $('.select2').select2({ theme: 'bootstrap-5', width: '100%', dir: 'rtl', allowClear: true });
        });

        // main barcode
        document.getElementById('btnGenerateBarcodeMain')?.addEventListener('click', function () {
            document.getElementById('barcode_input').value = generateBarcode();
        });

        // ===== Image Preview =====
        const defaultImg = "{{ $defaultImg }}";
        const originalImg = "{{ $currentImg }}";

        document.getElementById('imageInput')?.addEventListener('change', function (e) {
            const file = e.target.files?.[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) { toastr.error('يرجى اختيار ملف صورة صحيح'); e.target.value=''; return; }
            if (file.size > 2 * 1024 * 1024) { toastr.error('حجم الصورة يجب ألا يتجاوز 2MB'); e.target.value=''; return; }

            const reader = new FileReader();
            reader.onload = (event) => { document.getElementById('imagePreview').src = event.target.result; };
            reader.readAsDataURL(file);
        });

        document.getElementById('btnResetImage')?.addEventListener('click', function () {
            document.getElementById('imageInput').value = '';
            document.getElementById('imagePreview').src = originalImg || defaultImg;
        });

        document.getElementById('btnClearImage')?.addEventListener('click', function () {
            document.getElementById('imageInput').value = '';
            document.getElementById('imagePreview').src = originalImg || defaultImg;
        });

        // ===== Retail toggle =====
        (function () {
            const toggle = document.getElementById('does_has_retail_unit');
            const retailUnitWrap = document.getElementById('retail_unit_wrap');
            const retailRatioWrap = document.getElementById('retail_ratio_wrap');
            const retailUnit = document.getElementById('retail_unit');
            const retailRatio = document.getElementById('retail_uom_quintToParent');

            function applyRetailUI() {
                const enabled = toggle && toggle.checked;
                if (enabled) {
                    retailUnitWrap.style.display = '';
                    retailRatioWrap.style.display = '';
                    retailUnit.setAttribute('required', 'required');
                    retailRatio.setAttribute('required', 'required');
                } else {
                    retailUnitWrap.style.display = 'none';
                    retailRatioWrap.style.display = 'none';
                    retailUnit.removeAttribute('required');
                    retailRatio.removeAttribute('required');
                    // edit: لا نفرّغ القيم تلقائيًا
                }
            }

            applyRetailUI();
            toggle?.addEventListener('change', applyRetailUI);
        })();

        // ===== Client-side Validation (before controller) =====
        function isEmpty(v){ return v === null || v === undefined || String(v).trim() === ''; }
        function getEl(name){ return document.querySelector(`[name="${name}"]`); }
        function val(name){
            const el = getEl(name);
            if(!el) return '';
            if(el.type === 'checkbox') return el.checked ? '1' : '';
            return (el.value ?? '').toString().trim();
        }
        function asNumber(name){
            const v = val(name);
            if(isEmpty(v)) return null;
            const n = Number(v);
            return Number.isFinite(n) ? n : NaN;
        }

        function setInvalid(el, msg){
            if(!el) return;
            el.classList.add('is-invalid');

            // Select2 highlight
            if(el.tagName === 'SELECT' && $(el).hasClass('select2-hidden-accessible')){
                $(el).next('.select2-container').find('.select2-selection').addClass('is-invalid');
            }

            let fb = el.parentElement?.querySelector('.invalid-feedback.js-client');
            if(!fb){
                fb = document.createElement('div');
                fb.className = 'invalid-feedback js-client';
                el.parentElement?.appendChild(fb);
            }
            fb.textContent = msg || 'قيمة غير صحيحة';
        }

        function clearInvalid(el){
            if(!el) return;
            el.classList.remove('is-invalid');

            if(el.tagName === 'SELECT' && $(el).hasClass('select2-hidden-accessible')){
                $(el).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
            }

            const fb = el.parentElement?.querySelector('.invalid-feedback.js-client');
            if(fb) fb.remove();
        }

        function scrollToFirstError(){
            const first = document.querySelector('.is-invalid');
            if(!first) return;
            first.scrollIntoView({behavior:'smooth', block:'center'});
            first.focus({preventScroll:true});
        }

        // clear invalid on input/change
        document.querySelectorAll('#itemForm input, #itemForm select').forEach(el => {
            el.addEventListener('input', () => clearInvalid(el));
            el.addEventListener('change', () => clearInvalid(el));
        });

        function validateForm(){
            let ok = true;

            const requiredFields = [
                ['items_code','كود الصنف'],
                ['barcode','الباركود'],
                ['name','اسم الصنف'],
                ['type','نوع الصنف'],
                ['item_category_id','التصنيف'],
                ['unit_id','وحدة القياس الأساسية'],

                ['price','السعر القطاعي (وحدة الأب)'],
                ['nos_egomania_price','سعر نص الجملة (وحدة الأب)'],
                ['egomania_price','سعر الجملة (وحدة الأب)'],
                ['price_retail','السعر القطاعي (التجزئة)'],
                ['nos_gomla_price_retail','سعر نص الجملة (التجزئة)'],
                ['gomla_price_retail','سعر الجملة (التجزئة)'],
            ];

            // clear old client errors
            requiredFields.forEach(([k]) => clearInvalid(getEl(k)));
            clearInvalid(getEl('retail_unit'));
            clearInvalid(getEl('retail_uom_quintToParent'));
            clearInvalid(document.getElementById('imageInput'));

            // required checks
            for(const [field, label] of requiredFields){
                const el = getEl(field);
                if(!el) continue;
                if(isEmpty(val(field))){
                    setInvalid(el, `${label} مطلوب`);
                    ok = false;
                }
            }

            // barcode format (12 digits, starts with 20)
            const barcodeEl = getEl('barcode');
            const b = val('barcode');
            if(barcodeEl && !isEmpty(b)){
                if(!/^\d{12}$/.test(b)){
                    setInvalid(barcodeEl, 'الباركود يجب أن يكون 12 رقم');
                    ok = false;
                } else if(!b.startsWith('20')){
                    setInvalid(barcodeEl, 'الباركود يجب أن يبدأ بـ 20');
                    ok = false;
                }
            }

            // numbers >= 0 for prices
            const priceFields = [
                ['price','السعر القطاعي (وحدة الأب)'],
                ['nos_egomania_price','سعر نص الجملة (وحدة الأب)'],
                ['egomania_price','سعر الجملة (وحدة الأب)'],
                ['price_retail','السعر القطاعي (التجزئة)'],
                ['nos_gomla_price_retail','سعر نص الجملة (التجزئة)'],
                ['gomla_price_retail','سعر الجملة (التجزئة)'],
            ];
            for(const [field, label] of priceFields){
                const el = getEl(field);
                if(!el) continue;
                const n = asNumber(field);
                if(n === null) continue; // already required error
                if(Number.isNaN(n)){
                    setInvalid(el, `${label} يجب أن يكون رقم`);
                    ok = false;
                    continue;
                }
                if(n < 0){
                    setInvalid(el, `${label} يجب ألا يقل عن 0`);
                    ok = false;
                }
            }

            // Retail conditional
            const retailEnabled = document.getElementById('does_has_retail_unit')?.checked;
            const retailUnitEl = getEl('retail_unit');
            const ratioEl = getEl('retail_uom_quintToParent');

            if(retailEnabled){
                if(retailUnitEl && isEmpty(val('retail_unit'))){
                    setInvalid(retailUnitEl, 'وحدة التجزئة مطلوبة عند تفعيل التجزئة');
                    ok = false;
                }

                const ratio = asNumber('retail_uom_quintToParent');
                if(ratioEl){
                    if(ratio === null){
                        setInvalid(ratioEl, 'معامل التحويل مطلوب عند تفعيل التجزئة');
                        ok = false;
                    } else if(Number.isNaN(ratio)){
                        setInvalid(ratioEl, 'معامل التحويل يجب أن يكون رقم');
                        ok = false;
                    } else if(ratio < 1){
                        setInvalid(ratioEl, 'معامل التحويل يجب ألا يقل عن 1');
                        ok = false;
                    }
                }
            } else {
                // Edit: لا نفرغ القيم (عشان ممكن يبقى عنده قيم مخزنة بس التوجل off بالخطأ)
                // لكن نزيل required فقط (تم في applyRetailUI)
            }

            // image validation (optional)
            const imgEl = document.getElementById('imageInput');
            if(imgEl && imgEl.files && imgEl.files.length){
                const f = imgEl.files[0];
                const allowed = ['image/jpeg','image/png','image/webp'];
                if(!allowed.includes(f.type)){
                    setInvalid(imgEl, 'الصورة يجب أن تكون JPG / PNG / WEBP');
                    ok = false;
                } else if(f.size > 2 * 1024 * 1024){
                    setInvalid(imgEl, 'حجم الصورة يجب ألا يتجاوز 2MB');
                    ok = false;
                }
            }

            if(!ok){
                toastr.error('تأكد من البيانات المدخلة');
                scrollToFirstError();
            }

            return ok;
        }

        // prevent double submit + validate
        $('#itemForm').on('submit', function(e){
            if(!validateForm()){
                e.preventDefault();
                return false;
            }
            const btn = $('#btnSubmit');
            btn.prop('disabled', true);
            btn.html('<i class="ti ti-loader"></i> جارٍ التحديث...');
        });

        // ===== helpers for modals validation =====
        function clearFieldError(id, errId) {
            const el = document.getElementById(id);
            const err = document.getElementById(errId);
            if (!el) return;
            el.classList.remove('is-invalid');
            if (err) err.innerText = '';
        }
        function setFieldError(id, errId, msg) {
            const el = document.getElementById(id);
            const err = document.getElementById(errId);
            if (!el) return;
            el.classList.add('is-invalid');
            if (err) err.innerText = msg || 'قيمة غير صحيحة';
        }

        // Parent modal barcode
        document.getElementById('btnGenerateParentBarcode')?.addEventListener('click', function () {
            document.getElementById('new_parent_barcode').value = generateBarcode();
        });
        document.getElementById('createParentItemModal')?.addEventListener('shown.bs.modal', function () {
            if (!document.getElementById('new_parent_barcode').value) {
                document.getElementById('new_parent_barcode').value = generateBarcode();
            }
        });

        // Add Category AJAX
        $('#btnCreateCategory').on('click', function () {
            clearFieldError('new_category_name', 'new_category_name_err');

            const name = ($('#new_category_name').val() || '').trim();
            if (!name) {
                setFieldError('new_category_name', 'new_category_name_err', 'اسم التصنيف مطلوب');
                toastr.error('تأكد من البيانات المدخلة');
                return;
            }

            const btn = $(this);
            btn.prop('disabled', true).text('...جارٍ الحفظ');

            $.ajax({
                url: "{{ route('items.ajax.categories.store') }}",
                type: "POST",
                data: { _token: "{{ csrf_token() }}", name },
                success: function (res) {
                    if (!res || !res.status) { toastr.error('تعذر إضافة التصنيف'); return; }

                    const option = new Option(res.text, res.id, true, true);
                    $('#category_select').append(option).trigger('change');

                    toastr.success('تم إضافة التصنيف بنجاح ✅');
                    $('#createCategoryModal').modal('hide');
                    $('#new_category_name').val('');
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON?.errors || {};
                        if (errors.name?.[0]) setFieldError('new_category_name', 'new_category_name_err', errors.name[0]);
                        toastr.error('تأكد من البيانات المدخلة');
                    } else {
                        toastr.error('حدث خطأ أثناء الإضافة');
                    }
                },
                complete: function () {
                    btn.prop('disabled', false).text('حفظ');
                }
            });
        });

        // Add Parent Item AJAX
        $('#btnCreateParentItem').on('click', function () {
            clearFieldError('new_parent_code', 'new_parent_code_err');
            clearFieldError('new_parent_name', 'new_parent_name_err');
            clearFieldError('new_parent_barcode', 'new_parent_barcode_err');

            const items_code = ($('#new_parent_code').val() || '').trim();
            const name = ($('#new_parent_name').val() || '').trim();
            const barcode = ($('#new_parent_barcode').val() || '').trim();

            let hasError = false;
            if (!items_code) { setFieldError('new_parent_code', 'new_parent_code_err', 'كود الصنف مطلوب'); hasError = true; }
            if (!name) { setFieldError('new_parent_name', 'new_parent_name_err', 'اسم الصنف مطلوب'); hasError = true; }
            if (barcode && !/^\d{12}$/.test(barcode)) { setFieldError('new_parent_barcode', 'new_parent_barcode_err', 'الباركود يجب أن يكون 12 رقم'); hasError = true; }

            if (hasError) { toastr.error('تأكد من البيانات المدخلة'); return; }

            const btn = $(this);
            btn.prop('disabled', true).text('...جارٍ الحفظ');

            $.ajax({
                url: "{{ route('items.ajax.parents.store') }}",
                type: "POST",
                data: { _token: "{{ csrf_token() }}", items_code, name, barcode },
                success: function (res) {
                    if (!res || !res.status) { toastr.error('تعذر إضافة الصنف الأب'); return; }

                    const option = new Option(res.text, res.id, true, true);
                    $('#parent_item_select').append(option).trigger('change');

                    toastr.success('تم إضافة الصنف الأب بنجاح ✅');

                    $('#createParentItemModal').modal('hide');
                    $('#new_parent_code').val('');
                    $('#new_parent_name').val('');
                    $('#new_parent_barcode').val('');
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON?.errors || {};
                        if (errors.items_code?.[0]) setFieldError('new_parent_code', 'new_parent_code_err', errors.items_code[0]);
                        if (errors.name?.[0]) setFieldError('new_parent_name', 'new_parent_name_err', errors.name[0]);
                        if (errors.barcode?.[0]) setFieldError('new_parent_barcode', 'new_parent_barcode_err', errors.barcode[0]);
                        toastr.error('تأكد من البيانات المدخلة');
                    } else {
                        toastr.error('حدث خطأ أثناء الإضافة');
                    }
                },
                complete: function () {
                    btn.prop('disabled', false).text('حفظ');
                }
            });
        });

        // clear invalid on typing (modals)
        $('#new_category_name').on('input', () => clearFieldError('new_category_name', 'new_category_name_err'));
        $('#new_parent_code').on('input', () => clearFieldError('new_parent_code', 'new_parent_code_err'));
        $('#new_parent_name').on('input', () => clearFieldError('new_parent_name', 'new_parent_name_err'));
        $('#new_parent_barcode').on('input', () => clearFieldError('new_parent_barcode', 'new_parent_barcode_err'));
    </script>
@endsection
