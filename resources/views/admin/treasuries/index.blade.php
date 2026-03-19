@extends('admin.layouts.master')
@section('css')

@endsection

@section('title')
    بيانات الخزن
@endsection



@section('content')

    <div class="content-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">
            بيانات الخزن
            <span class="text-muted fw-normal ms-2">
            — {{ auth()->user()->name }}
        </span>
        </h5>
    </div>


    <div class="row">
        <div class="col-12">
            @include('admin.Alerts')
            <div class="card table-card">
                <div class="card-header">
                    <a href="{{route('treasuries.create')}}" class="btn btn-sm btn-success">اضافه خزنة جديد</a>
                </div>
                <div class="card-body">
                    <!--begin::Table container-->

                        <!--begin::Table-->
                        <div class="table-responsive">
                            {!! $dataTable->table([
                                'id' => 'treasuries-table',
                                'class' => 'table table-striped table-row-bordered gy-5 gs-7 text-end'
                            ], true) !!}
                        </div>

                        <!--end::Table-->

                    <!--end::Table container-->
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    {!! $dataTable->scripts() !!}
@endsection
