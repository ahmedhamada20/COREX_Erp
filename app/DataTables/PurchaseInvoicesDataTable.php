<?php

namespace App\DataTables;

use App\Models\PurchaseInvoice;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;

class PurchaseInvoicesDataTable extends DataTable
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

            ->editColumn('invoice_date', fn ($row) => $row->invoice_date ? \Carbon\Carbon::parse($row->invoice_date)->format('Y-m-d') : '-')
            ->editColumn('due_date', fn ($row) => $row->due_date ? \Carbon\Carbon::parse($row->due_date)->format('Y-m-d') : '-')

            ->editColumn('payment_type', function ($row) {
                return $row->payment_type === 'cash'
                    ? '<span class="badge bg-success">كاش</span>'
                    : '<span class="badge bg-warning text-dark">آجل</span>';
            })

            ->editColumn('status', function ($row) {
                return match ($row->status) {
                    'draft' => '<span class="badge bg-secondary">مسودة</span>',
                    'posted' => '<span class="badge bg-primary">مُرحّلة</span>',
                    'paid' => '<span class="badge bg-success">مدفوعة</span>',
                    'partial' => '<span class="badge bg-info text-dark">جزئي</span>',
                    'cancelled' => '<span class="badge bg-danger">ملغاة</span>',
                    default => '<span class="badge bg-light text-dark">'.e($row->status).'</span>',
                };
            })

            ->editColumn('total', fn ($row) => number_format((float) $row->total, 2))
            ->editColumn('paid_amount', fn ($row) => number_format((float) $row->paid_amount, 2))
            ->editColumn('remaining_amount', fn ($row) => number_format((float) $row->remaining_amount, 2))

            ->editColumn('created_at', fn ($row) => optional($row->created_at)?->format('Y-m-d H:i'))

            ->addColumn('actions', function ($row) {

                $show = route('purchase_invoices.show', $row->id);
                $edit = route('purchase_invoices.edit', $row->id);
                $destroy = route('purchase_invoices.destroy', $row->id);

                $post = route('purchase_invoices.post', $row->id);
                $cancel = route('purchase_invoices.cancel', $row->id);

                // ✅ إنشاء مرتجع من الفاتورة (محاسبيًا الصح)
                // لازم route موجود: purchase_returns.create_from_invoice
                $createReturn = route('purchase_returns.create_from_invoice', $row->id);

                $canEdit = ! in_array($row->status, ['paid', 'cancelled'], true);
                $canPost = in_array($row->status, ['draft'], true);
                $canCancel = ! in_array($row->status, ['cancelled'], true);

                // ✅ المرتجع يتعمل من فاتورة مُعتمدة (posted/paid/partial)
                $canReturn = in_array($row->status, ['posted', 'paid', 'partial'], true);

                // Bootstrap 5 dropdown
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
                                <div class="fw-semibold">عرض الفاتورة</div>
                                <small class="text-muted">فتح تفاصيل الفاتورة</small>
                            </div>
                        </a>
                    </li>
                ';

                // ✅ عمل مرتجع مشتريات
                if ($canReturn) {
                    $html .= '
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2" href="'.$createReturn.'">
                                <i class="fa fa-rotate-left text-warning"></i>
                                <div>
                                    <div class="fw-semibold">عمل مرتجع مشتريات</div>
                                    <small class="text-muted">إنشاء مرتجع من هذه الفاتورة</small>
                                </div>
                            </a>
                        </li>
                    ';
                } else {
                    $html .= '
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2" type="button" disabled>
                                <i class="fa fa-rotate-left text-muted"></i>
                                <div>
                                    <div class="fw-semibold text-muted">عمل مرتجع مشتريات</div>
                                    <small class="text-muted">متاح بعد الترحيل فقط</small>
                                </div>
                            </button>
                        </li>
                    ';
                }

                $html .= '<li><hr class="dropdown-divider"></li>';

                // تعديل
                if ($canEdit) {
                    $html .= '
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2" href="'.$edit.'">
                                <i class="fa fa-edit text-success"></i>
                                <div>
                                    <div class="fw-semibold">تعديل</div>
                                    <small class="text-muted">تعديل بيانات الفاتورة قبل الإغلاق</small>
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
                                    <small class="text-muted">غير متاح (مدفوعة/ملغاة)</small>
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
                                    <small class="text-muted">اعتماد الفاتورة وترحيلها للحسابات</small>
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
                                    <small class="text-muted">إلغاء الفاتورة ومنع أي تأثير محاسبي لاحقًا</small>
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
                                    <small class="text-muted">الفاتورة بالفعل ملغاة</small>
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
                                    <small class="text-muted">حذف الفاتورة نهائيًا من النظام</small>
                                </div>
                            </button>
                        </form>
                    </li>
                ';

                $html .= '</ul></div>';

                return $html;
            })

            ->rawColumns(['payment_type', 'status', 'actions']);
    }

    public function query(PurchaseInvoice $model): QueryBuilder
    {
        $ownerId = $this->ownerId();

        $q = $model->newQuery()
            ->where('user_id', $ownerId)
            ->select([
                'purchase_invoices.id',
                'purchase_invoices.supplier_id',
                'purchase_invoices.purchase_invoice_code',
                'purchase_invoices.invoice_number',
                'purchase_invoices.invoice_date',
                'purchase_invoices.payment_type',
                'purchase_invoices.due_date',
                'purchase_invoices.total',
                'purchase_invoices.paid_amount',
                'purchase_invoices.remaining_amount',
                'purchase_invoices.status',
                'purchase_invoices.updated_by',
                'purchase_invoices.created_at',
            ])
            ->with(['supplier:id,name'])
            ->orderByDesc('purchase_invoices.id');

        // External filters (زي Customers)
        if (request()->filled('status')) {
            $q->where('status', request('status'));
        }

        if (request()->filled('payment_type')) {
            $q->where('payment_type', request('payment_type'));
        }

        if (request()->filled('supplier_id')) {
            $q->where('supplier_id', (int) request('supplier_id'));
        }

        if (request()->filled('date_from')) {
            $q->whereDate('invoice_date', '>=', request('date_from'));
        }

        if (request()->filled('date_to')) {
            $q->whereDate('invoice_date', '<=', request('date_to'));
        }

        // External q search
        $external = trim((string) request('q'));
        if ($external !== '') {
            $q->where(function ($qq) use ($external) {
                $qq->where('purchase_invoice_code', 'like', "%{$external}%")
                    ->orWhere('invoice_number', 'like', "%{$external}%");
            });
        }

        // Internal datatables search
        $dt = request('search');
        $internal = is_array($dt) ? trim((string) ($dt['value'] ?? '')) : '';
        if ($internal !== '') {
            $q->where(function ($qq) use ($internal) {
                $qq->where('purchase_invoice_code', 'like', "%{$internal}%")
                    ->orWhere('invoice_number', 'like', "%{$internal}%");
            });
        }

        return $q;
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('purchase-invoices-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'q' => '$("#purchaseInvoicesFilterForm [name=q]").val()',
                'status' => '$("#purchaseInvoicesFilterForm [name=status]").val()',
                'payment_type' => '$("#purchaseInvoicesFilterForm [name=payment_type]").val()',
                'supplier_id' => '$("#purchaseInvoicesFilterForm [name=supplier_id]").val()',
                'date_from' => '$("#purchaseInvoicesFilterForm [name=date_from]").val()',
                'date_to' => '$("#purchaseInvoicesFilterForm [name=date_to]").val()',
            ])
            ->responsive(true)
            ->processing(true)
            ->serverSide(true)
            ->orderBy(11, 'desc') // created_at index (راجع الأعمدة تحت)
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

            ['data' => 'purchase_invoice_code', 'name' => 'purchase_invoice_code', 'title' => 'كود الفاتورة'],
            ['data' => 'invoice_number', 'name' => 'invoice_number', 'title' => 'رقم الفاتورة'],
            ['data' => 'supplier', 'name' => 'supplier.name', 'title' => 'المورد', 'orderable' => false],
            ['data' => 'invoice_date', 'name' => 'invoice_date', 'title' => 'تاريخ الفاتورة'],
            ['data' => 'payment_type', 'name' => 'payment_type', 'title' => 'نوع الدفع'],
            ['data' => 'due_date', 'name' => 'due_date', 'title' => 'تاريخ الاستحقاق'],
            ['data' => 'total', 'name' => 'total', 'title' => 'الإجمالي'],
            ['data' => 'paid_amount', 'name' => 'paid_amount', 'title' => 'المدفوع'],
            ['data' => 'remaining_amount', 'name' => 'remaining_amount', 'title' => 'المتبقي'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة'],
            ['data' => 'updated_by', 'name' => 'updated_by', 'title' => 'بواسطة'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإضافة'],

            ['data' => 'actions', 'name' => 'actions', 'title' => 'إجراءات', 'orderable' => false, 'searchable' => false],
        ];
    }

    protected function filename(): string
    {
        return 'PurchaseInvoices_'.date('YmdHis');
    }
}
