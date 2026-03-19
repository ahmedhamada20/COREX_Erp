@extends('admin.layouts.master')

@section('title', 'إضافة نوع حساب')

@section('content')

    <div class="content-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">إضافة نوع حساب</h5>

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
                <div class="card-header">
                    <h6 class="mb-0">بيانات نوع الحساب</h6>
                </div>

                <div class="card-body">
                    <form action="{{ route('account_types.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">الاسم <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="name"
                                   value="{{ old('name') }}"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الكود</label>
                            <input type="text"
                                   name="code"
                                   value="{{ old('code') }}"
                                   class="form-control"
                                   placeholder="مثال: AST / LIA / REV ...">
                            <small class="text-muted">اختياري — لو بتستخدم أكواد محاسبية.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">التاريخ</label>
                            <input type="date"
                                   name="date"
                                   value="{{ old('date') }}"
                                   class="form-control">
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="allow_posting"
                                   value="1"
                                   id="allow_posting"
                                @checked(old('allow_posting', 1))>
                            <label class="form-check-label" for="allow_posting">
                                يقبل حركة مباشرة؟ (Allow Posting)
                            </label>
                        </div>
                        <small class="text-muted d-block mb-3">
                            لو غير مفعل: النوع يكون تجميعي فقط (Grouping) ومينفعش تعمل عليه قيود مباشرة.
                        </small>

                        <div class="form-check mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="status"
                                   value="1"
                                   id="status"
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
@section('js')
<script>
    document.querySelector('form').addEventListener('submit', function(e) {

        let name = document.querySelector('[name="name"]').value.trim();

        if (!name) {
            e.preventDefault();
            alert("اسم نوع الحساب مطلوب.");
            return false;
        }

    });
</script>

@endsection
