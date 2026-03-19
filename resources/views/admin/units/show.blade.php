@extends('admin.layouts.master')

@section('title', 'عرض وحدة')

@section('content')

    <div class="content-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تفاصيل الوحدة</h5>

            <div class="d-flex gap-2">
                <a href="{{ route('units.edit', $unit->id) }}" class="btn btn-sm btn-success">
                    <i class="ti ti-edit"></i> تعديل
                </a>

                <a href="{{ route('units.index') }}" class="btn btn-sm btn-secondary">
                    <i class="ti ti-arrow-left"></i> رجوع
                </a>
            </div>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="row">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">بيانات الوحدة</h6>
                </div>

                <div class="card-body">
                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <div class="fw-bold mb-1">الاسم</div>
                            <div>{{ $unit->name }}</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="fw-bold mb-1">نوع الوحدة</div>
                            <div>
                                @if($unit->is_master)
                                    <span class="badge bg-primary">رئيسية</span>
                                @else
                                    <span class="badge bg-secondary">فرعية</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="fw-bold mb-1">العنوان</div>
                            <div>{{ $unit->address ?? '-' }}</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="fw-bold mb-1">رقم الهاتف</div>
                            <div>{{ $unit->phone ?? '-' }}</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="fw-bold mb-1">التاريخ</div>
                            <div>{{ $unit->date ? \Carbon\Carbon::parse($unit->date)->format('Y-m-d') : '-' }}</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="fw-bold mb-1">الحالة</div>
                            <div>
                                @if($unit->status)
                                    <span class="badge bg-success">نشطة</span>
                                @else
                                    <span class="badge bg-danger">غير نشطة</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="fw-bold mb-1">آخر تعديل بواسطة</div>
                            <div>{{ $unit->updated_by ?? '-' }}</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="fw-bold mb-1">تاريخ الإضافة</div>
                            <div>{{ $unit->created_at ? $unit->created_at->format('Y-m-d H:i') : '-' }}</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="fw-bold mb-1">آخر تحديث</div>
                            <div>{{ $unit->updated_at ? $unit->updated_at->format('Y-m-d H:i') : '-' }}</div>
                        </div>

                    </div>
                </div>

                <div class="card-footer text-end">
                    <a href="{{ route('units.edit', $unit->id) }}" class="btn btn-success">
                        <i class="ti ti-edit"></i> تعديل
                    </a>
                </div>
            </div>

        </div>
    </div>

@endsection
