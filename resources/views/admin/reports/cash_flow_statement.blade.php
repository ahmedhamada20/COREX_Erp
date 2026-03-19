@extends('admin.layouts.master')

@section('title', 'قائمة التدفقات النقدية')

@section('content')
    <div class="row">
        <div class="col-12">
            @include('admin.Alerts')

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3"
                     style="background:linear-gradient(135deg,#198754,#157347);border-radius:.5rem;">
                    <div>
                        <h4 class="text-white fw-bold mb-1"><i class="ti ti-activity-heartbeat me-1"></i> قائمة التدفقات النقدية</h4>
                        <p class="text-white-50 mb-0">من {{ $fromDate }} إلى {{ $toDate }}</p>
                    </div>
                    <a href="{{ route('reports.index') }}" class="btn btn-light btn-sm">عودة</a>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.cash_flow_statement') }}" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">من تاريخ</label>
                            <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">إلى تاريخ</label>
                            <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-success w-100">عرض</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-muted small">رصيد افتتاحي</div>
                            <div class="fw-bold fs-4">{{ number_format((float) $report['opening_cash'], 2) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-muted small">صافي التغير</div>
                            <div class="fw-bold fs-4 {{ $report['net_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format((float) $report['net_change'], 2) }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-muted small">رصيد ختامي</div>
                            <div class="fw-bold fs-4">{{ number_format((float) $report['closing_cash'], 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>البند</th>
                            <th class="text-end">تدفقات داخلة</th>
                            <th class="text-end">تدفقات خارجة</th>
                            <th class="text-end">الصافي</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>التدفقات التشغيلية</td>
                            <td class="text-end text-success">{{ number_format((float) $report['sections']['operating']['inflows'], 2) }}</td>
                            <td class="text-end text-danger">{{ number_format((float) $report['sections']['operating']['outflows'], 2) }}</td>
                            <td class="text-end">{{ number_format((float) $report['sections']['operating']['net'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>التدفقات الاستثمارية</td>
                            <td class="text-end text-success">{{ number_format((float) $report['sections']['investing']['inflows'], 2) }}</td>
                            <td class="text-end text-danger">{{ number_format((float) $report['sections']['investing']['outflows'], 2) }}</td>
                            <td class="text-end">{{ number_format((float) $report['sections']['investing']['net'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>التدفقات التمويلية</td>
                            <td class="text-end text-success">{{ number_format((float) $report['sections']['financing']['inflows'], 2) }}</td>
                            <td class="text-end text-danger">{{ number_format((float) $report['sections']['financing']['outflows'], 2) }}</td>
                            <td class="text-end">{{ number_format((float) $report['sections']['financing']['net'], 2) }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

