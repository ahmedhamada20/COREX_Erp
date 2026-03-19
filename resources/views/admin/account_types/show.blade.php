@extends('admin.layouts.master')

@section('title', 'عرض نوع حساب')

@section('content')

    <div class="content-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">عرض نوع حساب</h5>

            <div class="d-flex gap-2">
                <a href="{{ route('account_types.edit', $accountType->id) }}" class="btn btn-sm btn-success">
                    <i class="fa fa-edit"></i> تعديل
                </a>

                <a href="{{ route('account_types.index') }}" class="btn btn-sm btn-light">
                    رجوع
                </a>
            </div>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="row">
        <div class="col-lg-8">

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">تفاصيل نوع الحساب</h6>

                    <div>
                        @if($accountType->status)
                            <span class="badge bg-success">نشط</span>
                        @else
                            <span class="badge bg-danger">غير نشط</span>
                        @endif

                        @if($accountType->allow_posting)
                            <span class="badge bg-info">يقبل حركة</span>
                        @else
                            <span class="badge bg-secondary">تجميعي</span>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <tbody>
                            <tr>
                                <th style="width: 220px">الاسم</th>
                                <td>{{ $accountType->name }}</td>
                            </tr>
                            <tr>
                                <th>الكود</th>
                                <td>{{ $accountType->code ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>التاريخ</th>
                                <td>{{ $accountType->date ? \Carbon\Carbon::parse($accountType->date)->format('Y-m-d') : '-' }}</td>
                            </tr>
                            <tr>
                                <th>آخر تعديل بواسطة</th>
                                <td>{{ $accountType->updated_by ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>تاريخ الإنشاء</th>
                                <td>{{ optional($accountType->created_at)->format('Y-m-d H:i') }}</td>
                            </tr>
                            <tr>
                                <th>آخر تحديث</th>
                                <td>{{ optional($accountType->updated_at)->format('Y-m-d H:i') }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <form action="{{ route('account_types.destroy', $accountType->id) }}"
                              method="POST"
                              onsubmit="return confirm('هل أنت متأكد من حذف نوع الحساب؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fa fa-trash"></i> حذف
                            </button>
                        </form>

                        <a href="{{ route('account_types.edit', $accountType->id) }}" class="btn btn-success btn-sm">
                            <i class="fa fa-edit"></i> تعديل
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>

@endsection
