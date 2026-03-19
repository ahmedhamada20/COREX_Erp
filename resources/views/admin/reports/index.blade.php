@extends('admin.layouts.master')

@section('title', 'التقارير المالية')

@section('content')
    <div class="row">
        <div class="col-12">
            @include('admin.Alerts')

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body" style="background: linear-gradient(135deg,#0d6efd,#0a58ca); border-radius: .5rem;">
                    <h4 class="text-white mb-1 fw-bold"><i class="ti ti-chart-bar me-1"></i> التقارير المالية</h4>
                    <p class="text-white-50 mb-0">اختر التقرير المناسب لعرض البيانات المالية</p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <a href="{{ route('reports.balance_sheet') }}" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100 report-card">
                            <div class="card-body text-center p-4">
                                <div class="report-icon bg-primary bg-opacity-10 rounded-3 d-inline-flex p-3 mb-3">
                                    <i class="ti ti-scale fs-2 text-primary"></i>
                                </div>
                                <h5 class="fw-bold mb-2">الميزانية العمومية</h5>
                                <p class="text-muted small mb-0">عرض الأصول والخصوم وحقوق الملكية في تاريخ محدد</p>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="{{ route('reports.income_statement') }}" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100 report-card">
                            <div class="card-body text-center p-4">
                                <div class="report-icon bg-success bg-opacity-10 rounded-3 d-inline-flex p-3 mb-3">
                                    <i class="ti ti-trending-up fs-2 text-success"></i>
                                </div>
                                <h5 class="fw-bold mb-2">قائمة الدخل</h5>
                                <p class="text-muted small mb-0">عرض الإيرادات والمصروفات وصافي الربح لفترة محددة</p>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="{{ route('reports.trial_balance') }}" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100 report-card">
                            <div class="card-body text-center p-4">
                                <div class="report-icon bg-warning bg-opacity-10 rounded-3 d-inline-flex p-3 mb-3">
                                    <i class="ti ti-list-check fs-2 text-warning"></i>
                                </div>
                                <h5 class="fw-bold mb-2">ميزان المراجعة</h5>
                                <p class="text-muted small mb-0">عرض أرصدة جميع الحسابات مرتبة وفق دليل الحسابات</p>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="{{ route('reports.account_statement') }}" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100 report-card">
                            <div class="card-body text-center p-4">
                                <div class="report-icon bg-info bg-opacity-10 rounded-3 d-inline-flex p-3 mb-3">
                                    <i class="ti ti-receipt fs-2 text-info"></i>
                                </div>
                                <h5 class="fw-bold mb-2">كشف حساب</h5>
                                <p class="text-muted small mb-0">عرض حركات حساب أو عميل أو مورد مع إجماليات الفترة</p>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="{{ route('reports.cash_flow_statement') }}" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100 report-card">
                            <div class="card-body text-center p-4">
                                <div class="report-icon bg-success bg-opacity-10 rounded-3 d-inline-flex p-3 mb-3">
                                    <i class="ti ti-activity-heartbeat fs-2 text-success"></i>
                                </div>
                                <h5 class="fw-bold mb-2">التدفقات النقدية</h5>
                                <p class="text-muted small mb-0">تتبع صافي حركة النقد خلال الفترة</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
        .report-card { transition: transform .2s, box-shadow .2s; cursor: pointer; }
        .report-card:hover { transform: translateY(-4px); box-shadow: 0 .75rem 1.5rem rgba(0,0,0,.1) !important; }
        .report-icon { width: 64px; height: 64px; align-items: center; justify-content: center; }
    </style>
@endsection

