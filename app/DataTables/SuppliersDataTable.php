<?php

namespace App\DataTables;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;

class SuppliersDataTable extends DataTable
{
    protected function tenantId(): int
    {
        $u = auth()->user();

        return (int) ($u->owner_user_id ?? $u->id);
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()

            ->editColumn('status', function ($row) {
                $checked = $row->status ? 'checked' : '';
                $url = route('suppliers.toggle-status', $row->id);

                return '
                    <label class="switch">
                        <input type="checkbox" class="js-toggle-status" data-url="'.$url.'" '.$checked.'>
                        <span class="slider round"></span>
                    </label>
                ';
            })

            ->editColumn('start_balance', fn ($row) => number_format((float) $row->start_balance, 2))
            ->editColumn('current_balance', fn ($row) => number_format((float) $row->current_balance, 2))
            ->editColumn('created_at', fn ($row) => optional($row->created_at)?->format('Y-m-d H:i'))

            ->addColumn('actions', function ($row) {
                return view('admin.suppliers.partials.actions', compact('row'))->render();
            })

            ->rawColumns(['actions', 'status']);
    }

    public function query(Supplier $model): QueryBuilder
    {
        $ownerId = $this->tenantId();

        $q = $model->newQuery()
            ->where('user_id', $ownerId)
            ->select('suppliers.*')
            ->orderByDesc('suppliers.id');

        if (request()->filled('status')) {
            $q->where('status', (int) request('status'));
        }

        if ($city = trim((string) request('city'))) {
            $q->where('city', 'like', "%{$city}%");
        }

        if ($cat = request('supplier_category_id')) {
            $q->where('supplier_category_id', (int) $cat);
        }

        if (request()->filled('date_from')) {
            $q->whereDate('created_at', '>=', request('date_from'));
        }

        if (request()->filled('date_to')) {
            $q->whereDate('created_at', '<=', request('date_to'));
        }

        $external = trim((string) request('q'));
        if ($external !== '') {
            $q->where(function ($qq) use ($external) {
                $qq->where('name', 'like', "%{$external}%")
                    ->orWhere('code', 'like', "%{$external}%")
                    ->orWhere('phone', 'like', "%{$external}%")
                    ->orWhere('account_number', 'like', "%{$external}%")
                    ->orWhere('email', 'like', "%{$external}%")
                    ->orWhere('city', 'like', "%{$external}%");
            });
        }

        $dt = request('search');
        $internal = is_array($dt) ? trim((string) ($dt['value'] ?? '')) : '';
        if ($internal !== '') {
            $q->where(function ($qq) use ($internal) {
                $qq->where('name', 'like', "%{$internal}%")
                    ->orWhere('code', 'like', "%{$internal}%")
                    ->orWhere('phone', 'like', "%{$internal}%")
                    ->orWhere('account_number', 'like', "%{$internal}%")
                    ->orWhere('email', 'like', "%{$internal}%")
                    ->orWhere('city', 'like', "%{$internal}%");
            });
        }

        return $q;
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('suppliers-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'q' => '$("#suppliersFilterForm [name=q]").val()',
                'status' => '$("#suppliersFilterForm [name=status]").val()',
                'city' => '$("#suppliersFilterForm [name=city]").val()',
                'supplier_category_id' => '$("#suppliersFilterForm [name=supplier_category_id]").val()',
                'date_from' => '$("#suppliersFilterForm [name=date_from]").val()',
                'date_to' => '$("#suppliersFilterForm [name=date_to]").val()',
            ])
            ->responsive(true)
            ->processing(true)
            ->serverSide(true)
            ->orderBy(10, 'desc')
            ->parameters([
                'searchDelay' => 400,
                'dom' => "<'row align-items-center mb-2'<'col-md-6'l><'col-md-6 text-end'f>>"
                    .'rt'
                    ."<'row align-items-center mt-2'<'col-md-6'i><'col-md-6 text-end'p>>",
                'language' => [
                    'url' => 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json',
                ],
            ]);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false],

            ['data' => 'name', 'name' => 'name', 'title' => 'الاسم'],
            ['data' => 'code', 'name' => 'code', 'title' => 'كود المورد'],
            ['data' => 'phone', 'name' => 'phone', 'title' => 'الهاتف'],
            ['data' => 'city', 'name' => 'city', 'title' => 'المدينة'],
            ['data' => 'account_number', 'name' => 'account_number', 'title' => 'رقم الحساب'],
            ['data' => 'start_balance', 'name' => 'start_balance', 'title' => 'رصيد افتتاحي'],
            ['data' => 'current_balance', 'name' => 'current_balance', 'title' => 'الرصيد الحالي'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة'],
            ['data' => 'updated_by', 'name' => 'updated_by', 'title' => 'بواسطة'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإضافة'],

            ['data' => 'actions', 'name' => 'actions', 'title' => 'إجراءات', 'orderable' => false, 'searchable' => false],
        ];
    }

    protected function filename(): string
    {
        return 'Suppliers_'.date('YmdHis');
    }
}
