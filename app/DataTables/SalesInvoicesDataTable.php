<?php

namespace App\DataTables;

use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;

class SalesInvoicesDataTable extends DataTable
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

            // =========================
            // Computed Columns
            // =========================
            ->addColumn('customer', fn ($row) => $row->customer?->name ?? '-')

            // ✅ هل عليها مرتجع؟ + مبلغ المرتجع + المتبقي قابل للمرتجع
            ->addColumn('return_info', function ($row) {
                $count = (int) ($row->returns_count ?? 0);
                $sum = (float) ($row->returns_sum_total ?? 0);

                $badge = $count > 0
                    ? '<span class="badge bg-warning text-dark">عليها مرتجع ('.$count.')</span>'
                    : '<span class="badge bg-light text-dark">لا</span>';

                $sumHtml = $count > 0
                    ? '<div class="small text-muted mt-1">مجموع المرتجع: <b dir="ltr">'.number_format($sum, 2).'</b></div>'
                    : '<div class="small text-muted mt-1">—</div>';

                return $badge.$sumHtml;
            })

            ->addColumn('returnable_amount', function ($row) {
                $total = (float) ($row->total ?? 0);
                $sum = (float) ($row->returns_sum_total ?? 0);
                $remain = max(0, $total - $sum);

                return '<span dir="ltr" class="fw-bold">'.number_format($remain, 2).'</span>';
            })

            // =========================
            // Dates
            // =========================
            ->editColumn('invoice_date', fn ($row) => $row->invoice_date ? \Carbon\Carbon::parse($row->invoice_date)->format('Y-m-d') : '-')
            ->editColumn('due_date', fn ($row) => $row->due_date ? \Carbon\Carbon::parse($row->due_date)->format('Y-m-d') : '-')

            // =========================
            // Badges
            // =========================
            ->editColumn('payment_type', function ($row) {
                return ($row->payment_type ?? 'cash') === 'cash'
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

            // =========================
            // Money
            // =========================
            ->editColumn('total', fn ($row) => number_format((float) $row->total, 2))
            ->editColumn('paid_amount', fn ($row) => number_format((float) $row->paid_amount, 2))
            ->editColumn('remaining_amount', fn ($row) => number_format((float) $row->remaining_amount, 2))

            ->editColumn('created_at', fn ($row) => optional($row->created_at)?->format('Y-m-d H:i'))

            // =========================
            // Actions
            // =========================
            ->addColumn('actions', function ($row) {

                $show = route('sales_invoices.show', $row->id);
                $edit = route('sales_invoices.edit', $row->id);
                $destroy = route('sales_invoices.destroy', $row->id);

                $post = route('sales_invoices.post', $row->id);
                $cancel = route('sales_invoices.cancel', $row->id);

                // ✅ إنشاء مرتجع من الفاتورة
                $createReturn = route('sales_returns.create_from_invoice', $row->id);

                $canEdit = ! in_array($row->status, ['paid', 'cancelled'], true);
                $canPost = in_array($row->status, ['draft'], true);
                $canCancel = ! in_array($row->status, ['cancelled'], true);

                // ✅ المرتجع يتعمل من فاتورة مُعتمدة (posted/paid/partial) وغير ملغاة
                $canReturnStatus = in_array($row->status, ['posted', 'paid', 'partial'], true);

                // ✅ منع مرتجع جديد لو خلاص اتعمل مرتجع كامل (sum == total)
                $total = (float) ($row->total ?? 0);
                $sumReturns = (float) ($row->returns_sum_total ?? 0);
                $returnable = max(0, $total - $sumReturns);
                $canReturnAmount = $returnable > 0.0001;

                $canReturn = $canReturnStatus && $canReturnAmount;

                $returnHint = ! $canReturnStatus
                    ? 'متاح بعد الترحيل فقط'
                    : (! $canReturnAmount ? 'تم عمل مرتجع كامل بالفعل' : 'إنشاء مرتجع من هذه الفاتورة');

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

                // ✅ مرتجع
                if ($canReturn) {
                    $html .= '
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2" href="'.$createReturn.'">
                                <i class="fa fa-rotate-left text-warning"></i>
                                <div>
                                    <div class="fw-semibold">عمل مرتجع مبيعات</div>
                                    <small class="text-muted">'.$returnHint.' (متبقي: <span dir="ltr">'.number_format($returnable, 2).'</span>)</small>
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
                                    <div class="fw-semibold text-muted">عمل مرتجع مبيعات</div>
                                    <small class="text-muted">'.$returnHint.'</small>
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
                                    <small class="text-muted">إلغاء الفاتورة (لو مترحّلة هيتم عمل قيد عكسي)</small>
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

            ->rawColumns(['payment_type', 'status', 'return_info', 'returnable_amount', 'actions']);
    }

    public function query(SalesInvoice $model): QueryBuilder
    {
        $ownerId = $this->ownerId();

        $q = $model->newQuery()
            ->where('user_id', $ownerId)
            ->select([
                'sales_invoices.id',
                'sales_invoices.customer_id',
                'sales_invoices.invoice_code',
                'sales_invoices.invoice_number',
                'sales_invoices.invoice_date',
                'sales_invoices.payment_type',
                'sales_invoices.due_date',
                'sales_invoices.total',
                'sales_invoices.paid_amount',
                'sales_invoices.remaining_amount',
                'sales_invoices.status',
                'sales_invoices.updated_by',
                'sales_invoices.created_at',
            ])
            ->with(['customer:id,name'])

            // ✅ عشان نعرف هل عليها مرتجع ونجمع قيم المرتجع
            ->withCount('returns')
            ->withSum('returns', 'total')

            ->orderByDesc('sales_invoices.id');

        // External filters
        if (request()->filled('status')) {
            $q->where('status', request('status'));
        }

        if (request()->filled('payment_type')) {
            $q->where('payment_type', request('payment_type'));
        }

        if (request()->filled('customer_id')) {
            $q->where('customer_id', (int) request('customer_id'));
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
                $qq->where('invoice_code', 'like', "%{$external}%")
                    ->orWhere('invoice_number', 'like', "%{$external}%");
            });
        }

        // Internal datatables search
        $dt = request('search');
        $internal = is_array($dt) ? trim((string) ($dt['value'] ?? '')) : '';
        if ($internal !== '') {
            $q->where(function ($qq) use ($internal) {
                $qq->where('invoice_code', 'like', "%{$internal}%")
                    ->orWhere('invoice_number', 'like', "%{$internal}%");
            });
        }

        return $q;
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('sales-invoices-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'q' => '$("#salesInvoicesFilterForm [name=q]").val()',
                'status' => '$("#salesInvoicesFilterForm [name=status]").val()',
                'payment_type' => '$("#salesInvoicesFilterForm [name=payment_type]").val()',
                'customer_id' => '$("#salesInvoicesFilterForm [name=customer_id]").val()',
                'date_from' => '$("#salesInvoicesFilterForm [name=date_from]").val()',
                'date_to' => '$("#salesInvoicesFilterForm [name=date_to]").val()',
            ])
            ->responsive(true)
            ->processing(true)
            ->serverSide(true)

            // ✅ created_at index (لاحظ: ترتيب الأعمدة اتغير بسبب أعمدة المرتجع)
            ->orderBy(13, 'desc')

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
            ['data' => 'invoice_code', 'name' => 'invoice_code', 'title' => 'كود الفاتورة'],
            ['data' => 'invoice_number', 'name' => 'invoice_number', 'title' => 'رقم الفاتورة'],
            ['data' => 'customer', 'name' => 'customer.name', 'title' => 'العميل', 'orderable' => false],
            ['data' => 'invoice_date', 'name' => 'invoice_date', 'title' => 'تاريخ الفاتورة'],
            ['data' => 'payment_type', 'name' => 'payment_type', 'title' => 'نوع الدفع'],
            ['data' => 'due_date', 'name' => 'due_date', 'title' => 'تاريخ الاستحقاق'],

            ['data' => 'total', 'name' => 'total', 'title' => 'الإجمالي'],
            ['data' => 'paid_amount', 'name' => 'paid_amount', 'title' => 'المدفوع'],
            ['data' => 'remaining_amount', 'name' => 'remaining_amount', 'title' => 'المتبقي'],

            // ✅ new
            ['data' => 'return_info', 'name' => 'returns_count', 'title' => 'مرتجع؟', 'orderable' => false, 'searchable' => false],
            ['data' => 'returnable_amount', 'name' => 'returns_sum_total', 'title' => 'المتاح للمرتجع', 'orderable' => false, 'searchable' => false],

            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة'],
            //            ['data' => 'updated_by', 'name' => 'updated_by', 'title' => 'بواسطة'],
            //            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإضافة'],

            ['data' => 'actions', 'name' => 'actions', 'title' => 'إجراءات', 'orderable' => false, 'searchable' => false],
        ];
    }

    protected function filename(): string
    {
        return 'SalesInvoices_'.date('YmdHis');
    }
}
