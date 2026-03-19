<?php

namespace App\DataTables;

use App\Models\Treasuries;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;

class TreasuriesDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()

            ->editColumn('is_master', function ($row) {
                return $row->is_master
                    ? '<span class="badge bg-primary">رئيسية</span>'
                    : '<span class="badge bg-secondary">فرعية</span>';
            })

            ->editColumn('status', function ($row) {
                return $row->status
                    ? '<span class="badge bg-success">مفعل</span>'
                    : '<span class="badge bg-danger">غير مفعل</span>';
            })

            ->editColumn('updated_at', function ($row) {
                if (empty($row->updated_at) || empty($row->updated_by)) {
                    return '<span class="text-muted small">—</span>';
                }

                $updatedAt = $row->updated_at->timezone('Africa/Cairo');
                $period = ((int) $updatedAt->format('H')) < 12 ? 'صباحًا' : 'مساءً';
                $formattedDate = $updatedAt->translatedFormat('d M Y - h:i');

                return '
                    <div class="px-2 py-1 rounded bg-light border small">
                        <div class="d-flex align-items-center gap-1 text-muted">
                            <i class="ti ti-clock fs-6"></i>
                            <span class="fw-semibold text-dark">'.e($row->updated_by).'</span>
                            <span class="mx-1">•</span>
                            <span>'.e($formattedDate).' '.e($period).'</span>
                        </div>
                    </div>
                ';
            })

            ->addColumn('shift_status', function ($row) {
                $count = (int) ($row->open_shift_count ?? 0);

                if ($count > 0) {
                    return '<span class="badge bg-warning text-dark">شفت مفتوح</span>';
                }

                return '<span class="badge bg-light text-dark">لا يوجد شفت</span>';
            })

            ->addColumn('actions', function ($row) {
                return view('admin.treasuries.datatables.actions', compact('row'))->render();
            })

            // ✅ لازم نضيف shift_status هنا
            ->rawColumns(['is_master', 'status', 'shift_status', 'updated_at', 'actions']);
    }

    public function query(Treasuries $model): QueryBuilder
    {
        $ownerId = auth()->user()->owner_user_id ?? auth()->id();

        return $model->newQuery()
            ->where('user_id', $ownerId)
            ->select([
                'id',
                'name',
                'is_master',
                'last_payment_receipt_no',
                'last_collection_receipt_no',
                'status',
                'updated_at',
                'updated_by',
            ])
            ->withCount([
                'shifts as open_shift_count' => function ($q) {
                    $q->where('status', 'open');
                },
            ])
            ->orderByDesc('id');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('treasuries-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1) // name
            ->responsive(true)
            ->parameters([
                'language' => [
                    'url' => '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json',
                ],
            ]);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم الخزنة'],
            ['data' => 'is_master', 'name' => 'is_master', 'title' => 'النوع'],
            ['data' => 'last_payment_receipt_no', 'name' => 'last_payment_receipt_no', 'title' => 'آخر إيصال صرف'],
            ['data' => 'last_collection_receipt_no', 'name' => 'last_collection_receipt_no', 'title' => 'آخر إيصال تحصيل'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة'],

            // ✅ عمود الشفت
            ['data' => 'shift_status', 'name' => 'shift_status', 'title' => 'حالة الشفت', 'orderable' => false, 'searchable' => false],

            ['data' => 'updated_at', 'name' => 'updated_at', 'title' => 'آخر تحديث'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'إجراءات', 'orderable' => false, 'searchable' => false],
        ];
    }

    protected function filename(): string
    {
        return 'Treasuries_'.date('YmdHis');
    }
}
