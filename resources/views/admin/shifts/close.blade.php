@extends('admin.layouts.master')

@section('title', 'قفل الشفت')

@section('css')
@endsection

@section('content')

    <div class="content-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">
            قفل الشفت
            <span class="text-muted fw-normal ms-2">— {{ auth()->user()->name }}</span>
        </h5>

        <a href="{{ route('shifts.index') }}" class="btn btn-sm btn-light">
            <i class="ti ti-arrow-right"></i>
            رجوع
        </a>
    </div>

    <div class="row">
        <div class="col-12">

            @include('admin.Alerts')

            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="fw-semibold">تفاصيل الشفت</div>
                    <span class="badge bg-warning text-dark">مفتوح</span>
                </div>

                <div class="card-body">

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="p-3 rounded border bg-light">
                                <div class="text-muted small">الخزنة</div>
                                <div class="fw-semibold">{{ $shift->treasury?->name ?? '-' }}</div>
                                <div class="text-muted small">#{{ $shift->treasury_id }}</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 rounded border bg-light">
                                <div class="text-muted small">المستخدم</div>
                                <div class="fw-semibold">{{ $shift->actor?->name ?? '-' }}</div>
                                <div class="text-muted small">#{{ $shift->actor_user_id }}</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 rounded border bg-light">
                                <div class="text-muted small">وقت الفتح</div>
                                <div class="fw-semibold">
                                    {{ optional($shift->opened_at)->timezone('Africa/Cairo')->format('Y-m-d h:i A') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ملخص --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="p-3 rounded border">
                                <div class="text-muted small">Opening</div>
                                <div class="fw-semibold num">{{ number_format((float)$shift->opening_balance, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded border">
                                <div class="text-muted small">Expected</div>
                                <div class="fw-semibold num">{{ number_format((float)$shift->closing_expected, 2) }}</div>
                                <div class="text-muted small">سيُعاد حسابه عند الإقفال</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info small mb-0">
                                <i class="ti ti-info-circle"></i>
                                أدخل الرصيد الفعلي الموجود بالخزنة الآن (بعد العد)، وسيحسب النظام الفرق تلقائيًا.
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('shifts.close') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label" for="closing_actual">الرصيد الفعلي عند الإقفال</label>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   name="closing_actual"
                                   id="closing_actual"
                                   class="form-control @error('closing_actual') is-invalid @enderror"
                                   value="{{ old('closing_actual') }}"
                                   placeholder="مثال: 5300.00"
                                   required>

                            @error('closing_actual')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <div class="form-text">
                                بعد الإقفال سيتم تسجيل Expected/Actual/Difference داخل الشفت.
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('shifts.index') }}" class="btn btn-light">إلغاء</a>
                            <button type="submit" class="btn btn-warning">
                                <i class="ti ti-lock"></i>
                                قفل الشفت
                            </button>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>

@endsection

@section('js')
@endsection
