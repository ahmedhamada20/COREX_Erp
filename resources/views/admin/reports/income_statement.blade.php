@extends('admin.layouts.master')

@section('title', 'قائمة الدخل')

@section('content')
    <div class="row">
        <div class="col-12">
            @include('admin.Alerts')

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3"
                     style="background:linear-gradient(135deg,#198754,#146c43);border-radius:.5rem;">
                    <div>
                        <h4 class="text-white fw-bold mb-1"><i class="ti ti-trending-up me-1"></i> قائمة الدخل</h4>
                        <p class="text-white-50 mb-0">
                            من {{ \Carbon\Carbon::parse($fromDate)->format('Y/m/d') }}
                            إلى {{ \Carbon\Carbon::parse($toDate)->format('Y/m/d') }}
                        </p>
                    </div>
                    <a href="{{ route('reports.index') }}" class="btn btn-light btn-sm">
                        <i class="ti ti-arrow-right me-1"></i> عودة
                    </a>
                </div>
            </div>

            {{-- Date Filter --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.income_statement') }}" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">من تاريخ</label>
                            <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">إلى تاريخ</label>
                            <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-success w-100"><i class="ti ti-search me-1"></i> عرض</button>
                        </div>
                    </form>
                </div>
            </div>

            @php
                $netIncome = $report['net_income'];
                $isProfit  = $netIncome >= 0;
            @endphp

            {{-- KPIs --}}
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm text-center p-3">
                        <div class="text-muted small mb-1">إجمالي الإيرادات</div>
                        <div class="fw-bold text-success fs-5">{{ number_format($report['revenues']['total'], 2) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm text-center p-3">
                        <div class="text-muted small mb-1">مجمل الربح</div>
                        <div class="fw-bold {{ $report['gross_profit'] >= 0 ? 'text-success' : 'text-danger' }} fs-5">
                            {{ number_format($report['gross_profit'], 2) }}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm text-center p-3">
                        <div class="text-muted small mb-1">ربح التشغيل</div>
                        <div class="fw-bold {{ $report['operating_income'] >= 0 ? 'text-success' : 'text-danger' }} fs-5">
                            {{ number_format($report['operating_income'], 2) }}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm text-center p-3">
                        <div class="text-muted small mb-1">صافي الربح / الخسارة</div>
                        <div class="fw-bold {{ $isProfit ? 'text-success' : 'text-danger' }} fs-5">
                            {{ $isProfit ? '' : '-' }}{{ number_format(abs($netIncome), 2) }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Detailed Table --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr><th>البند</th><th class="text-end">المبلغ</th></tr>
                        </thead>
                        <tbody>
                            {{-- Revenues --}}
                            <tr class="table-success"><td colspan="2" class="fw-bold"><i class="ti ti-plus me-1"></i> الإيرادات</td></tr>
                            @foreach($report['revenues']['accounts'] as $acc)
                                <tr>
                                    <td class="ps-4"><span class="text-muted small">{{ $acc->account_number ?? '' }}</span> {{ $acc->name ?? '' }}</td>
                                    <td class="text-end text-success">{{ number_format((float)($acc->balance ?? 0), 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="fw-bold"><td class="ps-4">إجمالي الإيرادات</td><td class="text-end text-success">{{ number_format($report['revenues']['total'], 2) }}</td></tr>

                            {{-- COGS --}}
                            <tr class="table-warning"><td colspan="2" class="fw-bold"><i class="ti ti-minus me-1"></i> تكلفة البضاعة المباعة</td></tr>
                            @foreach($report['cost_of_goods_sold']['accounts'] as $acc)
                                <tr>
                                    <td class="ps-4"><span class="text-muted small">{{ $acc->account_number ?? '' }}</span> {{ $acc->name ?? '' }}</td>
                                    <td class="text-end text-warning">{{ number_format((float)($acc->balance ?? 0), 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="fw-bold table-light"><td>مجمل الربح</td><td class="text-end {{ $report['gross_profit'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($report['gross_profit'], 2) }}</td></tr>

                            {{-- Operating Expenses --}}
                            <tr class="table-danger"><td colspan="2" class="fw-bold"><i class="ti ti-minus me-1"></i> المصروفات التشغيلية</td></tr>
                            @foreach($report['operating_expenses']['accounts'] as $acc)
                                <tr>
                                    <td class="ps-4"><span class="text-muted small">{{ $acc->account_number ?? '' }}</span> {{ $acc->name ?? '' }}</td>
                                    <td class="text-end text-danger">{{ number_format((float)($acc->balance ?? 0), 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="fw-bold table-light"><td>ربح التشغيل</td><td class="text-end {{ $report['operating_income'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($report['operating_income'], 2) }}</td></tr>

                            {{-- Net Income --}}
                            <tr class="fw-bold {{ $isProfit ? 'table-success' : 'table-danger' }}">
                                <td class="fs-6">صافي {{ $isProfit ? 'الربح' : 'الخسارة' }}</td>
                                <td class="text-end fs-6">{{ number_format(abs($netIncome), 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

