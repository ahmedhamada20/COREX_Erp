@extends('admin.layouts.master')

@section('title', 'إضافة حركة خزنة')

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">إضافة حركة على خزنة: {{ $treasury->name }}</h5>
                <div class="alert alert-info mb-3">
                    <strong>رصيد الخزنة الحالي:</strong>
                    {{ number_format($balance, 2) }}
                </div>

                <a href="{{ route('treasuries.deliveries.index', $treasury->id) }}" class="btn btn-sm btn-secondary">رجوع</a>
            </div>

            <div class="card-body">
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <form method="POST" action="{{ route('treasuries.deliveries.store', $treasury->id) }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">النوع</label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror">
                                <option value="collection" @selected(old('type')==='collection')>تحصيل (قبض)</option>
                                <option value="payment" @selected(old('type')==='payment')>صرف</option>
                                <option value="transfer" @selected(old('type')==='transfer')>تحويل بين خزنتين</option>
                            </select>
                            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">المبلغ</label>
                            <input type="number" step="0.01" name="amount" value="{{ old('amount') }}"
                                   class="form-control @error('amount') is-invalid @enderror">
                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">التاريخ</label>
                            <input type="date" name="doc_date" value="{{ old('doc_date', now()->toDateString()) }}"
                                   class="form-control @error('doc_date') is-invalid @enderror">
                            @error('doc_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Transfer --}}
                        <div class="col-md-6" id="otherTreasuryWrap" style="display:none;">
                            <label class="form-label">خزنة الاستلام</label>
                            <select name="other_treasury_id" id="other_treasury_id" class="form-select @error('other_treasury_id') is-invalid @enderror">
                                <option value="">— اختر خزنة —</option>
                                @foreach($treasuries as $t)
                                    @if((int)$t->id !== (int)$treasury->id)
                                        <option value="{{ $t->id }}" @selected((string)old('other_treasury_id')===(string)$t->id)>
                                            {{ $t->name }} {{ $t->is_master ? '(رئيسية)' : '' }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('other_treasury_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Counterparty (Collection/Payment) --}}
                        <div class="col-md-6" id="counterpartyWrap" style="display:none;">
                            <label class="form-label">نوع الطرف المقابل</label>
                            <select name="counterparty_type" id="counterparty_type" class="form-select @error('counterparty_type') is-invalid @enderror">
                                <option value="">— اختر —</option>
                                <option value="customer" @selected(old('counterparty_type')==='customer')>عميل</option>
                                <option value="supplier" @selected(old('counterparty_type')==='supplier')>مورد</option>
                                <option value="general"  @selected(old('counterparty_type')==='general')>حساب عام (محدود)</option>
                            </select>
                            @error('counterparty_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">المستخدم لا يختار حساب محاسبي عشوائيًا — اختر الطرف فقط.</div>
                        </div>

                        <div class="col-md-6" id="customerWrap" style="display:none;">
                            <label class="form-label">العميل</label>
                            <select name="customer_id" id="customer_id" class="form-select @error('customer_id') is-invalid @enderror">
                                <option value="">— اختر عميل —</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" @selected((string)old('customer_id')===(string)$c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                            @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6" id="supplierWrap" style="display:none;">
                            <label class="form-label">المورد</label>
                            <select name="supplier_id" id="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
                                <option value="">— اختر مورد —</option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}" @selected((string)old('supplier_id')===(string)$s->id)>{{ $s->name }}</option>
                                @endforeach
                            </select>
                            @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6" id="generalWrap" style="display:none;">
                            <label class="form-label">الحساب العام</label>
                            <select name="general_account_id" id="general_account_id" class="form-select @error('general_account_id') is-invalid @enderror">
                                <option value="">— اختر حساب —</option>
                                @foreach($generalAccounts as $a)
                                    <option value="{{ $a->id }}" @selected((string)old('general_account_id')===(string)$a->id)>
                                        {{ $a->name }}{{ $a->account_number ? ' - '.$a->account_number : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('general_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text text-warning">يفضل استخدام عميل/مورد. الحساب العام للحالات الخاصة فقط.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">السبب</label>
                            <input type="text" name="reason" value="{{ old('reason') }}" class="form-control @error('reason') is-invalid @enderror">
                            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">ملاحظات</label>
                            <textarea name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 d-flex gap-2">
                            <button class="btn btn-primary">حفظ</button>
                            <a href="{{ route('treasuries.deliveries.index', $treasury->id) }}" class="btn btn-light">إلغاء</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>

        (function () {
            const typeEl = document.getElementById('type');

            const otherWrap = document.getElementById('otherTreasuryWrap');
            const otherEl   = document.getElementById('other_treasury_id');

            const counterWrap = document.getElementById('counterpartyWrap');
            const counterType = document.getElementById('counterparty_type');

            const customerWrap = document.getElementById('customerWrap');
            const customerEl   = document.getElementById('customer_id');

            const supplierWrap = document.getElementById('supplierWrap');
            const supplierEl   = document.getElementById('supplier_id');

            const generalWrap = document.getElementById('generalWrap');
            const generalEl   = document.getElementById('general_account_id');

            function syncCounterparty() {
                const t = counterType.value;

                customerWrap.style.display = (t === 'customer') ? '' : 'none';
                supplierWrap.style.display = (t === 'supplier') ? '' : 'none';
                generalWrap.style.display  = (t === 'general')  ? '' : 'none';

                if (customerEl) customerEl.required = (t === 'customer');
                if (supplierEl) supplierEl.required = (t === 'supplier');
                if (generalEl)  generalEl.required  = (t === 'general');

                if (t !== 'customer' && customerEl) customerEl.value = '';
                if (t !== 'supplier' && supplierEl) supplierEl.value = '';
                if (t !== 'general'  && generalEl)  generalEl.value  = '';
            }

            function sync() {
                const type = typeEl.value;

                const isTransfer = (type === 'transfer');
                otherWrap.style.display = isTransfer ? '' : 'none';
                if (otherEl) otherEl.required = isTransfer;
                if (!isTransfer && otherEl) otherEl.value = '';

                const needsCounter = (type === 'collection' || type === 'payment');
                counterWrap.style.display = needsCounter ? '' : 'none';
                if (counterType) counterType.required = needsCounter;

                if (!needsCounter) {
                    counterType.value = '';
                    syncCounterparty();
                } else {
                    syncCounterparty();
                }
            }

            typeEl.addEventListener('change', sync);
            counterType.addEventListener('change', syncCounterparty);

            sync();
        })();
    </script>
@endsection
