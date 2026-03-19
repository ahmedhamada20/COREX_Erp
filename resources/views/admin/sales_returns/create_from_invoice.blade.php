{{-- resources/views/admin/sales_returns/create_from_invoice.blade.php --}}
@extends('admin.layouts.master')

@section('title', 'إنشاء مرتجع من فاتورة مبيعات')

@section('content')
    <div class="container-fluid">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h4 class="mb-1">إنشاء مرتجع من الفاتورة: {{ $invoice->invoice_code ?? $invoice->invoice_number }}</h4>
                <div class="text-muted">التاريخ: {{ $invoice->invoice_date }}</div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('sales_invoices.show', $invoice->id) }}" class="btn btn-outline-secondary">
                    رجوع للفواتير
                </a>
            </div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <div class="fw-bold mb-2">يوجد أخطاء:</div>
                <ul class="mb-0">
                    @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-3">
            {{-- Invoice Info --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <strong>بيانات الفاتورة</strong>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="text-muted">الحالة</div>
                                <div class="fw-bold">{{ strtoupper($invoice->status) }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">نوع الدفع</div>
                                <div class="fw-bold">{{ strtoupper($invoice->payment_type) }}</div>
                            </div>

                            <div class="col-6">
                                <div class="text-muted">الإجمالي</div>
                                <div class="fw-bold">{{ number_format($invoice->total, 2) }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">مدفوع</div>
                                <div class="fw-bold text-success">{{ number_format($invoice->paid_amount, 2) }}</div>
                            </div>

                            <div class="col-6">
                                <div class="text-muted">متبقي</div>
                                <div class="fw-bold text-danger">{{ number_format($invoice->remaining_amount, 2) }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">العميل</div>
                                <div class="fw-bold">
                                    {{ $invoice->customer?->name ?? '-' }}
                                    <div class="small text-muted">{{ $invoice->customer?->code ?? '' }}</div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="text-muted mb-2">الأصناف</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>الصنف</th>
                                    <th class="text-center">الكمية</th>
                                    <th class="text-end">الإجمالي</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($invoice->items as $i => $line)
                                    <tr>
                                        <td>{{ $i+1 }}</td>
                                        <td>
                                            {{ $line->item?->name ?? '—' }}
                                            <div class="small text-muted">{{ $line->item?->barcode ?? $line->item?->items_code }}</div>
                                        </td>
                                        <td class="text-center">{{ rtrim(rtrim(number_format($line->quantity, 4), '0'), '.') }}</td>
                                        <td class="text-end">{{ number_format($line->total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">لا يوجد أصناف</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Create Return --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <strong>بيانات المرتجع</strong>
                    </div>
                    <div class="card-body">

                        <form method="POST" action="{{ route('sales_returns.store_from_invoice', $invoice->id) }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">تاريخ المرتجع</label>
                                <input type="date" name="return_date" class="form-control"
                                       value="{{ old('return_date', now()->toDateString()) }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">قيمة المرتجع (أقصى: {{ number_format($maxReturn, 2) }})</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0.0001"
                                           max="{{ $maxReturn }}"
                                           name="amount"
                                           class="form-control"
                                           value="{{ old('amount', $maxReturn) }}"
                                           required>
                                    <button type="button" class="btn btn-outline-primary" id="btnMax">
                                        أقصى مبلغ
                                    </button>
                                </div>
                                <div class="form-text text-muted">
                                    هذا المرتجع Header-only (بدون تحديد أصناف). لاحقاً يمكن تطويره لمرتجع بأصناف.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">طريقة رد المبلغ</label>
                                <select name="refund_mode" class="form-select" required>
                                    <option value="auto" @selected(old('refund_mode','auto')==='auto')>
                                        تلقائي (كاش يرجع كاش / آجل يرجع على العميل)
                                    </option>
                                    <option value="cash" @selected(old('refund_mode')==='cash')>رد نقداً (Cash)</option>
                                    <option value="ar" @selected(old('refund_mode')==='ar')>على حساب العميل (A/R)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">ملاحظات</label>
                                <textarea name="notes" class="form-control" rows="3"
                                          placeholder="سبب المرتجع...">{{ old('notes') }}</textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-success">
                                    إنشاء مرتجع + قيد محاسبي
                                </button>
                                <a href="{{ route('sales_invoices.show', $invoice->id) }}" class="btn btn-outline-secondary">
                                    إلغاء
                                </a>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('js')
    <script>
        document.getElementById('btnMax')?.addEventListener('click', function () {
            const input = document.querySelector('input[name="amount"]');
            if (!input) return;
            input.value = input.getAttribute('max') || input.value;
        });
    </script>
@endsection
