@extends('admin.layouts.master')

@section('title', 'تعديل نوع حساب')

@section('content')

    <div class="content-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تعديل نوع حساب</h5>

            <a href="{{ route('account_types.index') }}" class="btn btn-sm btn-light">
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">بيانات نوع الحساب</h6>

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
                    <form action="{{ route('account_types.update', $accountType->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">الاسم <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="name"
                                   value="{{ old('name', $accountType->name) }}"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الكود</label>
                            <input type="text"
                                   name="code"
                                   value="{{ old('code', $accountType->code) }}"
                                   class="form-control"
                                   placeholder="مثال: AST / LIA / REV ...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">التاريخ</label>
                            <input type="date"
                                   name="date"
                                   value="{{ old('date', optional($accountType->date)->format('Y-m-d')) }}"
                                   class="form-control">
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="allow_posting"
                                   value="1"
                                   id="allow_posting"
                                @checked(old('allow_posting', (int) $accountType->allow_posting))>
                            <label class="form-check-label" for="allow_posting">
                                يقبل حركة مباشرة؟ (Allow Posting)
                            </label>
                        </div>
                        <small class="text-muted d-block mb-3">
                            لو غير مفعل: النوع يكون تجميعي فقط (Grouping).
                        </small>

                        <div class="form-check mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="status"
                                   value="1"
                                   id="status"
                                @checked(old('status', (int) $accountType->status))>
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
