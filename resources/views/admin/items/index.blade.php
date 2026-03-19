@extends('admin.layouts.master')

@section('title', 'بيانات الأصناف')

@section('css')
    {{-- لو ملفات الداتا تيبولز مش متحملة في master --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <style>
        /* تحسين شكل الجدول */
        .table-card .card-body {
            padding-top: 1rem;
        }

        table.dataTable td, table.dataTable th {
            vertical-align: middle;
        }

        .dataTables_wrapper .dataTables_filter input {
            margin-inline-start: .5rem;
        }

        .dataTables_wrapper .dataTables_length select {
            margin: 0 .35rem;
        }

        /* Switch status */
        .switch {
            position: relative;
            display: inline-block;
            width: 46px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background-color: #ccc;
            transition: .2s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: #fff;
            transition: .2s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #22c55e;
        }

        input:checked + .slider:before {
            transform: translateX(22px);
        }
    </style>
@endsection

@section('content')

    <div class="content-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">
            بيانات الأصناف
            <span class="text-muted fw-normal ms-2">
                — {{ auth()->user()->name }}
            </span>
        </h5>

        <div class="d-flex gap-2">
            <a href="{{ route('items.create') }}" class="btn btn-sm btn-success">
                <i class="ti ti-plus"></i> إضافة صنف جديد
            </a>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            @include('admin.Alerts')

            <div class="card table-card">
                <div class="card-header">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-5">
                            <input type="text" id="filter_q" class="form-control"
                                   placeholder="بحث بالاسم / الكود / الباركود...">
                        </div>

                        <div class="col-md-3">
                            <select id="filter_type" class="form-select">
                                <option value="">كل الأنواع</option>
                                <option value="store">مخزني</option>
                                <option value="consumption">استهلاكي</option>
                                <option value="custody">عهدة</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <select id="filter_status" class="form-select">
                                <option value="">كل الحالات</option>
                                <option value="1">نشط</option>
                                <option value="0">غير نشط</option>
                            </select>
                        </div>

                        <div class="col-md-1 text-end">
                            <button type="button" id="filter_reset" class="btn btn-light btn-sm w-100">
                                <i class="ti ti-refresh"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        {!! $dataTable->table([
                          'id' => 'items-table',
                          'class' => 'table table-striped table-row-bordered gy-5 gs-7 text-end w-100'
                      ], true) !!}

                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('js')
    {{-- لو ملفات الداتا تيبولز مش متحملة في master --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    {!! $dataTable->scripts() !!}

    {{-- Toggle status --}}
    <script>
        $(document).on('change', '.js-toggle-status', function () {
            const url = $(this).data('url');
            const el = $(this);

            $.ajax({
                url: url,
                type: 'POST',
                data: {_token: '{{ csrf_token() }}'},
                success: function () {
                    toastr.success('تم تحديث الحالة بنجاح');
                },
                error: function () {
                    el.prop('checked', !el.prop('checked')); // يرجعها لو حصل خطأ
                    toastr.error('حدث خطأ أثناء تحديث الحالة');
                }
            });
        });
    </script>

    <script>
        // helper debounce
        function debounce(fn, delay = 350) {
            let t;
            return function (...args) {
                clearTimeout(t);
                t = setTimeout(() => fn.apply(this, args), delay);
            }
        }

        function dtDraw() {
            // مهم: instance بتاعة yajra بتكون غالباً على نفس id
            $('#items-table').DataTable().ajax.reload(null, true);
        }

        $(document).ready(function () {
            const table = $('#items-table').DataTable();

            // search input (debounced)
            $('#filter_q').on('keyup', debounce(function () {
                table.ajax.reload(null, true);
            }, 350));

            // filters
            $('#filter_type, #filter_status').on('change', function () {
                table.ajax.reload(null, true);
            });

            // reset
            $('#filter_reset').on('click', function () {
                $('#filter_q').val('');
                $('#filter_type').val('');
                $('#filter_status').val('');
                table.ajax.reload(null, true);
            });
        });
    </script>




@endsection
