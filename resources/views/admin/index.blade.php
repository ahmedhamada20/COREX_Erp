@extends('admin.layouts.master')
@section('css')

@endsection

@section('title')
    نظام المبيعات والمخازن والمشتريات
@endsection

@section('content')

    <div class="content-header mb-3">
        <h1 class="mb-0 fw-bold">
            مرحبًا بعودتك،
            <span class="text-muted">الصفحة الرئيسية</span>
            —
            <span class="text-primary">
            {{ auth()->user()->name }}
        </span>
            👋
        </h1>
    </div>


    <div class="row">
        <div class="col-12">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body bg-primary rounded-3">
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-12">
                                    <div class="d-lg-flex justify-content-between align-items-center ">
                                        <div class="d-md-flex align-items-center">
                                            <img src="{{asset('dash/assets/images/user/avatar-2.jpg')}}" alt="Image"
                                                 class="rounded-circle avatar avatar-xl">
                                            <div class="mt-3 ms-md-4">
                                                <h2 class="mb-1 text-white fw-600">تقرير النظام الحالي
                                                </h2>
                                                <p class="text-white mb-0">ملخص لحظي لبيانات المبيعات والمشتريات الخاصة بحسابك.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6">
                    <div class="card">
                        <div class="border rounded card-body border-success bg-light-success">
                            <div class="d-flex align-items-center">
                                <div class="numbers flex-grow-1 pe-3">
                                    <p class="mb-1 fw-600 text-muted">إجمالي المبيعات</p>
                                    <h4 class="mb-0 fw-700 text-dark-black">{{ number_format((float) data_get($dashboardReport, 'finance.sales_total', 0), 2) }}</h4>
                                </div>
                                <div class="icon-shape bg-success ">
                                    <i class="ti ti-report-money"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6">
                    <div class="card">
                        <div class="border rounded card-body border-success bg-light-success">
                            <div class="d-flex align-items-center">
                                <div class="numbers flex-grow-1 pe-3">
                                    <p class="mb-1 fw-600 text-muted">إجمالي المشتريات</p>
                                    <h4 class="mb-0 fw-700 text-dark-black">{{ number_format((float) data_get($dashboardReport, 'finance.purchase_total', 0), 2) }}</h4>
                                </div>
                                <div class="icon-shape bg-success ">
                                    <i class="ti ti-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6">
                    <div class="card">
                        <div class="border rounded card-body border-danger bg-light-danger">
                            <div class="d-flex align-items-center">
                                <div class="numbers flex-grow-1 pe-3">
                                    <p class="mb-1 fw-600 text-muted">العملاء</p>
                                    <h4 class="mb-0 fw-700 text-dark-black">{{ number_format((int) data_get($dashboardReport, 'counts.customers', 0)) }}</h4>
                                </div>
                                <div class="icon-shape bg-danger ">
                                    <i class="ti ti-click"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6">
                    <div class="card">
                        <div class="border rounded card-body border-danger bg-light-danger">
                            <div class="d-flex align-items-center">
                                <div class="numbers flex-grow-1">
                                    <p class="mb-1 fw-600 text-muted">الموردين</p>
                                    <h4 class="mb-0 fw-700 text-dark-black">{{ number_format((int) data_get($dashboardReport, 'counts.suppliers', 0)) }}</h4>
                                </div>
                                <div class="icon-shape bg-danger ">
                                    <i class="ti ti-shopping-cart"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xxl-4 col-lg-6 col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Daily Sales</h4>
                        </div>
                        <div class="card-body">
                            <div id="Sales-chart"></div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-8 col-lg-6 col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Statistics</h4>
                        </div>
                        <div class="card-body">
                            <div id="traffic-chart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card table-card">
                        <div class="card-header">
                            <h4>تقرير النظام</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th>المؤشر</th>
                                        <th>القيمة</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td>عدد الأصناف</td>
                                        <td>{{ number_format((int) data_get($dashboardReport, 'counts.items', 0)) }}</td>
                                    </tr>
                                    <tr>
                                        <td>فواتير المبيعات</td>
                                        <td>{{ number_format((int) data_get($dashboardReport, 'counts.sales_invoices', 0)) }}</td>
                                    </tr>
                                    <tr>
                                        <td>فواتير المشتريات</td>
                                        <td>{{ number_format((int) data_get($dashboardReport, 'counts.purchase_invoices', 0)) }}</td>
                                    </tr>
                                    <tr>
                                        <td>إجمالي مرتجع المبيعات</td>
                                        <td>{{ number_format((float) data_get($dashboardReport, 'finance.sales_returns_total', 0), 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>صافي المبيعات</td>
                                        <td>{{ number_format((float) data_get($dashboardReport, 'finance.net_sales', 0), 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>المدفوع من المبيعات</td>
                                        <td>{{ number_format((float) data_get($dashboardReport, 'finance.sales_paid_total', 0), 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>المتبقي من المبيعات</td>
                                        <td>{{ number_format((float) data_get($dashboardReport, 'finance.sales_due_total', 0), 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>إجمالي مرتجع المشتريات</td>
                                        <td>{{ number_format((float) data_get($dashboardReport, 'finance.purchase_returns_total', 0), 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>صافي المشتريات</td>
                                        <td>{{ number_format((float) data_get($dashboardReport, 'finance.net_purchases', 0), 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>المدفوع من المشتريات</td>
                                        <td>{{ number_format((float) data_get($dashboardReport, 'finance.purchase_paid_total', 0), 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>المتبقي من المشتريات</td>
                                        <td>{{ number_format((float) data_get($dashboardReport, 'finance.purchase_due_total', 0), 2) }}</td>
                                    </tr>
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
