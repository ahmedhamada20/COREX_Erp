<?php

namespace App\DataTables;

use App\Models\Treasuries;
use App\Models\TreasuriesDelivery;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class TreasuriesDeliveriesDataTable extends DataTable
{
    protected Treasuries $treasury;

    public function withTreasury(Treasuries $treasury): static
    {
        $this->treasury = $treasury;

        return $this;
    }

    public function dataTable($query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()

            ->editColumn('type', function ($row) {
                return match ($row->type) {
                    'in' => '<span class="badge bg-success">إيداع</span>',
                    'out' => '<span class="badge bg-danger">صرف</span>',
                    'transfer' => '<span class="badge bg-warning">تحويل</span>',
                    default => '<span class="badge bg-secondary">—</span>',
                };
            })

            ->addColumn('from', fn ($row) => e($row->fromTreasury?->name ?? '-'))
            ->addColumn('to', fn ($row) => e($row->toTreasury?->name ?? '-'))
            ->editColumn('amount', fn ($row) => number_format((float) $row->amount, 2))
            ->editColumn('reason', fn ($row) => e($row->reason ?? '-'))
            ->addColumn('user_name', fn ($row) => e($row->user?->name ?? '-'))

            ->editColumn('created_at', function ($row) {
                if (empty($row->created_at)) {
                    return '-';
                }

                return $row->created_at->timezone('Africa/Cairo')->format('Y-m-d h:i A');
            })

            ->addColumn('actions', function ($row) {
                return view('admin.treasuries.deliveries.partials.actions', [
                    'treasury' => $this->treasury,
                    'delivery' => $row,
                ])->render();
            })

            ->rawColumns(['type', 'actions']);
    }

    public function query(TreasuriesDelivery $model): QueryBuilder
    {
        // ✅ Tenant/Owner scope
        $ownerId = auth()->user()->owner_user_id ?? auth()->id();

        // ✅ حماية: تأكد إن الخزنة نفسها تبع الـ owner
        // لو مش تبعه: رجّع Query فاضي بدل ما نعرض داتا غلط
        if (($this->treasury->user_id ?? null) !== $ownerId) {
            return $model->newQuery()->whereRaw('1=0');
        }

        return $model->newQuery()
            ->with([
                'user:id,name',
                'fromTreasury:id,name',
                'toTreasury:id,name',
            ])
            ->where('user_id', $ownerId)
            ->where(function ($q) {
                $q->where('from_treasury_id', $this->treasury->id)
                    ->orWhere('to_treasury_id', $this->treasury->id);
            });
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('treasury-deliveries-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->serverSide()
            ->processing()
            ->orderBy(7, 'desc')
            ->language([
                'search' => 'بحث:',
                'lengthMenu' => 'عرض _MENU_',
                'info' => 'عرض _START_ إلى _END_ من _TOTAL_',
                'zeroRecords' => 'لا توجد نتائج',
                'infoEmpty' => 'لا توجد بيانات',
                'paginate' => [
                    'first' => 'الأول',
                    'last' => 'الأخير',
                    'next' => 'التالي',
                    'previous' => 'السابق',
                ],
            ]);
    }

    protected function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')
                ->title('#')
                ->searchable(false)
                ->orderable(false),

            Column::make('type')->title('النوع')->searchable(false)->orderable(false),
            Column::computed('from')->title('من')->orderable(false),
            Column::computed('to')->title('إلى')->orderable(false),
            Column::make('amount')->title('المبلغ'),
            Column::make('reason')->title('السبب'),
            Column::computed('user_name')->title('بواسطة')->orderable(false),
            Column::make('created_at')->title('التاريخ'),

            Column::computed('actions')
                ->title('إجراءات')
                ->searchable(false)
                ->orderable(false),
        ];
    }

    protected function filename(): string
    {
        return 'TreasuriesDeliveries_'.date('YmdHis');
    }
}
