@extends('admin.layouts.master')

@section('title', 'تعديل فئة موردين')

@section('content')

    <div class="content-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تعديل فئة موردين</h5>

            <a href="{{ route('supplier_categories.index') }}" class="btn btn-sm btn-light">
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
                    <h6 class="mb-0">بيانات فئة الموردين</h6>
                </div>

                <div class="card-body">
                    <form action="{{ route('supplier_categories.update', $item->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- اسم الفئة --}}
                        <div class="mb-3">
                            <label class="form-label">اسم الفئة</label>
                            <input type="text"
                                   name="name"
                                   value="{{ old('name', $item->name) }}"
                                   class="form-control"
                                   required>
                        </div>

                        {{-- التاريخ --}}
                        <div class="mb-3">
                            <label class="form-label">التاريخ</label>
                            <input type="date"
                                   name="date"
                                   value="{{ old('date', $item->date ? \Carbon\Carbon::parse($item->date)->format('Y-m-d') : '') }}"
                                   class="form-control">
                        </div>

                        {{-- الحالة --}}
                        <div class="form-check mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="status"
                                   value="1"
                                   id="status"
                                @checked(old('status', $item->status))>
                            <label class="form-check-label" for="status">
                                نشط
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            تحديث
                        </button>

                    </form>
                </div>
            </div>

        </div>
    </div>

@endsection
