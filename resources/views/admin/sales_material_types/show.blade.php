@extends('admin.layouts.master')

@section('title', 'تفاصيل فئة مواد مبيعات')

@section('content')

    <div class="content-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تفاصيل فئة مواد مبيعات</h5>

            <div class="d-flex gap-2">
                <a href="{{ route('sales_material_types.edit', $item->id) }}" class="btn btn-sm btn-success">
                    تعديل
                </a>
                <a href="{{ route('sales_material_types.index') }}" class="btn btn-sm btn-light">
                    رجوع
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">

            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">بيانات الفئة</h6>
                </div>

                <div class="card-body">
                    <div class="mb-2"><strong>الاسم:</strong> {{ $item->name }}</div>
                    <div class="mb-2"><strong>التاريخ:</strong> {{ $item->date ? \Carbon\Carbon::parse($item->date)->format('Y-m-d') : '-' }}</div>
                    <div class="mb-2"><strong>الحالة:</strong> {{ $item->status ? 'نشط' : 'غير نشط' }}</div>
                    <div class="mb-2"><strong>آخر تعديل بواسطة:</strong> {{ $item->updated_by ?? '-' }}</div>
                    <div class="mb-2"><strong>تاريخ الإنشاء:</strong> {{ $item->created_at?->format('Y-m-d H:i') }}</div>
                    <div class="mb-2"><strong>آخر تحديث:</strong> {{ $item->updated_at?->format('Y-m-d H:i') }}</div>
                </div>
            </div>

        </div>
    </div>

@endsection
