{{-- resources/views/admin/sales_returns/index.blade.php --}}
@extends('admin.layouts.master')

@section('title', 'مرتجعات المبيعات')

@section('css')
    {{-- DataTables Bootstrap 5 --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

    {{-- Select2 --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet"/>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <style>
        .table-card .card-body { padding-top: 1rem; }
        table.dataTable td, table.dataTable th { vertical-align: middle; }
        .dataTables_wrapper .dataTables_filter input { margin-inline-start: .5rem; }
        .dataTables_wrapper .dataTables_length select { margin: 0 .35rem; }

        .form-hint { font-size: 12px; color: #64748b; }

        /* Dropdown actions */
        .dropdown-menu { min-width: 280px; }
        .dropdown-item small { display:block; line-height: 1.2; }

        /* Select2 tweak with BS5 */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: calc(1.5em + .5rem + 2px);
            padding: .25rem .5rem;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h4 class="mb-1">مرتجعات المبيعات</h4>
                <div class="text-muted">عرض وإدارة مرتجعات المبيعات والقيد المحاسبي المرتبط بها</div>
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="card mb-3">
            <div class="card-body">
                <form id="salesReturnsFilterForm" class="row g-2 align-items-end">

                    <div class="col-md-3">
                        <label class="form-label">بحث</label>
                        <input type="text" name="q" class="form-control" placeholder="رقم المرتجع / كود الفاتورة / اسم العميل">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">العميل</label>
                        <select name="customer_id" class="form-select">
                            <option value="">الكل</option>
                            {{-- اختياري: لو انت بتمرر $customers من الكونترولر --}}
                            @isset($customers)
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            @endisset
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">من</label>
                        <input type="date" name="date_from" class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">إلى</label>
                        <input type="date" name="date_to" class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">قيد اليومية</label>
                        <select name="has_je" class="form-select">
                            <option value="">الكل</option>
                            <option value="1">يوجد قيد</option>
                            <option value="0">بدون قيد</option>
                        </select>
                    </div>

                    <div class="col-12 d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-outline-primary" id="btnApplyReturnsFilters">
                            <i class="fa fa-filter me-1"></i> تطبيق
                        </button>

                        <button type="button" class="btn btn-outline-secondary" id="btnResetReturnsFilters">
                            <i class="fa fa-rotate-left me-1"></i> مسح
                        </button>
                    </div>

                </form>
            </div>
        </div>

        {{-- Table --}}
        <div class="card">
            <div class="card-body">
                {!! $dataTable->table(['class' => 'table table-bordered table-striped table-hover w-100'], true) !!}
            </div>
        </div>

    </div>
@endsection

@section('js')
    {!! $dataTable->scripts() !!}

    <script>
        (function () {
            const tableId = '#sales-returns-table';

            document.getElementById('btnApplyReturnsFilters')?.addEventListener('click', function () {
                window.LaravelDataTables?.['sales-returns-table']?.ajax.reload();
            });

            document.getElementById('btnResetReturnsFilters')?.addEventListener('click', function () {
                const form = document.getElementById('salesReturnsFilterForm');
                if (!form) return;
                form.reset();
                window.LaravelDataTables?.['sales-returns-table']?.ajax.reload();
            });

            // Cancel Return (POST)
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.js-cancel-return');
                if (!btn) return;

                const url = btn.getAttribute('data-url');
                if (!url) return;

                if (!confirm('تأكيد إلغاء المرتجع؟ سيتم عمل قيد عكسي إذا كان موجودًا.')) return;

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                })
                    .then(async (res) => {
                        // لو رجع Redirect HTML هنعمل reload
                        if (!res.ok) throw new Error('Request failed');
                        window.LaravelDataTables?.['sales-returns-table']?.ajax.reload(null, false);
                        location.reload();
                    })
                    .catch(() => alert('حدث خطأ أثناء الإلغاء'));
            });
        })();
    </script>
@endsection
