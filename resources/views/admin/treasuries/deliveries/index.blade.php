@extends('admin.layouts.master')

@section('title', 'حركات الخزنة')

@section('css')
@endsection

@section('content')

    <div class="content-header d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">حركات الخزنة: {{ $treasury->name }}</h5>
            <small class="text-muted">
                الرصيد الحالي (تقريبي): <span class="num">{{ number_format($balance ?? 0, 2) }}</span>
            </small>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('shifts.create', ['treasury_id' => $treasury->id]) }}" class="btn btn-sm btn-dark">
                <i class="ti ti-play"></i>
                فتح شفت
            </a>

            <a href="{{ route('treasuries.deliveries.create', $treasury->id) }}" class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
                إضافة حركة
            </a>

            <a href="{{ route('treasuries.index') }}" class="btn btn-sm btn-light">
                <i class="ti ti-arrow-right"></i>
                رجوع للخزن
            </a>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="card table-card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="fw-semibold">قائمة الحركات</div>
            <span class="badge bg-info">ERP</span>
        </div>

        <div class="card-body">
            {{-- لو بتستخدم Yajra DataTables --}}
            @isset($dataTable)
                {!! $dataTable->table(['class' => 'table table-striped table-bordered text-end align-middle'], true) !!}
            @else
                <div class="alert alert-warning small mb-0">
                    لم يتم تمرير <code>$dataTable</code> للصفحة. لو بتستخدم DataTable Render طبيعي تجاهل الرسالة.
                </div>
            @endisset
        </div>
    </div>

@endsection

@section('js')
    @isset($dataTable)
        {!! $dataTable->scripts() !!}
    @endisset
@endsection
