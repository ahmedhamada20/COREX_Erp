{{-- resources/views/admin/suppliers/index.blade.php --}}
@extends('admin.layouts.master')

@section('title', 'الموردين')

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <style>
        .table-card .card-body { padding-top: 1rem; }
        table.dataTable td, table.dataTable th { vertical-align: middle; }
        .dataTables_wrapper .dataTables_filter input { margin-inline-start: .5rem; }
        .dataTables_wrapper .dataTables_length select { margin: 0 .35rem; }

        /* Switch status */
        .switch { position: relative; display: inline-block; width: 46px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; inset: 0;
            background-color: #ccc; transition: .2s; border-radius: 24px;
        }
        .slider:before {
            position: absolute; content: ""; height: 18px; width: 18px;
            left: 3px; bottom: 3px; background-color: #fff; transition: .2s; border-radius: 50%;
        }
        input:checked + .slider { background-color: #22c55e; }
        input:checked + .slider:before { transform: translateX(22px); }
    </style>
@endsection

@section('content')

    {{-- Header --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-sm-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-0">الموردين</h4>
                    <small class="text-muted">إدارة الموردين + البحث والتصفية + تغيير الحالة</small>
                </div>

                <div class="d-flex gap-2 mt-2 mt-sm-0">
                    <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm">
                        <i class="ti ti-plus"></i> إضافة مورد
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" id="suppliersFilterForm" class="row g-2 align-items-end">

                <div class="col-md-3">
                    <label class="form-label">بحث</label>
                    <input type="text" name="q" value="{{ request('q') }}"
                           class="form-control form-control-sm"
                           placeholder="اسم المورد / كود / هاتف / رقم حساب">
                </div>

                <div class="col-md-2">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        <option value="1" @selected(request('status') === '1')>نشط</option>
                        <option value="0" @selected(request('status') === '0')>غير نشط</option>
                    </select>
                </div>

                {{-- اختياري: تصنيف المورد --}}
                <div class="col-md-2">
                    <label class="form-label">التصنيف</label>
                    <select name="supplier_category_id" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        @foreach(($categories ?? []) as $cat)
                            <option value="{{ $cat->id }}" @selected((string)request('supplier_category_id') === (string)$cat->id)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">المدينة</label>
                    <input type="text" name="city" value="{{ request('city') }}"
                           class="form-control form-control-sm"
                           placeholder="القاهرة / الجيزة">
                </div>

                <div class="col-md-1">
                    <label class="form-label">من</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="form-control form-control-sm">
                </div>

                <div class="col-md-1">
                    <label class="form-label">إلى</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="form-control form-control-sm">
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="button" id="resetSuppliersFilters" class="btn btn-light btn-sm">
                        <i class="ti ti-refresh"></i> إعادة ضبط
                    </button>
                </div>

            </form>
        </div>
    </div>

    {{-- Alerts --}}
    @include('admin.Alerts')

    {{-- Table --}}
    <div class="card table-card">
        <div class="card-body">
            <div class="table-responsive">
                {!! $dataTable->table([
                    'id' => 'suppliers-table',
                    'class' => 'table table-striped table-row-bordered gy-5 gs-7 text-end w-100'
                ], true) !!}
            </div>
        </div>
    </div>

@endsection

@section('js')
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
            const el  = $(this);
            const previous = !el.prop('checked');

            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: { _token: '{{ csrf_token() }}' },

                success: function (res) {
                    if (res && res.success === false) {
                        el.prop('checked', previous);
                        toastr.error(res.message || 'فشل تحديث الحالة');
                        return;
                    }
                    toastr.success((res && res.message) ? res.message : 'تم تحديث الحالة بنجاح');
                },

                error: function (xhr) {
                    el.prop('checked', previous);

                    const msg =
                        xhr?.responseJSON?.message ||
                        (xhr?.responseJSON?.errors ? Object.values(xhr.responseJSON.errors).flat()[0] : null) ||
                        'حدث خطأ أثناء تحديث الحالة';

                    toastr.error(msg);
                }
            });
        });
    </script>

    {{-- Filters -> redraw datatable --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const table = window.LaravelDataTables["suppliers-table"];
            const form  = document.getElementById('suppliersFilterForm');
            if (!form || !table) return;

            let typingTimer;
            const delay = 400;

            const searchInput = form.querySelector('[name="q"]');
            if (searchInput) {
                searchInput.addEventListener('keyup', function () {
                    clearTimeout(typingTimer);
                    typingTimer = setTimeout(() => table.draw(), delay);
                });
            }

            form.querySelectorAll('select,input[type="date"]').forEach(el => {
                el.addEventListener('change', function () {
                    table.draw();
                });
            });

            // Reset بدون Reload
            const resetBtn = document.getElementById('resetSuppliersFilters');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    form.querySelectorAll('input[type="text"], input[type="date"]').forEach(i => i.value = '');
                    form.querySelectorAll('select').forEach(s => s.value = '');
                    table.draw();
                });
            }
        });
    </script>
@endsection
