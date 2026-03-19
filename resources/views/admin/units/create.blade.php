@extends('admin.layouts.master')

@section('title', 'إضافة وحدة')

@section('content')

    <div class="content-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">إضافة وحدة جديدة</h5>

            <a href="{{ route('units.index') }}" class="btn btn-sm btn-secondary">
                <i class="ti ti-arrow-left"></i> رجوع
            </a>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="row">
        <div class="col-md-12">

            <form action="{{ route('units.store') }}" method="POST">
                @csrf

                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">بيانات الوحدة</h6>
                    </div>

                    <div class="card-body">
                        <div class="row">

                            {{-- الاسم --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">اسم الوحدة <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="name"
                                       class="form-control"
                                       value="{{ old('name') }}"
                                       required>
                            </div>

                            {{-- وحدة رئيسية --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">نوع الوحدة</label>
                                <select name="is_master" class="form-select"
                                    {{ $hasMaster ? 'disabled' : '' }}>
                                    <option value="0">وحدة فرعية</option>
                                    <option value="1">وحدة رئيسية</option>
                                </select>

                                @if($hasMaster)
                                    <small class="text-muted">
                                        يوجد وحدة رئيسية بالفعل، لا يمكن إضافة وحدة رئيسية جديدة
                                    </small>
                                @endif
                            </div>

                            {{-- العنوان --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">العنوان</label>
                                <input type="text"
                                       name="address"
                                       class="form-control"
                                       value="{{ old('address') }}">
                            </div>

                            {{-- الهاتف --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">رقم الهاتف</label>
                                <input type="text"
                                       name="phone"
                                       class="form-control"
                                       value="{{ old('phone') }}">
                            </div>

                            {{-- التاريخ --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">التاريخ</label>
                                <input type="date"
                                       name="date"
                                       class="form-control"
                                       value="{{ old('date') }}">
                            </div>

                            {{-- الحالة --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الحالة</label>
                                <select name="status" class="form-select">
                                    <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>نشطة</option>
                                    <option value="0" {{ old('status') == 0 ? 'selected' : '' }}>غير نشطة</option>
                                </select>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy"></i> حفظ
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

@endsection
