<?php

namespace App\DataTables;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;

class CustomersDataTable extends DataTable
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
                $url = route('customers.toggle-status', $row->id);

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
                return view('admin.customers.partials.actions', compact('row'))->render();
            })
            ->rawColumns(['actions', 'status']);
    }

    public function query(Customer $model): QueryBuilder
    {
        $ownerId = auth()->user()->owner_user_id ?? auth()->id();

        $q = $model->newQuery()
            ->where('user_id', $ownerId)
            ->select('customers.*')
            ->orderByDesc('customers.id');

        // Filters (external)
        if (request()->filled('status')) {
            $q->where('status', (int) request('status'));
        }

        if ($city = trim((string) request('city'))) {
            $q->where('city', 'like', "%{$city}%");
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
            ->setTableId('customers-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'q' => '$("#customersFilterForm [name=q]").val()',
                'status' => '$("#customersFilterForm [name=status]").val()',
                'city' => '$("#customersFilterForm [name=city]").val()',
                'date_from' => '$("#customersFilterForm [name=date_from]").val()',
                'date_to' => '$("#customersFilterForm [name=date_to]").val()',
            ])
            ->responsive(true)
            ->processing(true)
            ->serverSide(true)
            ->orderBy(10, 'desc')
            ->parameters([
                'searchDelay' => 400, // ✅ يقلل requests من بحث الجدول نفسه
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
            ['data' => 'code', 'name' => 'code', 'title' => 'كود العميل'],
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
        return 'Customers_'.date('YmdHis');
    }
}
