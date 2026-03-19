{{-- resources/views/admin/accounts/create.blade.php --}}
@extends('admin.layouts.master')

@section('title', 'إضافة حساب مالي')

@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <style>
        .form-hint { font-size: 12px; color: #64748b; }
        .required:after { content: " *"; color: #dc2626; font-weight: 700; }

        /* Switch */
        .switch { position: relative; display: inline-block; width: 46px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; inset: 0; background: #dc2626; transition: .25s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background: #fff; transition: .25s; border-radius: 50%; }
        input:checked + .slider { background: #16a34a; }
        input:checked + .slider:before { transform: translateX(22px); }

        .invalid-feedback-js { display: block; }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

@endsection

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            {{-- Header --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-sm-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-1">إضافة حساب مالي</h4>
                            <div class="text-muted" style="font-size:12px;">
                                إنشاء حساب داخل شجرة الحسابات مع تحديد النوع والرصد الافتتاحي.
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="{{ route('accounts.index') }}" class="btn btn-light btn-sm">
                                <i class="ti ti-arrow-left"></i> رجوع
                            </a>
                            <button form="accountForm" class="btn btn-primary btn-sm">
                                <i class="ti ti-device-floppy"></i> حفظ
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Server-side errors --}}
            @if($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-bold mb-1">تحقق من البيانات:</div>
                    <ul class="mb-0">
                        @foreach($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                // يختار الأب تلقائيًا لو جاي من صفحة show
                $defaultParent = old('parent_account_id', $selectedParentId ?? request('parent_account_id'));
            @endphp

            <form id="accountForm" action="{{ route('accounts.store') }}" method="POST" novalidate>
                @csrf

                <div class="row g-3">

                    {{-- Main --}}
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">بيانات الحساب</h5>
                            </div>

                            <div class="card-body">
                                <div class="row g-3">

                                    {{-- account_type_id --}}
                                    <div class="col-md-6">
                                        <label class="form-label required" for="account_type_id">نوع الحساب</label>
                                        <select name="account_type_id" id="account_type_id"
                                                class="form-select @error('account_type_id') is-invalid @enderror">
                                            <option value="">اختر النوع</option>
                                            @foreach($types as $t)
                                                <option value="{{ $t->id }}" @selected(old('account_type_id') == $t->id)>
                                                    {{ $t->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('account_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        <div class="form-hint">تصنيف الحساب يساعد في التقارير والميزانية.</div>
                                    </div>


                                    {{-- parent_account_id --}}
                                    <div class="col-md-6">
                                        <label class="form-label" for="parent_account_id">الحساب الأب (اختياري)</label>

                                        <select name="parent_account_id"
                                                id="parent_account_id"
                                                class="form-select select2 @error('parent_account_id') is-invalid @enderror"
                                                data-placeholder="اختر الحساب الأب">

                                            <option value="">بدون (حساب رئيسي)</option>
                                            @foreach($parents as $p)
                                                <option value="{{ $p->id }}" @selected((string)$defaultParent === (string)$p->id)>
                                                    {{ $p->path }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @error('parent_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        <div class="form-hint">لو الحساب فرعي اختار الحساب الرئيسي/الأب.</div>
                                    </div>


                                    {{-- name --}}
                                    <div class="col-md-6">
                                        <label class="form-label required" for="name">اسم الحساب</label>
                                        <input type="text"
                                               name="name" id="name"
                                               value="{{ old('name') }}"
                                               class="form-control @error('name') is-invalid @enderror"
                                               placeholder="مثال: الصندوق / البنك / العملاء ...">
                                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        <div class="form-hint">يفضل أن يكون الاسم واضحًا ويعبر عن طبيعة الحساب.</div>
                                    </div>



                                    {{-- start_balance --}}
                                    <div class="col-md-6">
                                        <label class="form-label" for="start_balance">الرصيد الافتتاحي</label>
                                        <input type="number"
                                               step="0.01"
                                               name="start_balance" id="start_balance"
                                               value="{{ old('start_balance') }}"
                                               class="form-control @error('start_balance') is-invalid @enderror"
                                               placeholder="0.00">
                                        @error('start_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        <div class="form-hint">اختياري. سيتم ضبط الرصيد الحالي تلقائيًا.</div>
                                    </div>

                                    {{-- current_balance readonly --}}
                                    <div class="col-md-6">
                                        <label class="form-label">الرصيد الحالي</label>
                                        <input type="text" class="form-control" value="يتحدد تلقائيًا" readonly>
                                        <div class="form-hint">يتغير لاحقًا حسب القيود والحركات.</div>
                                    </div>

                                    {{-- other_table_id --}}
                                    <div class="col-md-6">
                                        <label class="form-label" for="other_table_id">مرجع خارجي (اختياري)</label>
                                        <input type="text"
                                               name="other_table_id" id="other_table_id"
                                               value="{{ old('other_table_id') }}"
                                               class="form-control @error('other_table_id') is-invalid @enderror"
                                               placeholder="مثال: customers:15 أو suppliers:8">
                                        @error('other_table_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        <div class="form-hint">لربط الحساب بكيان آخر (عميل/مورد/خزنة...).</div>
                                    </div>

                                    {{-- date --}}
                                    <div class="col-md-6">
                                        <label class="form-label" for="date">تاريخ</label>
                                        <input type="date"
                                               name="date" id="date"
                                               value="{{ old('date') }}"
                                               class="form-control @error('date') is-invalid @enderror">
                                        @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    {{-- notes --}}
                                    <div class="col-12">
                                        <label class="form-label" for="notes">ملاحظات</label>
                                        <textarea name="notes" id="notes" rows="4"
                                                  class="form-control @error('notes') is-invalid @enderror"
                                                  placeholder="أي ملاحظات محاسبية أو تشغيلية تخص الحساب...">{{ old('notes') }}</textarea>
                                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Sidebar --}}
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">إعدادات</h5>
                            </div>
                            <div class="card-body">

                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <div class="fw-bold">الحالة</div>
                                        <div class="form-hint">تفعيل الحساب يسمح باستخدامه في القيود.</div>
                                    </div>

                                    <label class="switch m-0">
                                        <input type="checkbox" name="status" value="1" {{ old('status') ? 'checked' : '' }}>
                                        <span class="slider round"></span>
                                    </label>
                                </div>

                                <hr>

                                <div class="alert alert-info mb-0" style="font-size: 13px;">
                                    <div class="fw-bold mb-1">تنبيه محاسبي</div>
                                    <div>
                                        لا يُنصح بحذف الحسابات بعد استخدامها في القيود.
                                        الأفضل إيقاف الحساب بدل الحذف.
                                    </div>
                                </div>

                                <div class="d-flex gap-2 mt-3">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="ti ti-device-floppy"></i> حفظ
                                    </button>
                                    <a href="{{ route('accounts.index') }}" class="btn btn-light w-100">
                                        إلغاء
                                    </a>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </form>

        </div>
    </div>
@endsection

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        $(function () {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dir: 'rtl',
                allowClear: true,
                placeholder: function () {
                    return $(this).data('placeholder') || 'اختر';
                }
            });
        });
    </script>


    <script>
        @if(session('success')) toastr.success(@json(session('success'))); @endif
        @if(session('error')) toastr.error(@json(session('error'))); @endif

        document.addEventListener('DOMContentLoaded', function () {

            const form = document.getElementById('accountForm');

            function showError(input, message) {
                input.classList.add('is-invalid');

                let feedback = input.parentElement.querySelector('.invalid-feedback-js');
                if (!feedback) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback invalid-feedback-js';
                    input.parentElement.appendChild(feedback);
                }
                feedback.innerText = message;
            }

            function clearError(input) {
                input.classList.remove('is-invalid');
                const feedback = input.parentElement.querySelector('.invalid-feedback-js');
                if (feedback) feedback.remove();
            }

            function isNumeric(val) {
                if (val === '' || val === null || val === undefined) return true;
                return !isNaN(val) && isFinite(val);
            }

            function validateField(input) {
                const name = input.name;
                const value = (input.value ?? '').toString().trim();

                clearError(input);

                if (name === 'account_type_id' && !value) {
                    showError(input, 'نوع الحساب مطلوب');
                    return false;
                }

                if (name === 'name') {
                    if (!value) { showError(input, 'اسم الحساب مطلوب'); return false; }
                    if (value.length < 3) { showError(input, 'اسم الحساب يجب أن يكون 3 أحرف على الأقل'); return false; }
                    if (value.length > 255) { showError(input, 'اسم الحساب لا يجب أن يتجاوز 255 حرفًا'); return false; }
                }

                if (name === 'start_balance' && value !== '') {
                    if (!isNumeric(value)) { showError(input, 'الرصيد الافتتاحي يجب أن يكون رقمًا'); return false; }
                }

                if (name === 'account_number' && value.length > 255) {
                    showError(input, 'رقم الحساب لا يجب أن يتجاوز 255 حرفًا');
                    return false;
                }

                if (name === 'other_table_id' && value.length > 255) {
                    showError(input, 'المرجع الخارجي لا يجب أن يتجاوز 255 حرفًا');
                    return false;
                }

                if (name === 'date' && value !== '') {
                    const ok = /^\d{4}-\d{2}-\d{2}$/.test(value);
                    if (!ok) { showError(input, 'صيغة التاريخ غير صحيحة'); return false; }
                }

                return true;
            }

            form.querySelectorAll('input, select, textarea').forEach(input => {
                input.addEventListener('input', () => validateField(input));
                input.addEventListener('change', () => validateField(input));
                input.addEventListener('blur', () => validateField(input));
            });

            form.addEventListener('submit', function (e) {
                let isValid = true;

                const fields = form.querySelectorAll('input[name], select[name], textarea[name]');
                fields.forEach(input => {
                    if (!validateField(input)) isValid = false;
                });

                if (!isValid) {
                    e.preventDefault();
                    toastr.error('تحقق من البيانات المدخلة قبل الحفظ');
                    const firstInvalid = form.querySelector('.is-invalid');
                    if (firstInvalid) firstInvalid.focus();
                }
            });
        });
    </script>
@endsection
