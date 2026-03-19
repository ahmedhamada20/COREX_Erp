@extends('admin.layouts.master')

@section('title', 'قيد يدوي جديد')

@section('content')
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">إضافة سند قيد يدوي</h4>
                <p class="text-muted mb-0">القيد يجب أن يكون متوازن قبل الحفظ.</p>
            </div>
            <a href="{{ route('journal_entries.index') }}" class="btn btn-light btn-sm">عودة</a>
        </div>
    </div>

    @include('admin.Alerts')

    <form method="POST" action="{{ route('journal_entries.store') }}">
        @csrf

        <div class="card mb-3">
            <div class="card-body row g-2">
                <div class="col-md-3">
                    <label class="form-label">تاريخ القيد</label>
                    <input type="date" name="entry_date" class="form-control" value="{{ old('entry_date', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-9">
                    <label class="form-label">الوصف</label>
                    <input type="text" name="description" class="form-control" value="{{ old('description') }}" placeholder="وصف اختياري">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm" id="lines-table">
                        <thead class="table-light">
                        <tr>
                            <th>الحساب</th>
                            <th>مدين</th>
                            <th>دائن</th>
                            <th>بيان</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                        <tr>
                            <th class="text-end">الإجمالي</th>
                            <th><input type="text" id="total-debit" class="form-control form-control-sm" readonly></th>
                            <th><input type="text" id="total-credit" class="form-control form-control-sm" readonly></th>
                            <th colspan="2"></th>
                        </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" id="add-line" class="btn btn-outline-primary btn-sm">إضافة سطر</button>
                    <button type="submit" class="btn btn-primary btn-sm">حفظ القيد</button>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('js')
    <script>
        (function () {
            const accounts = @json(
            $accounts->map(fn ($a) => [
                'id' => $a->id,
                'text' => ($a->account_number ?: '—') . ' - ' . $a->name
            ])->values()
        );

            const tbody = document.querySelector('#lines-table tbody');
            const addButton = document.getElementById('add-line');
            const debitTotalEl = document.getElementById('total-debit');
            const creditTotalEl = document.getElementById('total-credit');

            const rowTemplate = (index) => {
                const options = accounts.map(a => `<option value="${a.id}">${a.text}</option>`).join('');
                return `
                <tr>
                    <td>
                        <select name="lines[${index}][account_id]" class="form-select form-select-sm" required>
                            <option value="">اختر الحساب</option>
                            ${options}
                        </select>
                    </td>
                    <td><input type="number" step="0.0001" min="0" name="lines[${index}][debit]" class="form-control form-control-sm amount-debit"></td>
                    <td><input type="number" step="0.0001" min="0" name="lines[${index}][credit]" class="form-control form-control-sm amount-credit"></td>
                    <td><input type="text" name="lines[${index}][memo]" class="form-control form-control-sm"></td>
                    <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-line">حذف</button></td>
                </tr>
            `;
            };

            const recalc = () => {
                let debit = 0;
                let credit = 0;

                document.querySelectorAll('.amount-debit').forEach((input) => {
                    debit += parseFloat(input.value || '0');
                });

                document.querySelectorAll('.amount-credit').forEach((input) => {
                    credit += parseFloat(input.value || '0');
                });

                debitTotalEl.value = debit.toFixed(4);
                creditTotalEl.value = credit.toFixed(4);
            };

            const reindex = () => {
                tbody.querySelectorAll('tr').forEach((tr, idx) => {
                    tr.querySelectorAll('select, input').forEach((field) => {
                        field.name = field.name.replace(/lines\[\d+\]/, `lines[${idx}]`);
                    });
                });
            };

            addButton.addEventListener('click', () => {
                const index = tbody.querySelectorAll('tr').length;
                tbody.insertAdjacentHTML('beforeend', rowTemplate(index));
                recalc();
            });

            tbody.addEventListener('input', recalc);

            tbody.addEventListener('click', (event) => {
                if (event.target.classList.contains('remove-line')) {
                    event.target.closest('tr').remove();
                    reindex();
                    recalc();
                }
            });

            addButton.click();
            addButton.click();
        })();
    </script>
@endsection
