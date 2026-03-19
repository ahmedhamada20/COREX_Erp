@extends('admin.layouts.master')

@section('title', 'الميزانية العمومية')

@section('content')
    <div class="row">
        <div class="col-12">
            @include('admin.Alerts')

            {{-- Header + Filter --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3"
                     style="background:linear-gradient(135deg,#0d6efd,#0a58ca);border-radius:.5rem;">
                    <div>
                        <h4 class="text-white fw-bold mb-1"><i class="ti ti-scale me-1"></i> الميزانية العمومية</h4>
                        <p class="text-white-50 mb-0">بتاريخ: {{ \Carbon\Carbon::parse($asOfDate)->format('Y/m/d') }}</p>
                    </div>
                    <a href="{{ route('reports.index') }}" class="btn btn-light btn-sm">
                        <i class="ti ti-arrow-right me-1"></i> عودة للتقارير
                    </a>
                </div>
            </div>

            {{-- Date Filter --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.balance_sheet') }}" class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">بتاريخ</label>
                            <input type="date" name="as_of_date" class="form-control"
                                   value="{{ $asOfDate }}" max="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100"><i class="ti ti-search me-1"></i> عرض</button>
                        </div>
                    </form>
                </div>
            </div>

            @php
                $isBalanced = $report['equation_balanced'];
            @endphp

            {{-- Equation Alert --}}
            @if(!$isBalanced)
                <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
                    <i class="ti ti-alert-triangle fs-5"></i>
                    <span>تحذير: الميزانية غير متوازنة — الفرق: {{ number_format($report['difference'], 2) }}</span>
                </div>
            @endif

            <div class="row g-4">
                {{-- Assets --}}
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-primary text-white fw-bold">
                            <i class="ti ti-building-bank me-1"></i> الأصول
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>الحساب</th>
                                        <th class="text-end">الرصيد</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($report['assets']['accounts'] as $acc)
                                        <tr>
                                            <td>
                                                <span class="text-muted small">{{ $acc->account_number ?? '' }}</span>
                                                {{ $acc->name ?? '' }}
                                            </td>
                                            <td class="text-end fw-semibold text-success">
                                                {{ number_format((float)($acc->balance ?? 0), 2) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="text-center text-muted py-3">لا توجد بيانات</td></tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-primary">
                                    <tr>
                                        <th>إجمالي الأصول</th>
                                        <th class="text-end">{{ number_format($report['assets']['total'], 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Liabilities + Equity --}}
                <div class="col-lg-6">
                    {{-- Liabilities --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-danger text-white fw-bold">
                            <i class="ti ti-credit-card me-1"></i> الخصوم
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr><th>الحساب</th><th class="text-end">الرصيد</th></tr>
                                </thead>
                                <tbody>
                                    @forelse($report['liabilities']['accounts'] as $acc)
                                        <tr>
                                            <td><span class="text-muted small">{{ $acc->account_number ?? '' }}</span> {{ $acc->name ?? '' }}</td>
                                            <td class="text-end fw-semibold text-danger">{{ number_format((float)($acc->balance ?? 0), 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="text-center text-muted py-3">لا توجد بيانات</td></tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-danger">
                                    <tr>
                                        <th>إجمالي الخصوم</th>
                                        <th class="text-end">{{ number_format($report['liabilities']['total'], 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- Equity --}}
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-success text-white fw-bold">
                            <i class="ti ti-coin me-1"></i> حقوق الملكية
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr><th>الحساب</th><th class="text-end">الرصيد</th></tr>
                                </thead>
                                <tbody>
                                    @forelse($report['equity']['accounts'] as $acc)
                                        <tr>
                                            <td><span class="text-muted small">{{ $acc->account_number ?? '' }}</span> {{ $acc->name ?? '' }}</td>
                                            <td class="text-end fw-semibold text-success">{{ number_format((float)($acc->balance ?? 0), 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="text-center text-muted py-3">لا توجد بيانات</td></tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-success">
                                    <tr>
                                        <th>إجمالي حقوق الملكية</th>
                                        <th class="text-end">{{ number_format($report['equity']['total'], 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- Summary --}}
                    <div class="card border-0 shadow-sm mt-3 {{ $isBalanced ? 'border-success' : 'border-warning' }}">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between fw-bold">
                                <span>الخصوم + حقوق الملكية</span>
                                <span class="{{ $isBalanced ? 'text-success' : 'text-warning' }}">
                                    {{ number_format($report['liabilities_and_equity'], 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

