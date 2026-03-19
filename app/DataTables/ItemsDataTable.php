<?php

namespace App\DataTables;

use App\Models\Items;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;

class ItemsDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()

            ->addColumn('image', function ($row) {
                $src = $row->image ? asset('storage/'.$row->image) : asset('images/no-image.png');
                $alt = e($row->name ?? 'Item');

                return '
                    <div class="d-flex justify-content-center">
                        <img src="'.$src.'"
                             alt="'.$alt.'"
                             style="width:42px;height:42px;object-fit:cover;border-radius:10px;border:1px solid rgba(0,0,0,.12)"
                             loading="lazy">
                    </div>
                ';
            })

            ->editColumn('status', function ($row) {
                $checked = $row->status ? 'checked' : '';
                $url = route('items.toggle-status', $row->id);

                return '
                    <label class="switch">
                        <input type="checkbox" class="js-toggle-status" data-url="'.$url.'" '.$checked.'>
                        <span class="slider round"></span>
                    </label>
                ';
            })

            ->addColumn('type_badge', function ($row) {
                return match ($row->type) {
                    'store' => '<span class="badge bg-primary">مخزني</span>',
                    'consumption' => '<span class="badge bg-warning">استهلاكي</span>',
                    'custody' => '<span class="badge bg-info">عهدة</span>',
                    default => '<span class="badge bg-secondary">'.e($row->type).'</span>',
                };
            })

            ->addColumn('category', fn ($row) => e(optional($row->category)->name ?? '-'))
            ->addColumn('parent', fn ($row) => e(optional($row->parent)->name ?? '-'))

            // Prices (Parent Unit)
            ->editColumn('price', fn ($row) => '<span class="money">'.number_format((float) $row->price, 2).'</span>')
            ->editColumn('nos_egomania_price', fn ($row) => '<span class="money">'.number_format((float) $row->nos_egomania_price, 2).'</span>')
            ->editColumn('egomania_price', fn ($row) => '<span class="money">'.number_format((float) $row->egomania_price, 2).'</span>')

            // Prices (Retail Unit)
            ->editColumn('price_retail', fn ($row) => '<span class="money">'.number_format((float) $row->price_retail, 2).'</span>')
            ->editColumn('nos_gomla_price_retail', fn ($row) => '<span class="money">'.number_format((float) $row->nos_gomla_price_retail, 2).'</span>')
            ->editColumn('gomla_price_retail', fn ($row) => '<span class="money">'.number_format((float) $row->gomla_price_retail, 2).'</span>')

            ->editColumn('retail_uom_quintToParent', function ($row) {
                return $row->retail_uom_quintToParent !== null
                    ? number_format((float) $row->retail_uom_quintToParent, 2)
                    : '-';
            })

            ->addColumn('action', function ($row) {
                return view('admin.items.datatables.actions', compact('row'))->render();
            })

            ->rawColumns([
                'image',
                'status',
                'type_badge',
                'action',

                'price',
                'nos_egomania_price',
                'egomania_price',
                'price_retail',
                'nos_gomla_price_retail',
                'gomla_price_retail',
            ]);
    }

    public function query(Items $model): QueryBuilder
    {
        // ✅ Tenant/Owner scope
        $ownerId = auth()->user()->owner_user_id ?? auth()->id();

        $q = $model->newQuery()
            ->with(['category:id,name', 'parent:id,name'])
            ->where('user_id', $ownerId)
            ->select('items.*')
            ->orderByDesc('items.id');

        if ($type = request('type')) {
            $q->where('type', $type);
        }

        if (request()->filled('status')) {
            $q->where('status', (int) request('status'));
        }

        $search = trim((string) request('q'));
        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('name', 'like', "%{$search}%")
                    ->orWhere('items_code', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        return $q;
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('items-table')
            ->columns($this->getColumns())
            ->ajax([
                'url' => route('items.index'),
                'type' => 'GET',
                'data' => 'function(d){
                    d.q      = $("#filter_q").val();
                    d.type   = $("#filter_type").val();
                    d.status = $("#filter_status").val();
                }',
            ])
            ->responsive(true)
            ->processing(true)
            ->serverSide(true)
            ->orderBy(2, 'desc')
            ->parameters([
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
            ['data' => 'image', 'name' => 'image', 'title' => 'الصورة', 'orderable' => false, 'searchable' => false],
            ['data' => 'items_code', 'name' => 'items_code', 'title' => 'الكود'],
            ['data' => 'barcode', 'name' => 'barcode', 'title' => 'الباركود'],
            ['data' => 'name', 'name' => 'name', 'title' => 'الاسم'],
            //            ['data' => 'price', 'name' => 'price', 'title' => 'قطاعي (الأب)'],
            //            ['data' => 'nos_egomania_price', 'name' => 'nos_egomania_price', 'title' => 'نص جملة (الأب)'],
            //            ['data' => 'egomania_price', 'name' => 'egomania_price', 'title' => 'جملة (الأب)'],
            ['data' => 'price_retail', 'name' => 'price_retail', 'title' => 'قطاعي (تجزئة)'],
            //            ['data' => 'nos_gomla_price_retail', 'name' => 'nos_gomla_price_retail', 'title' => 'نص جملة (تجزئة)'],
            //            ['data' => 'gomla_price_retail', 'name' => 'gomla_price_retail', 'title' => 'جملة (تجزئة)'],
            ['data' => 'type_badge', 'name' => 'type', 'title' => 'النوع', 'orderable' => false, 'searchable' => false],
            ['data' => 'category', 'name' => 'category', 'title' => 'التصنيف', 'orderable' => false, 'searchable' => false],
            //            ['data' => 'parent', 'name' => 'parent', 'title' => 'الأب', 'orderable' => false, 'searchable' => false],
            //            ['data' => 'retail_uom_quintToParent', 'name' => 'retail_uom_quintToParent', 'title' => 'معامل التحويل'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'orderable' => false, 'searchable' => false],
            ['data' => 'action', 'name' => 'action', 'title' => 'إجراءات', 'orderable' => false, 'searchable' => false],
        ];
    }

    protected function filename(): string
    {
        return 'Items_'.date('YmdHis');
    }
}
