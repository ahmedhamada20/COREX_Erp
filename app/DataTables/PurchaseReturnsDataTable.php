<?php

namespace App\DataTables;

use App\Models\PurchaseReturn;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;

class PurchaseReturnsDataTable extends DataTable
{
    protected function ownerId(): int
    {
        $u = auth()->user();

        return (int) ($u->owner_user_id ?? $u->id);
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()

            ->addColumn('supplier', fn ($row) => $row->supplier?->name ?? '-')

            ->addColumn('invoice', function ($row) {
                // اختياري لو عندك purchase_invoice_id
                if (! $row->purchase_invoice_id) {
                    return '-';
                }

                return $row->invoice?->purchase_invoice_code
                    ?? $row->invoice?->invoice_number
                    ?? ('#'.$row->purchase_invoice_id);
            })

            ->editColumn('return_date', fn ($row) => $row->return_date ? \Carbon\Carbon::parse($row->return_date)->format('Y-m-d') : '-')

            ->editColumn('status', function ($row) {
                // خليك بسيط لحد ما تعمل مالية: draft / posted / cancelled
                return match ($row->status) {
                    'draft' => '<span class="badge bg-secondary">مسودة</span>',
                    'posted' => '<span class="badge bg-primary">مُرحّل</span>',
                    'cancelled' => '<span class="badge bg-danger">ملغى</span>',
                    default => '<span class="badge bg-light text-dark">'.e($row->status).'</span>',
                };
            })

            ->editColumn('total', fn ($row) => number_format((float) ($row->total ?? 0), 2))

            ->editColumn('created_at', fn ($row) => optional($row->created_at)?->format('Y-m-d H:i'))

            ->addColumn('actions', function ($row) {

                $show = route('purchase_returns.show', $row->id);
                $edit = route('purchase_returns.edit', $row->id);
                $destroy = route('purchase_returns.destroy', $row->id);

                $post = route('purchase_returns.post', $row->id);
                $cancel = route('purchase_returns.cancel', $row->id);

                $canEdit = ! in_array($row->status, ['posted', 'cancelled'], true); // بعد الترحيل أو الإلغاء اقفل التعديل
                $canPost = in_array($row->status, ['draft'], true);
                $canCancel = ! in_array($row->status, ['cancelled'], true);

                $html = '<div class="dropdown">
                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa fa-cog me-1"></i> إجراءات
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">';

                // عرض
                $html .= '
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="'.$show.'">
                            <i class="fa fa-eye text-info"></i>
                            <div>
                                <div class="fw-semibold">عرض المرتجع</div>
                                <small class="text-muted">فتح تفاصيل المرتجع</small>
                            </div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                ';

                // تعديل
                if ($canEdit) {
                    $html .= '
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2" href="'.$edit.'">
                                <i class="fa fa-edit text-success"></i>
                                <div>
                                    <div class="fw-semibold">تعديل</div>
                                    <small class="text-muted">تعديل بيانات المرتجع قبل الترحيل</small>
                                </div>
                            </a>
                        </li>
                    ';
                } else {
                    $html .= '
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2" type="button" disabled>
                                <i class="fa fa-edit text-muted"></i>
                                <div>
                                    <div class="fw-semibold text-muted">تعديل</div>
                                    <small class="text-muted">غير متاح (مُرحّل/ملغى)</small>
                                </div>
                            </button>
                        </li>
                    ';
                }

                // ترحيل
                if ($canPost) {
                    $html .= '
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2 js-post"
                                    type="button" data-url="'.$post.'">
                                <i class="fa fa-check text-primary"></i>
                                <div>
                                    <div class="fw-semibold">ترحيل</div>
                                    <small class="text-muted">اعتماد المرتجع وترحيله</small>
                                </div>
                            </button>
                        </li>
                    ';
                } else {
                    $html .= '
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2" type="button" disabled>
                                <i class="fa fa-check text-muted"></i>
                                <div>
                                    <div class="fw-semibold text-muted">ترحيل</div>
                                    <small class="text-muted">متاح فقط للمسودة</small>
                                </div>
                            </button>
                        </li>
                    ';
                }

                // إلغاء
                if ($canCancel) {
                    $html .= '
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2 js-cancel"
                                    type="button" data-url="'.$cancel.'">
                                <i class="fa fa-ban text-warning"></i>
                                <div>
                                    <div class="fw-semibold">إلغاء</div>
                                    <small class="text-muted">إلغاء المرتجع ومنع أي تأثير لاحقًا</small>
                                </div>
                            </button>
                        </li>
                    ';
                } else {
                    $html .= '
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2" type="button" disabled>
                                <i class="fa fa-ban text-muted"></i>
                                <div>
                                    <div class="fw-semibold text-muted">إلغاء</div>
                                    <small class="text-muted">المرتجع بالفعل ملغى</small>
                                </div>
                            </button>
                        </li>
                    ';
                }

                $html .= '<li><hr class="dropdown-divider"></li>';

                // حذف
                $html .= '
                    <li>
                        <form action="'.$destroy.'" method="POST"
                              onsubmit="return confirm(\'هل أنت متأكد من الحذف؟\');">
                            '.csrf_field().method_field('DELETE').'
                            <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                <i class="fa fa-trash"></i>
                                <div>
                                    <div class="fw-semibold">حذف</div>
                                    <small class="text-muted">حذف المرتجع نهائيًا من النظام</small>
                                </div>
                            </button>
                        </form>
                    </li>
                ';

                $html .= '</ul></div>';

                return $html;
            })

            ->rawColumns(['status', 'actions']);
    }

    public function query(PurchaseReturn $model): QueryBuilder
    {
        $ownerId = $this->ownerId();

        $q = $model->newQuery()
            ->where('user_id', $ownerId)
            ->select([
                'purchase_returns.id',
                'purchase_returns.supplier_id',
                'purchase_returns.purchase_invoice_id',
                'purchase_returns.purchase_return_code',
                'purchase_returns.return_number',
                'purchase_returns.return_date',
                'purchase_returns.total',
                'purchase_returns.status',
                'purchase_returns.updated_by',
                'purchase_returns.created_at',
            ])
            ->with([
                'supplier:id,name',
                'invoice:id,purchase_invoice_code,invoice_number',
            ])
            ->orderByDesc('purchase_returns.id');

        // External filters
        if (request()->filled('status')) {
            $q->where('status', request('status'));
        }

        if (request()->filled('supplier_id')) {
            $q->where('supplier_id', (int) request('supplier_id'));
        }

        if (request()->filled('purchase_invoice_id')) {
            $q->where('purchase_invoice_id', (int) request('purchase_invoice_id'));
        }

        if (request()->filled('date_from')) {
            $q->whereDate('return_date', '>=', request('date_from'));
        }

        if (request()->filled('date_to')) {
            $q->whereDate('return_date', '<=', request('date_to'));
        }

        // External q search
        $external = trim((string) request('q'));
        if ($external !== '') {
            $q->where(function ($qq) use ($external) {
                $qq->where('purchase_return_code', 'like', "%{$external}%")
                    ->orWhere('return_number', 'like', "%{$external}%");
            });
        }

        // Internal datatables search
        $dt = request('search');
        $internal = is_array($dt) ? trim((string) ($dt['value'] ?? '')) : '';
        if ($internal !== '') {
            $q->where(function ($qq) use ($internal) {
                $qq->where('purchase_return_code', 'like', "%{$internal}%")
                    ->orWhere('return_number', 'like', "%{$internal}%");
            });
        }

        return $q;
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('purchase-returns-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'q' => '$("#purchaseReturnsFilterForm [name=q]").val()',
                'status' => '$("#purchaseReturnsFilterForm [name=status]").val()',
                'supplier_id' => '$("#purchaseReturnsFilterForm [name=supplier_id]").val()',
                'purchase_invoice_id' => '$("#purchaseReturnsFilterForm [name=purchase_invoice_id]").val()',
                'date_from' => '$("#purchaseReturnsFilterForm [name=date_from]").val()',
                'date_to' => '$("#purchaseReturnsFilterForm [name=date_to]").val()',
            ])
            ->responsive(true)
            ->processing(true)
            ->serverSide(true)
            ->orderBy(9, 'desc') // created_at index
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

            ['data' => 'purchase_return_code', 'name' => 'purchase_return_code', 'title' => 'كود المرتجع'],
            ['data' => 'return_number', 'name' => 'return_number', 'title' => 'رقم المرتجع'],

            ['data' => 'supplier', 'name' => 'supplier.name', 'title' => 'المورد', 'orderable' => false],

            // اختياري: ربط المرتجع بفاتورة
            ['data' => 'invoice', 'name' => 'invoice.purchase_invoice_code', 'title' => 'فاتورة مشتريات', 'orderable' => false, 'searchable' => false],

            ['data' => 'return_date', 'name' => 'return_date', 'title' => 'تاريخ المرتجع'],

            ['data' => 'total', 'name' => 'total', 'title' => 'الإجمالي'],

            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة'],
            ['data' => 'updated_by', 'name' => 'updated_by', 'title' => 'بواسطة'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإضافة'],

            ['data' => 'actions', 'name' => 'actions', 'title' => 'إجراءات', 'orderable' => false, 'searchable' => false],
        ];
    }

    protected function filename(): string
    {
        return 'PurchaseReturns_'.date('YmdHis');
    }
}
