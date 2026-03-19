@extends('admin.layouts.master')

@section('title', 'عرض فئة')

@section('content')

    <div class="content-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">عرض الفئة</h5>

            <div class="d-flex gap-2">
                <a href="{{ route('item_categories.edit', $itemCategory->id) }}" class="btn btn-sm btn-success">
                    <i class="fa fa-edit"></i> تعديل
                </a>

                <a href="{{ route('item_categories.index') }}" class="btn btn-sm btn-light">
                    رجوع
                </a>
            </div>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="row">
        <div class="col-12">
            <div class="card table-card">
                <div class="card-header">
                    <h6 class="mb-0">تفاصيل الفئة</h6>
                </div>

                <div class="card-body">
                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <div class="fw-bold">الاسم</div>
                            <div>{{ $itemCategory->name }}</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="fw-bold">رقم الهاتف</div>
                            <div>{{ $itemCategory->phone ?? '-' }}</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="fw-bold">العنوان</div>
                            <div>{{ $itemCategory->address ?? '-' }}</div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="fw-bold">التاريخ</div>
                            <div>{{ $itemCategory->date ? \Carbon\Carbon::parse($itemCategory->date)->format('Y-m-d') : '-' }}</div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="fw-bold">الحالة</div>
                            <div>
                                @if($itemCategory->status)
                                    <span class="badge bg-success">نشط</span>
                                @else
                                    <span class="badge bg-danger">غير نشط</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="fw-bold">آخر تعديل بواسطة</div>
                            <div>
                                @if($itemCategory->updated_by && $itemCategory->updated_at)
                                    {{ $itemCategory->updated_by }}
                                    <br>
                                    <small class="text-muted">
                                        {{ $itemCategory->updated_at->timezone('Africa/Cairo')->format('Y-m-d h:i A') }}
                                    </small>
                                @else
                                    -
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="fw-bold">تاريخ الإنشاء</div>
                            <div>
                                @if($itemCategory->created_at)
                                    {{ $itemCategory->created_at->timezone('Africa/Cairo')->format('Y-m-d h:i A') }}
                                @else
                                    -
                                @endif
                            </div>
                        </div>

                    </div>

                    <div class="d-flex gap-2 mt-2">
                        <a href="{{ route('item_categories.edit', $itemCategory->id) }}" class="btn btn-sm btn-success">
                            <i class="fa fa-edit"></i> تعديل
                        </a>

                        <form action="{{ route('item_categories.destroy', $itemCategory->id) }}"
                              method="POST"
                              onsubmit="return confirm('هل أنت متأكد من حذف هذه الفئة؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fa fa-trash"></i> حذف
                            </button>
                        </form>

                        <a href="{{ route('item_categories.index') }}" class="btn btn-sm btn-light">
                            رجوع
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection
