@extends('admin.layouts.master')

@section('title', 'إضافة أصل ثابت')

@section('content')
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h4 class="mb-0">إضافة أصل ثابت</h4>
            <a href="{{ route('fixed_assets.index') }}" class="btn btn-light btn-sm">عودة</a>
        </div>
    </div>

    @include('admin.Alerts')

    <form method="POST" action="{{ route('fixed_assets.store') }}">
        @csrf

        <div class="card">
            <div class="card-body row g-2">
                <div class="col-md-3">
                    <label class="form-label">الكود</label>
                    <input type="text" name="asset_code" class="form-control" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">اسم الأصل</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">أصل أب (شجرة)</label>
                    <select name="parent_id" class="form-select">
                        <option value="">بدون</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->asset_code }} - {{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">حساب الأصل</label>
                    <select name="asset_account_id" class="form-select">
                        <option value="">اختر</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->account_number }} - {{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">حساب مجمع الإهلاك</label>
                    <select name="accumulated_depreciation_account_id" class="form-select">
                        <option value="">اختر</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->account_number }} - {{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">حساب مصروف الإهلاك</label>
                    <select name="depreciation_expense_account_id" class="form-select">
                        <option value="">اختر</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->account_number }} - {{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">تاريخ الشراء</label>
                    <input type="date" name="purchase_date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">التكلفة</label>
                    <input type="number" step="0.01" min="0" name="cost" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">القيمة التخريدية</label>
                    <input type="number" step="0.01" min="0" name="salvage_value" class="form-control" value="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">العمر الإنتاجي (شهور)</label>
                    <input type="number" min="1" name="useful_life_months" class="form-control" value="60" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">بداية الإهلاك</label>
                    <input type="date" name="depreciation_start_date" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">نوع السجل</label>
                    <select name="is_group" class="form-select">
                        <option value="0">أصل</option>
                        <option value="1">مجموعة</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="1">نشط</option>
                        <option value="0">غير نشط</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary btn-sm">حفظ الأصل</button>
                </div>
            </div>
        </div>
    </form>
@endsection

