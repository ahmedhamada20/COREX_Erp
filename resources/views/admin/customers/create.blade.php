@extends('admin.layouts.master')

@section('title', 'إضافة عميل')

@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        .form-hint { font-size: 12px; color: #64748b; }
        .required:after { content: " *"; color: #dc2626; font-weight: 700; }

        /* Switch */
        .switch { position: relative; display: inline-block; width: 46px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #dc3545; transition: .4s; border-radius: 24px;
        }
        .slider:before {
            position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px;
            background-color: white; transition: .4s; border-radius: 50%;
        }
        input:checked + .slider { background-color: #198754; }
        input:checked + .slider:before { transform: translateX(22px); }

        /* Preview */
        .img-preview {
            width: 96px; height: 96px; border-radius: 16px; object-fit: cover;
            border: 1px solid #e5e7eb; background: #f8fafc;
        }
        .card-header h6 { margin: 0; }
    </style>
@endsection

@section('content')

    <div class="content-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">إضافة عميل</h5>
                <small class="text-muted">إنشاء بطاقة عميل + بيانات محاسبية (رصيد افتتاحي)</small>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('customers.index') }}" class="btn btn-sm btn-light">رجوع</a>
                <button form="customerForm" class="btn btn-sm btn-primary">
                    <i class="ti ti-plus"></i>
                    حفظ العميل
                </button>
            </div>
        </div>
    </div>

    @include('admin.Alerts')



    <form id="customerForm" action="{{ route('customers.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row">

            {{-- Right: Main Data --}}
            <div class="col-lg-8 mb-3">
                {{-- Basic Info --}}
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6>البيانات الأساسية</h6>
                        <span class="badge bg-light text-dark">Customer Card</span>
                    </div>
                    <div class="card-body">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">اسم العميل</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" placeholder="مثال: أحمد علي">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-hint">اكتب الاسم التجاري/الشخصي كما يظهر في الفواتير.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">رقم الهاتف</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone') }}" placeholder="01xxxxxxxxx">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">البريد الإلكتروني</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}" placeholder="customer@email.com">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">المدينة</label>
                                <input type="text" name="city" class="form-control @error('city') is-invalid @enderror"
                                       value="{{ old('city') }}" placeholder="القاهرة / الجيزة ...">
                                @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Accounting --}}
                <div class="card mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6>البيانات المحاسبية</h6>
                        <span class="badge bg-warning-subtle text-warning">Accounting</span>
                    </div>
                    <div class="card-body">

                        <div class="row g-3">



                            <div class="col-md-6">
                                <label class="form-label">تاريخ فتح الحساب</label>
                                <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                                       value="{{ old('date', now()->toDateString()) }}">
                                @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-hint">يفضل تاريخ بداية التعامل المحاسبي مع العميل.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">الرصيد الافتتاحي</label>
                                <input type="number" step="0.01" name="start_balance"
                                       class="form-control @error('start_balance') is-invalid @enderror"
                                       value="{{ old('start_balance', 0) }}">
                                @error('start_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-hint">
                                    ملاحظة محاسبية: موجب = العميل <b>مدين</b> (له عليك) ، سالب = العميل <b>دائن</b> (عليه لك).
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">الرصيد الحالي (اختياري)</label>
                                <input type="number" step="0.01" name="current_balance" readonly
                                       class="form-control @error('current_balance') is-invalid @enderror"
                                       value="{{ old('current_balance') }}"
                                       placeholder="سيُملأ تلقائيًا مثل الافتتاحي لو تركته فارغ">
                                @error('current_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-hint">يفضل تركه فارغ كبداية (النظام يساويه بالرصيد الافتتاحي).</div>
                            </div>

                        </div>

                    </div>
                </div>

                {{-- Notes --}}
                <div class="card mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6>ملاحظات</h6>
                        <span class="badge bg-light text-dark">Optional</span>
                    </div>
                    <div class="card-body">
                        <textarea name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror"
                                  placeholder="أي ملاحظات تخص العميل (عنوان، بيانات ضريبية، شروط دفع...)">{{ old('notes') }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-hint">مثال: شروط دفع 30 يوم / حد ائتماني / ملاحظات فاتورة.</div>
                    </div>
                </div>

            </div>

            {{-- Left: Side Card --}}
            <div class="col-lg-4 mb-3">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6>الإعدادات</h6>
                        <span class="badge bg-success-subtle text-success">Status</span>
                    </div>
                    <div class="card-body">

                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <label class="form-label mb-1">الحالة</label>
                                <div class="form-hint">نشط يظهر في الفواتير والمعاملات.</div>
                            </div>

                            <label class="switch">
                                <input type="checkbox" name="status" value="1" {{ old('status', 1) ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>

                        <hr>

                        <div class="mb-2">
                            <label class="form-label">صورة العميل (اختياري)</label>
                            <input type="file" name="image" id="imageInput"
                                   class="form-control @error('image') is-invalid @enderror"
                                   accept="image/*">
                            @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-hint">JPG / PNG / WEBP — حد أقصى 2MB.</div>
                        </div>

                        <div class="d-flex align-items-center gap-3 mt-3">
                            <img id="imgPreview" class="img-preview" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='96' height='96'%3E%3Crect width='100%25' height='100%25' fill='%23f1f5f9'/%3E%3Ctext x='50%25' y='52%25' dominant-baseline='middle' text-anchor='middle' fill='%2364748b' font-size='10'%3ENo Image%3C/text%3E%3C/svg%3E" alt="preview">
                            <div>
                                <div class="fw-semibold">معاينة الصورة</div>
                                <div class="text-muted" style="font-size: 12px;">هتظهر في بطاقة العميل (اختياري).</div>
                            </div>
                        </div>

                        <hr>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy"></i>
                                حفظ العميل
                            </button>
                            <a href="{{ route('customers.index') }}" class="btn btn-light">إلغاء</a>
                        </div>
                    </div>
                </div>

                {{-- Accounting Tip --}}
                <div class="alert alert-warning mt-3 mb-0">
                    <div class="fw-semibold mb-1">تنبيه محاسبي</div>
                    <div style="font-size: 13px;">
                        الأفضل عمليًا أن <b>الرصيد الحالي</b> يتحدث من القيود/الفواتير،
                        ويكون تخزينه فقط كـ Cache لو عندك تحديث تلقائي مع كل حركة.
                    </div>
                </div>
            </div>

        </div>
    </form>

@endsection

@section('js')
    <script>
        // Image Preview
        const input = document.getElementById('imageInput');
        const preview = document.getElementById('imgPreview');

        if (input) {
            input.addEventListener('change', function () {
                const file = this.files && this.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = (e) => preview.src = e.target.result;
                reader.readAsDataURL(file);
            });
        }
    </script>
@endsection
