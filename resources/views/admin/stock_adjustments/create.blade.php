@extends('admin.layouts.master')

@section('title', 'تسوية مخزون جديدة')

@section('content')
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h4 class="mb-0">إضافة تسوية مخزون</h4>
            <a href="{{ route('stock_adjustments.index') }}" class="btn btn-light btn-sm">عودة</a>
        </div>
    </div>

    @include('admin.Alerts')

    <form method="POST" action="{{ route('stock_adjustments.store') }}">
        @csrf

        <div class="card mb-3">
            <div class="card-body row g-2">
                <div class="col-md-3">
                    <label class="form-label">تاريخ التسوية</label>
                    <input type="date" name="adjustment_date" class="form-control" value="{{ old('adjustment_date', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-9">
                    <label class="form-label">ملاحظات</label>
                    <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="سبب التسوية أو ملاحظات إضافية">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table table-sm" id="adjustment-lines-table">
                    <thead class="table-light">
                    <tr>
                        <th>الصنف</th>
                        <th>المخزن</th>
                        <th>فرق الكمية (+/-)</th>
                        <th>تكلفة الوحدة</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <div class="d-flex gap-2">
                    <button type="button" id="add-adjustment-line" class="btn btn-outline-primary btn-sm">إضافة سطر</button>
                    <button type="submit" class="btn btn-primary btn-sm">حفظ التسوية</button>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('js')
<script>
    (function () {
        const items = @json($items->map(fn ($item) => ['id' => $item->id, 'text' => $item->name])->values());
        const stores = @json($stores->map(fn ($store) => ['id' => $store->id, 'text' => $store->name])->values());

        const tbody = document.querySelector('#adjustment-lines-table tbody');
        const addButton = document.getElementById('add-adjustment-line');

        const lineTemplate = (index) => {
            const itemOptions = items.map((item) => `<option value="${item.id}">${item.text}</option>`).join('');
            const storeOptions = stores.map((store) => `<option value="${store.id}">${store.text}</option>`).join('');

            return `
                <tr>
                    <td>
                        <select name="lines[${index}][item_id]" class="form-select form-select-sm" required>
                            <option value="">اختر الصنف</option>
                            ${itemOptions}
                        </select>
                    </td>
                    <td>
                        <select name="lines[${index}][store_id]" class="form-select form-select-sm">
                            <option value="">بدون مخزن محدد</option>
                            ${storeOptions}
                        </select>
                    </td>
                    <td>
                        <input name="lines[${index}][quantity_diff]" type="number" step="0.0001" class="form-control form-control-sm" required>
                    </td>
                    <td>
                        <input name="lines[${index}][unit_cost]" type="number" min="0" step="0.0001" class="form-control form-control-sm" value="0">
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-line">حذف</button>
                    </td>
                </tr>
            `;
        };

        const reindex = () => {
            tbody.querySelectorAll('tr').forEach((row, index) => {
                row.querySelectorAll('select, input').forEach((field) => {
                    field.name = field.name.replace(/lines\[\d+\]/, `lines[${index}]`);
                });
            });
        };

        addButton.addEventListener('click', () => {
            const index = tbody.querySelectorAll('tr').length;
            tbody.insertAdjacentHTML('beforeend', lineTemplate(index));
        });

        tbody.addEventListener('click', (event) => {
            if (event.target.classList.contains('remove-line')) {
                event.target.closest('tr').remove();
                reindex();
            }
        });

        addButton.click();
    })();
</script>
@endsection

