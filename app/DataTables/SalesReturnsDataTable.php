<?php

namespace App\DataTables;

use App\Models\SalesReturn;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;

class SalesReturnsDataTable extends DataTable
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

            ->addColumn('customer', fn ($row) => $row->customer?->name ?? '-')
            ->addColumn('invoice_code', fn ($row) => $row->invoice?->invoice_code ?? $row->invoice?->invoice_number ?? '-')
            ->addColumn('invoice_date', fn ($row) => $row->invoice?->invoice_date ? \Carbon\Carbon::parse($row->invoice->invoice_date)->format('Y-m-d') : '-')

            ->editColumn('return_date', fn ($row) => $row->return_date ? \Carbon\Carbon::parse($row->return_date)->format('Y-m-d') : '-')

            ->editColumn('total', fn ($row) => number_format((float) $row->total, 2))
            ->editColumn('vat_amount', fn ($row) => number_format((float) $row->vat_amount, 2))
            ->editColumn('subtotal', fn ($row) => number_format((float) $row->subtotal, 2))

            ->addColumn('status', function ($row) {
                // بما إن جدولك مفيهوش status: نعتبره "ملغي" لو total=0
                $isCancelled = ((float) $row->total <= 0.0001);

                return $isCancelled
                    ? '<span class="badge bg-danger">ملغي</span>'
                    : '<span class="badge bg-success">مُرحّل</span>';
            })

            ->addColumn('je', function ($row) {
                if (! empty($row->journal_entry_id)) {
                    return '<span class="badge bg-primary">JE #'.e($row->journal_entry_id).'</span>';
                }

                return '<span class="badge bg-secondary">—</span>';
            })

            ->editColumn('created_at', fn ($row) => optional($row->created_at)?->format('Y-m-d H:i'))

            ->addColumn('actions', function ($row) {

                $show = route('sales_returns.show', $row->id);
                $pdf = route('sales_returns.pdf', $row->id);
                $cancel = route('sales_returns.cancel', $row->id);

                $invId = $row->sales_invoice_id ?? null;
                $openInvoice = ($invId && \Route::has('sales_invoices.show'))
                    ? route('sales_invoices.show', $invId)
                    : null;

                $isCancelled = ((float) $row->total <= 0.0001);

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
                ';

                // PDF
                $html .= '
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="'.$pdf.'">
                            <i class="fa fa-file-pdf text-danger"></i>
                            <div>
                                <div class="fw-semibold">طباعة PDF</div>
                                <small class="text-muted">تصدير مستند المرتجع</small>
                            </div>
                        </a>
                    </li>
                ';

                // فتح الفاتورة الأصلية
                if ($openInvoice) {
                    $html .= '
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2" href="'.$openInvoice.'">
                                <i class="fa fa-receipt text-secondary"></i>
                                <div>
                                    <div class="fw-semibold">فتح الفاتورة</div>
                                    <small class="text-muted">عرض الفاتورة الأصلية</small>
                                </div>
                            </a>
                        </li>
                    ';
                }

                $html .= '<li><hr class="dropdown-divider"></li>';

                // إلغاء (reversal)
                if (! $isCancelled) {
                    $html .= '
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2 js-cancel-return"
                                    type="button" data-url="'.$cancel.'">
                                <i class="fa fa-ban text-warning"></i>
                                <div>
                                    <div class="fw-semibold">إلغاء المرتجع</div>
                                    <small class="text-muted">عمل قيد عكسي ثم تصفير المستند</small>
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
                                    <div class="fw-semibold text-muted">إلغاء المرتجع</div>
                                    <small class="text-muted">تم الإلغاء بالفعل</small>
                                </div>
                            </button>
                        </li>
                    ';
                }

                $html .= '</ul></div>';

                return $html;
            })

            ->rawColumns(['status', 'je', 'actions']);
    }

    public function query(SalesReturn $model): QueryBuilder
    {
        $ownerId = $this->ownerId();

        $q = $model->newQuery()
            ->where('sales_returns.user_id', $ownerId)
            ->select([
                'sales_returns.id',
                'sales_returns.user_id',
                'sales_returns.customer_id',
                'sales_returns.sales_invoice_id',
                'sales_returns.return_date',
                'sales_returns.subtotal',
                'sales_returns.vat_amount',
                'sales_returns.total',
                'sales_returns.journal_entry_id',
                'sales_returns.created_at',
            ])
            ->with([
                'customer:id,name',
                'invoice:id,invoice_code,invoice_number,invoice_date',
            ])
            ->orderByDesc('sales_returns.id');

        // Filters
        if (request()->filled('customer_id')) {
            $q->where('sales_returns.customer_id', (int) request('customer_id'));
        }

        if (request()->filled('date_from')) {
            $q->whereDate('sales_returns.return_date', '>=', request('date_from'));
        }

        if (request()->filled('date_to')) {
            $q->whereDate('sales_returns.return_date', '<=', request('date_to'));
        }

        if (request()->filled('has_je')) {
            if (request('has_je') === '1') {
                $q->whereNotNull('sales_returns.journal_entry_id');
            }
            if (request('has_je') === '0') {
                $q->whereNull('sales_returns.journal_entry_id');
            }
        }

        // External search q
        $external = trim((string) request('q'));
        if ($external !== '') {
            $q->where(function ($qq) use ($external) {
                $qq->where('sales_returns.id', (int) $external)
                    ->orWhereHas('invoice', function ($iq) use ($external) {
                        $iq->where('invoice_code', 'like', "%{$external}%")
                            ->orWhere('invoice_number', 'like', "%{$external}%");
                    })
                    ->orWhereHas('customer', function ($cq) use ($external) {
                        $cq->where('name', 'like', "%{$external}%");
                    });
            });
        }

        // Internal DT search
        $dt = request('search');
        $internal = is_array($dt) ? trim((string) ($dt['value'] ?? '')) : '';
        if ($internal !== '') {
            $q->where(function ($qq) use ($internal) {
                $qq->orWhereHas('invoice', function ($iq) use ($internal) {
                    $iq->where('invoice_code', 'like', "%{$internal}%")
                        ->orWhere('invoice_number', 'like', "%{$internal}%");
                })
                    ->orWhereHas('customer', function ($cq) use ($internal) {
                        $cq->where('name', 'like', "%{$internal}%");
                    });
            });
        }

        return $q;
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('sales-returns-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'q' => '$("#salesReturnsFilterForm [name=q]").val()',
                'customer_id' => '$("#salesReturnsFilterForm [name=customer_id]").val()',
                'date_from' => '$("#salesReturnsFilterForm [name=date_from]").val()',
                'date_to' => '$("#salesReturnsFilterForm [name=date_to]").val()',
                'has_je' => '$("#salesReturnsFilterForm [name=has_je]").val()',
            ])
            ->responsive(true)
            ->processing(true)
            ->serverSide(true)
            ->orderBy(8, 'desc') // created_at index (حسب ترتيب الأعمدة)
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

            ['data' => 'id', 'name' => 'sales_returns.id', 'title' => 'رقم المرتجع'],
            ['data' => 'invoice_code', 'name' => 'invoice.invoice_code', 'title' => 'الفاتورة'],
            ['data' => 'customer', 'name' => 'customer.name', 'title' => 'العميل', 'orderable' => false],
            ['data' => 'return_date', 'name' => 'sales_returns.return_date', 'title' => 'تاريخ المرتجع'],
            ['data' => 'subtotal', 'name' => 'sales_returns.subtotal', 'title' => 'Subtotal'],
            ['data' => 'vat_amount', 'name' => 'sales_returns.vat_amount', 'title' => 'VAT'],
            ['data' => 'total', 'name' => 'sales_returns.total', 'title' => 'الإجمالي'],
            ['data' => 'je', 'name' => 'sales_returns.journal_entry_id', 'title' => 'قيد اليومية', 'orderable' => false, 'searchable' => false],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'orderable' => false, 'searchable' => false],
            ['data' => 'created_at', 'name' => 'sales_returns.created_at', 'title' => 'تاريخ الإضافة'],

            ['data' => 'actions', 'name' => 'actions', 'title' => 'إجراءات', 'orderable' => false, 'searchable' => false],
        ];
    }

    protected function filename(): string
    {
        return 'SalesReturns_'.date('YmdHis');
    }
}
