@extends('admin.layouts.master')

@section('title', 'إضافة فئة مواد مبيعات')

@section('content')

    <div class="content-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">إضافة فئة مواد مبيعات</h5>

            <a href="{{ route('sales_material_types.index') }}" class="btn btn-sm btn-light">
                رجوع
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-6">

            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">بيانات الفئة</h6>
                </div>

                <div class="card-body">
                    <form action="{{ route('sales_material_types.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">الاسم</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">التاريخ</label>
                            <input type="date" name="date" value="{{ old('date') }}" class="form-control">
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="status" value="1" id="status"
                                @checked(old('status'))>
                            <label class="form-check-label" for="status">
                                نشط
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            حفظ
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

@endsection
