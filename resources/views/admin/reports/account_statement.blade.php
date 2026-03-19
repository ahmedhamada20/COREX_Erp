@extends('admin.layouts.master')

@section('title', 'كشف حساب')

@section('content')
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">كشف حساب</h4>
                <p class="text-muted mb-0">تصفية حسب حساب أو عميل أو مورد خلال فترة زمنية.</p>
            </div>
            <a href="{{ route('reports.index') }}" class="btn btn-light btn-sm">عودة</a>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.account_statement') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">من</label>
                    <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">إلى</label>
                    <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">الحساب</label>
                    <select name="account_id" class="form-select">
                        <option value="">الكل</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" @selected($selectedAccountId === $account->id)>
                                {{ $account->account_number }} - {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">عميل</label>
                    <select name="customer_id" class="form-select">
                        <option value="">الكل</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((int) request('customer_id') === $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">مورد</label>
                    <select name="supplier_id" class="form-select">
                        <option value="">الكل</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((int) request('supplier_id') === $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-primary w-100">عرض</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                <tr>
                    <th>التاريخ</th>
                    <th>رقم القيد</th>
                    <th>الحساب</th>
                    <th>الوصف</th>
                    <th class="text-end">مدين</th>
                    <th class="text-end">دائن</th>
                </tr>
                </thead>
                <tbody>
                @forelse($report['lines'] as $line)
                    <tr>
                        <td>{{ optional($line->journalEntry?->entry_date)->format('Y-m-d') ?? $line->journalEntry?->entry_date }}</td>
                        <td>{{ $line->journalEntry?->entry_number }}</td>
                        <td>{{ $line->account?->account_number }} - {{ $line->account?->name }}</td>
                        <td>{{ $line->journalEntry?->description ?: ($line->memo ?: '—') }}</td>
                        <td class="text-end text-success">{{ number_format((float) $line->debit, 2) }}</td>
                        <td class="text-end text-danger">{{ number_format((float) $line->credit, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">لا توجد حركات خلال الفترة المحددة</td></tr>
                @endforelse
                </tbody>
                <tfoot class="table-light">
                <tr>
                    <th colspan="4" class="text-end">الإجمالي</th>
                    <th class="text-end text-success">{{ number_format((float) $report['total_debit'], 2) }}</th>
                    <th class="text-end text-danger">{{ number_format((float) $report['total_credit'], 2) }}</th>
                </tr>
                <tr>
                    <th colspan="4" class="text-end">الصافي</th>
                    <th colspan="2" class="text-end {{ $report['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $report['net'], 2) }}</th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection

