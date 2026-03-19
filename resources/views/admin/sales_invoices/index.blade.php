{{-- resources/views/admin/sales_invoices/index.blade.php --}}
@extends('admin.layouts.master')

@section('title', 'فواتير المبيعات')

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

    {{-- Header --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-sm-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-0">فواتير المبيعات</h4>
                    <small class="text-muted">إدارة فواتير المبيعات + البحث والتصفية + إجراءات (ترحيل/إلغاء/حذف)</small>
                </div>

                <div class="d-flex gap-2 mt-2 mt-sm-0">
                    <a href="{{ route('sales_invoices.create') }}" class="btn btn-primary btn-sm">
                        <i class="ti ti-plus"></i> إضافة فاتورة
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" id="salesInvoicesFilterForm" class="row g-2 align-items-end">

                <div class="col-md-3">
                    <label class="form-label">بحث</label>
                    <input type="text" name="q" value="{{ request('q') }}"
                           class="form-control form-control-sm"
                           placeholder="كود الفاتورة / رقم الفاتورة">
                </div>

                <div class="col-md-2">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        <option value="draft" @selected(request('status') === 'draft')>مسودة</option>
                        <option value="posted" @selected(request('status') === 'posted')>مُرحّلة</option>
                        <option value="paid" @selected(request('status') === 'paid')>مدفوعة</option>
                        <option value="partial" @selected(request('status') === 'partial')>جزئي</option>
                        <option value="cancelled" @selected(request('status') === 'cancelled')>ملغاة</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">نوع الدفع</label>
                    <select name="payment_type" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        <option value="cash" @selected(request('payment_type') === 'cash')>كاش</option>
                        <option value="credit" @selected(request('payment_type') === 'credit')>آجل</option>
                    </select>
                </div>

                {{-- ✅ Customer AJAX Select2 --}}
                <div class="col-md-5">
                    <label class="form-label" for="customer_id">العميل</label>

                    <select name="customer_id"
                            id="customer_id"
                            class="form-select select2-ajax"
                            data-placeholder="ابحث بالاسم / الهاتف / الكود">
                        <option value="">الكل</option>

                        {{-- عشان لو الصفحة اتفتحت وفيها customer_id من request --}}
                        @if(request()->filled('customer_id') && request()->filled('customer_text'))
                            <option value="{{ request('customer_id') }}" selected>{{ request('customer_text') }}</option>
                        @endif
                    </select>

                    <input type="hidden" name="customer_text" id="customer_text" value="{{ request('customer_text') }}">
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="button" id="resetSalesInvoicesFilters" class="btn btn-light btn-sm">
                        <i class="ti ti-refresh"></i> إعادة ضبط
                    </button>
                </div>

            </form>
        </div>
    </div>

    @include('admin.Alerts')

    {{-- Table --}}
    <div class="card table-card">
        <div class="card-body">
            <div class="table-responsive">
                {!! $dataTable->table([
                    'id' => 'sales-invoices-table',
                    'class' => 'table table-striped table-row-bordered gy-5 gs-7 text-end w-100'
                ], true) !!}
            </div>
        </div>
    </div>

@endsection

@section('js')
    {{-- لو jQuery محمّل في master احذف السطر ده --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    {{-- DataTables --}}
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    {{-- Select2 --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    {!! $dataTable->scripts() !!}

    <script>
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });

        // ✅ Customer AJAX Select2
        $(function () {
            const $customer = $('#customer_id');

            $customer.select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                placeholder: $customer.data('placeholder') || 'ابحث...',
                ajax: {
                    url: "{{ route('customers.select2') }}",
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            page: params.page || 1
                        };
                    },
                    processResults: function (data) {
                        return data;
                    },
                    cache: true
                },
                minimumInputLength: 1
            });

            // نخزن النص عشان لو عمل refresh للصفحة يبقى ظاهر
            $customer.on('select2:select', function (e) {
                $('#customer_text').val(e.params.data.text || '');
            });

            $customer.on('select2:clear', function () {
                $('#customer_text').val('');
            });
        });
    </script>

    {{-- Actions: Post / Cancel --}}
    <script>
        $(document).on('click', '.js-post', function () {
            const url = $(this).data('url');
            if (!url) return;
            if (!confirm('تأكيد ترحيل الفاتورة؟')) return;

            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                success: function (res) {
                    if (res && res.success === false) {
                        toastr.error(res.message || 'فشل ترحيل الفاتورة');
                        return;
                    }
                    toastr.success(res?.message || 'تم ترحيل الفاتورة');
                    window.LaravelDataTables["sales-invoices-table"].ajax.reload(null, false);
                },
                error: function (xhr) {
                    const msg =
                        xhr?.responseJSON?.message ||
                        (xhr?.responseJSON?.errors ? Object.values(xhr.responseJSON.errors).flat()[0] : null) ||
                        'حدث خطأ أثناء الترحيل';
                    toastr.error(msg);
                }
            });
        });

        $(document).on('click', '.js-cancel', function () {
            const url = $(this).data('url');
            if (!url) return;
            if (!confirm('هل أنت متأكد من إلغاء الفاتورة؟')) return;

            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                success: function (res) {
                    if (res && res.success === false) {
                        toastr.error(res.message || 'فشل إلغاء الفاتورة');
                        return;
                    }
                    toastr.success(res?.message || 'تم إلغاء الفاتورة');
                    window.LaravelDataTables["sales-invoices-table"].ajax.reload(null, false);
                },
                error: function (xhr) {
                    const msg =
                        xhr?.responseJSON?.message ||
                        (xhr?.responseJSON?.errors ? Object.values(xhr.responseJSON.errors).flat()[0] : null) ||
                        'حدث خطأ أثناء الإلغاء';
                    toastr.error(msg);
                }
            });
        });
    </script>

    {{-- Filters -> redraw datatable --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = window.LaravelDataTables["sales-invoices-table"];
            const form  = document.getElementById('salesInvoicesFilterForm');
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

            // أي select/date change -> draw
            form.querySelectorAll('select,input[type="date"]').forEach(el => {
                el.addEventListener('change', function () {
                    table.draw();
                });
            });

            // customer select2 change -> draw
            $('#customer_id').on('change', function () {
                table.draw();
            });

            const resetBtn = document.getElementById('resetSalesInvoicesFilters');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    form.querySelectorAll('input[type="text"], input[type="date"]').forEach(i => i.value = '');
                    form.querySelectorAll('select').forEach(s => s.value = '');

                    // reset select2
                    $('#customer_id').val(null).trigger('change');
                    $('#customer_text').val('');

                    table.draw();
                });
            }
        });
    </script>

@endsection
