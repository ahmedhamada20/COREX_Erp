@extends('admin.layouts.master')

@section('title', 'تعديل حركة خزنة')

@section('content')

    <div class="content-header d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">تعديل حركة - خزنة: {{ $treasury->name }}</h5>
            <small class="text-muted">#{{ $delivery->id }} — سند: <span class="num">{{ $delivery->receipt_no ?? '—' }}</span></small>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('treasuries.deliveries.index', $treasury->id) }}" class="btn btn-sm btn-outline-secondary">رجوع</a>
        </div>
    </div>

    @include('admin.Alerts')

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="fw-semibold">بيانات الحركة</div>
            <span class="badge bg-info">ERP</span>
        </div>

        <div class="card-body">

            <div class="alert alert-warning small">
                <i class="ti ti-alert-triangle"></i>
                تعديل الحركة سيعمل <b>Repost</b> (عكس القيد القديم + إنشاء قيد جديد).
            </div>

            <form method="POST" action="{{ route('treasuries.deliveries.update', [$treasury->id, $delivery->id]) }}">
                @csrf
                @method('PUT')

                <div class="row g-3">

                    {{-- Type --}}
                    <div class="col-md-4">
                        <label class="form-label">نوع الحركة</label>
                        <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="collection" {{ old('type', $delivery->type)==='collection' ? 'selected' : '' }}>تحصيل (قبض)</option>
                            <option value="payment" {{ old('type', $delivery->type)==='payment' ? 'selected' : '' }}>صرف</option>
                            <option value="transfer" {{ old('type', $delivery->type)==='transfer' ? 'selected' : '' }}>تحويل بين الخزن</option>
                        </select>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Doc Date --}}
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الحركة</label>
                        <input type="date"
                               name="doc_date"
                               class="form-control @error('doc_date') is-invalid @enderror"
                               value="{{ old('doc_date', optional($delivery->doc_date)->format('Y-m-d') ?? now()->toDateString()) }}">
                        @error('doc_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Amount --}}
                    <div class="col-md-4">
                        <label class="form-label">المبلغ</label>
                        <input type="number" step="0.01" min="0.01"
                               name="amount"
                               class="form-control @error('amount') is-invalid @enderror"
                               value="{{ old('amount', $delivery->amount) }}" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Other Treasury (Transfer) --}}
                    <div class="col-md-6" id="otherTreasuryWrap" style="display:none;">
                        <label class="form-label">خزنة الاستلام (للتحويل)</label>
                        <select name="other_treasury_id" id="other_treasury_id" class="form-select @error('other_treasury_id') is-invalid @enderror">
                            <option value="">— اختر خزنة —</option>
                            @foreach($treasuries as $t)
                                @if($t->id != $treasury->id)
                                    <option value="{{ $t->id }}" {{ (string)old('other_treasury_id', $delivery->to_treasury_id)===(string)$t->id ? 'selected' : '' }}>
                                        {{ $t->name }} @if($t->is_master) — (رئيسية) @endif
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        @error('other_treasury_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Counterparty Account (Collection/Payment) --}}
                    <div class="col-md-6" id="counterpartyWrap" style="display:none;">
                        <label class="form-label">حساب الطرف المقابل</label>
                        <select name="counterparty_account_id" id="counterparty_account_id" class="form-select @error('counterparty_account_id') is-invalid @enderror">
                            <option value="">— اختر حساب —</option>
                            @foreach(($accounts ?? []) as $acc)
                                <option value="{{ $acc->id }}" {{ (string)old('counterparty_account_id', $delivery->counterparty_account_id)===(string)$acc->id ? 'selected' : '' }}>
                                    {{ $acc->name }}{{ $acc->account_number ? ' - '.$acc->account_number : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('counterparty_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Reason --}}
                    <div class="col-md-6">
                        <label class="form-label">السبب (اختياري)</label>
                        <input type="text"
                               name="reason"
                               class="form-control @error('reason') is-invalid @enderror"
                               value="{{ old('reason', $delivery->reason) }}">
                        @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Notes --}}
                    <div class="col-12">
                        <label class="form-label">ملاحظات (اختياري)</label>
                        <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $delivery->notes) }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('treasuries.deliveries.index', $treasury->id) }}" class="btn btn-outline-secondary">إلغاء</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy"></i>
                        حفظ التعديل
                    </button>
                </div>

            </form>

        </div>
    </div>

    <script>
        (function () {
            const typeEl = document.getElementById('type');
            const otherWrap = document.getElementById('otherTreasuryWrap');
            const counterWrap = document.getElementById('counterpartyWrap');

            const otherEl = document.getElementById('other_treasury_id');
            const counterEl = document.getElementById('counterparty_account_id');

            function sync() {
                const t = typeEl.value;

                const isTransfer = (t === 'transfer');
                otherWrap.style.display = isTransfer ? 'block' : 'none';
                if (otherEl) otherEl.required = isTransfer;

                const needsCounter = (t === 'collection' || t === 'payment');
                counterWrap.style.display = needsCounter ? 'block' : 'none';
                if (counterEl) counterEl.required = needsCounter;

                if (!isTransfer && otherEl) otherEl.value = '';
                if (!needsCounter && counterEl) counterEl.value = '';
            }

            typeEl.addEventListener('change', sync);
            sync();
        })();
    </script>

@endsection
