@extends('admin.layouts.master')

@section('title', 'أمر شراء جديد')

@section('content')
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h4 class="mb-0">إضافة أمر شراء</h4>
            <a href="{{ route('purchase_orders.index') }}" class="btn btn-light btn-sm">عودة</a>
        </div>
    </div>

    @include('admin.Alerts')

    <form method="POST" action="{{ route('purchase_orders.store') }}">
        @csrf
        <div class="card mb-3">
            <div class="card-body row g-2">
                <div class="col-md-4">
                    <label class="form-label">المورد</label>
                    <select name="supplier_id" class="form-select" required>
                        <option value="">اختر</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">تاريخ الأمر</label>
                    <input type="date" name="order_date" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">تاريخ التوريد المتوقع</label>
                    <input type="date" name="expected_date" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">ضريبة</label>
                    <input type="number" step="0.01" min="0" name="tax_amount" class="form-control" value="0">
                </div>
                <div class="col-12">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table table-sm" id="items-table">
                    <thead class="table-light">
                    <tr>
                        <th>الصنف</th>
                        <th>الكمية</th>
                        <th>سعر الوحدة</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <button type="button" class="btn btn-outline-primary btn-sm" id="add-item">إضافة سطر</button>
                <button type="submit" class="btn btn-primary btn-sm">حفظ</button>
            </div>
        </div>
    </form>
@endsection

@section('js')
<script>
    (function () {
        const rows = document.querySelector('#items-table tbody');
        const addButton = document.getElementById('add-item');
        const items = @json($items->map(fn ($item) => ['id' => $item->id, 'name' => $item->name])->values());

        const template = (index) => {
            const options = items.map((item) => `<option value="${item.id}">${item.name}</option>`).join('');
            return `
                <tr>
                    <td><select class="form-select form-select-sm" name="items[${index}][item_id]" required><option value="">اختر</option>${options}</select></td>
                    <td><input class="form-control form-control-sm" type="number" step="0.0001" min="0.0001" name="items[${index}][quantity]" required></td>
                    <td><input class="form-control form-control-sm" type="number" step="0.0001" min="0" name="items[${index}][unit_price]" required></td>
                    <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-item">حذف</button></td>
                </tr>
            `;
        };

        const reindex = () => {
            rows.querySelectorAll('tr').forEach((tr, idx) => {
                tr.querySelectorAll('select, input').forEach((field) => {
                    field.name = field.name.replace(/items\[\d+\]/, `items[${idx}]`);
                });
            });
        };

        addButton.addEventListener('click', () => {
            rows.insertAdjacentHTML('beforeend', template(rows.querySelectorAll('tr').length));
        });

        rows.addEventListener('click', (event) => {
            if (event.target.classList.contains('remove-item')) {
                event.target.closest('tr').remove();
                reindex();
            }
        });

        addButton.click();
    })();
</script>
@endsection

