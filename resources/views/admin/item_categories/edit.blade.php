@extends('admin.layouts.master')

@section('title', 'تعديل فئة')

@section('content')

    <div class="content-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تعديل الفئة</h5>

            <a href="{{ route('item_categories.index') }}" class="btn btn-sm btn-light">
                رجوع
            </a>
        </div>
    </div>

    @include('admin.Alerts')




    <div class="row">
        <div class="col-md-12">

            <form action="{{ route('item_categories.update', $itemCategory->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">بيانات الفئة</h6>
                    </div>

                    <div class="card-body">
                        @include('admin.item_categories._form', ['item' => $itemCategory])
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
