@extends('admin.layouts.master')

@section('title', 'إضافة فئة')

@section('content')

    <div class="content-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">إضافة فئة جديدة</h5>

            <a href="{{ route('item_categories.index') }}" class="btn btn-sm btn-light">
                رجوع
            </a>
        </div>
    </div>

    @include('admin.Alerts')



    <div class="row">
        <div class="col-md-12">

            <form action="{{ route('item_categories.store') }}" method="POST">
                @csrf

                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">بيانات الفئة</h6>
                    </div>

                    <div class="card-body">
                        @include('admin.item_categories._form')
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
