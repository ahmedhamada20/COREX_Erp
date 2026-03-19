@extends('admin.layouts.master')

@section('title', 'تعديل وحدة')

@section('content')

    <div class="content-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تعديل وحدة</h5>

            <a href="{{ route('units.index') }}" class="btn btn-sm btn-secondary">
                <i class="ti ti-arrow-left"></i> رجوع
            </a>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="row">
        <div class="col-md-12">

            <form action="{{ route('units.update', $unit->id) }}" method="POST">
                @csrf
                @method('PUT')

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
                                       value="{{ old('name', $unit->name) }}"
                                       required>
                            </div>

                            {{-- وحدة رئيسية --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">نوع الوحدة</label>

                                @php
                                    // لو فيه وحدة رئيسية غير الحالية → مينفعش تختار "رئيسية" هنا
                                    $disableMasterSelect = $hasMaster && !$unit->is_master;
                                @endphp

                                <select name="is_master" class="form-select" {{ $disableMasterSelect ? 'disabled' : '' }}>
                                    <option value="0" {{ old('is_master', (int)$unit->is_master) == 0 ? 'selected' : '' }}>
                                        وحدة فرعية
                                    </option>
                                    <option value="1" {{ old('is_master', (int)$unit->is_master) == 1 ? 'selected' : '' }}>
                                        وحدة رئيسية
                                    </option>
                                </select>

                                @if($disableMasterSelect)
                                    <small class="text-muted">
                                        يوجد وحدة رئيسية بالفعل، لا يمكن تعيين هذه الوحدة كوحدة رئيسية
                                    </small>
                                @elseif($unit->is_master)
                                    <small class="text-muted">
                                        هذه هي الوحدة الرئيسية الحالية
                                    </small>
                                @endif
                            </div>

                            {{-- العنوان --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">العنوان</label>
                                <input type="text"
                                       name="address"
                                       class="form-control"
                                       value="{{ old('address', $unit->address) }}">
                            </div>

                            {{-- الهاتف --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">رقم الهاتف</label>
                                <input type="text"
                                       name="phone"
                                       class="form-control"
                                       value="{{ old('phone', $unit->phone) }}">
                            </div>

                            {{-- التاريخ --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">التاريخ</label>
                                <input type="date"
                                       name="date"
                                       class="form-control"
                                       value="{{ old('date', $unit->date ? \Carbon\Carbon::parse($unit->date)->format('Y-m-d') : null) }}">
                            </div>

                            {{-- الحالة --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الحالة</label>
                                <select name="status" class="form-select">
                                    <option value="1" {{ old('status', (int)$unit->status) == 1 ? 'selected' : '' }}>نشطة</option>
                                    <option value="0" {{ old('status', (int)$unit->status) == 0 ? 'selected' : '' }}>غير نشطة</option>
                                </select>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy"></i> تحديث
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

@endsection
