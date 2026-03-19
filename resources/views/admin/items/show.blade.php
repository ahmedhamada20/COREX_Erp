@extends('admin.layouts.master')

@section('title', 'عرض صنف')

@section('css')
    <style>
        .section-title { font-size: .95rem; font-weight: 600; }
        .img-preview {
            width: 190px; height: 190px; border-radius: 16px; overflow: hidden;
            border: 1px solid rgba(0,0,0,.12); background: rgba(0,0,0,.02);
        }
        .img-preview img { width: 100%; height: 100%; object-fit: cover; }
        .btn-icon { display:inline-flex; align-items:center; gap:.35rem; }
        .kv { display:flex; justify-content:space-between; gap:1rem; padding:.45rem 0; }
        .kv + .kv { border-top: 1px dashed rgba(0,0,0,.08); }
        .kv .k { color:#6c757d; font-size:.85rem; }
        .kv .v { font-weight:600; }
        .money { direction:ltr; text-align:left; font-variant-numeric: tabular-nums; }
        .badge-soft { background: rgba(13,110,253,.08); color:#0d6efd; border: 1px solid rgba(13,110,253,.15); }
    </style>
@endsection

@section('content')

    @php
        $badge = $item->status ? 'success' : 'secondary';
        $statusText = $item->status ? 'نشط' : 'غير نشط';

        $typeMap = ['store' => 'مخزني', 'consumption' => 'استهلاكي', 'custody' => 'عهدة'];
        $typeText = $typeMap[$item->type] ?? $item->type;

        $imgSrc = $item->image ? asset('storage/'.$item->image) : asset('images/no-image.png');
        $hasBarcode = !empty($item->barcode);

        $fmt = fn ($v) => $v !== null ? number_format((float)$v, 2) : '-';

        // اختياري: منع الحذف لو له children (لو عندك relation)
        $childrenCount = method_exists($item, 'children') ? ($item->children()->count() ?? 0) : 0;
    @endphp

    <div class="content-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">عرض صنف</h5>
                <small class="text-muted">{{ $item->name }} — {{ $item->items_code }}</small>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('items.index') }}" class="btn btn-sm btn-light btn-icon">
                    <i class="ti ti-arrow-right"></i> رجوع
                </a>
                <a href="{{ route('items.edit', $item->id) }}" class="btn btn-sm btn-primary btn-icon">
                    <i class="ti ti-edit"></i> تعديل
                </a>
            </div>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="row">

        {{-- Summary Card --}}
        <div class="col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">ملخص</h6>
                    <span class="badge bg-{{ $badge }}">{{ $statusText }}</span>
                </div>

                <div class="card-body">

                    {{-- Image --}}
                    <div class="mb-3 text-center">
                        <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#itemImageModal" class="d-inline-block">
                            <div class="img-preview mx-auto">
                                <img src="{{ $imgSrc }}" alt="{{ $item->name ?? 'Item' }}" loading="lazy">
                            </div>
                        </a>
                        <div class="text-muted small mt-2">اضغط على الصورة للتكبير</div>
                    </div>

                    <div class="mb-3">
                        <div class="text-muted small mb-1">اسم الصنف</div>
                        <div class="fw-semibold">{{ $item->name ?? '-' }}</div>
                    </div>

                    <div class="mb-3">
                        <div class="text-muted small mb-1">كود الصنف</div>
                        <div class="fw-semibold">{{ $item->items_code ?? '-' }}</div>
                    </div>

                    <div class="mb-3">
                        <div class="text-muted small mb-1">الباركود</div>
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <span class="fw-semibold" id="barcodeText">{{ $item->barcode ?? '-' }}</span>

                            @if($hasBarcode)
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCopyBarcode">
                                    نسخ
                                </button>
                            @endif
                        </div>
                    </div>

                    <hr>

                    <div class="mb-2">
                        <div class="text-muted small mb-1">نوع الصنف</div>
                        <div class="fw-semibold">{{ $typeText }}</div>
                    </div>

                    <div class="mb-2">
                        <div class="text-muted small mb-1">التاريخ</div>
                        <div class="fw-semibold">
                            {{ $item->date ? \Carbon\Carbon::parse($item->date)->format('Y-m-d') : '-' }}
                        </div>
                    </div>

                    @if($childrenCount > 0)
                        <div class="mt-3">
                            <span class="badge badge-soft">له {{ $childrenCount }} صنف/وحدة مرتبطة</span>
                        </div>
                    @endif

                </div>
            </div>
        </div>

        {{-- Details + Prices --}}
        <div class="col-lg-8 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">تفاصيل الصنف</h6>
                    <span class="text-muted small">#{{ $item->id }}</span>
                </div>

                <div class="card-body">
                    <div class="row">

                        {{-- Category --}}
                        <div class="col-md-6 mb-3">
                            <div class="text-muted small mb-1">التصنيف</div>
                            <div class="fw-semibold">{{ optional($item->category)->name ?? '-' }}</div>
                        </div>

                        {{-- Parent --}}
                        <div class="col-md-6 mb-3">
                            <div class="text-muted small mb-1">الصنف الأب</div>
                            <div class="fw-semibold">
                                @if($item->parent)
                                    <a href="{{ route('items.show', $item->parent->id) }}" class="text-decoration-none">
                                        {{ $item->parent->name }} ({{ $item->parent->items_code }})
                                    </a>
                                @else
                                    -
                                @endif
                            </div>
                        </div>

                        {{-- Unit --}}
                        <div class="col-md-6 mb-3">
                            <div class="text-muted small mb-1">وحدة القياس الأساسية</div>
                            <div class="fw-semibold">{{ $item->unit_id ?? '-' }}</div>
                        </div>

                        {{-- Retail flag --}}
                        <div class="col-md-6 mb-3">
                            <div class="text-muted small mb-1">وحدة تجزئة</div>
                            <div class="fw-semibold">{{ $item->does_has_retail_unit ? 'نعم' : 'لا' }}</div>
                        </div>

                        @if($item->does_has_retail_unit)
                            <div class="col-md-6 mb-3">
                                <div class="text-muted small mb-1">وحدة التجزئة</div>
                                <div class="fw-semibold">{{ $item->retail_unit ?? '-' }}</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="text-muted small mb-1">معامل التحويل</div>
                                <div class="fw-semibold">
                                    {{ $item->retail_uom_quintToParent ?? '-' }}
                                    <span class="text-muted small">
                                        ({{ $item->retail_uom_quintToParent ?? '-' }} قطعة = 1 {{ $item->unit_id ?? 'وحدة أساسية' }})
                                    </span>
                                </div>
                            </div>
                        @endif

                        <div class="col-12"><hr></div>

                        {{-- Prices Card --}}
                        <div class="col-12 mb-3">
                            <div class="section-title mb-2">الأسعار</div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card border h-100">
                                        <div class="card-header bg-transparent">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="fw-semibold">أسعار الوحدة الأساسية</div>
                                                <span class="badge bg-light text-muted">{{ $item->unit_id ?? 'Unit' }}</span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="kv">
                                                <div class="k">قطاعي</div>
                                                <div class="v money">{{ $fmt($item->price) }}</div>
                                            </div>
                                            <div class="kv">
                                                <div class="k">نص جملة</div>
                                                <div class="v money">{{ $fmt($item->nos_egomania_price) }}</div>
                                            </div>
                                            <div class="kv">
                                                <div class="k">جملة</div>
                                                <div class="v money">{{ $fmt($item->egomania_price) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card border h-100">
                                        <div class="card-header bg-transparent">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="fw-semibold">أسعار التجزئة</div>
                                                <span class="badge bg-light text-muted">{{ $item->retail_unit ?? 'Retail' }}</span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="kv">
                                                <div class="k">قطاعي</div>
                                                <div class="v money">{{ $fmt($item->price_retail) }}</div>
                                            </div>
                                            <div class="kv">
                                                <div class="k">نص جملة</div>
                                                <div class="v money">{{ $fmt($item->nos_gomla_price_retail) }}</div>
                                            </div>
                                            <div class="kv">
                                                <div class="k">جملة</div>
                                                <div class="v money">{{ $fmt($item->gomla_price_retail) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if(!$item->does_has_retail_unit)
                                <div class="text-muted small mt-2">
                                    ملاحظة: الصنف بدون وحدة تجزئة، لكن يمكن أن تكون أسعار التجزئة محفوظة كقيم افتراضية.
                                </div>
                            @endif
                        </div>

                        {{-- Meta --}}
                        <div class="col-md-6 mb-3">
                            <div class="text-muted small mb-1">تاريخ الإضافة</div>
                            <div class="fw-semibold">{{ $item->created_at ? $item->created_at->format('Y-m-d H:i') : '-' }}</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="text-muted small mb-1">آخر تحديث</div>
                            <div class="fw-semibold">{{ $item->updated_at ? $item->updated_at->format('Y-m-d H:i') : '-' }}</div>
                        </div>

                    </div>
                </div>

                {{-- Actions Footer --}}
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div class="text-muted small">#{{ $item->id }}</div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('items.edit', $item->id) }}" class="btn btn-sm btn-primary btn-icon">
                            <i class="ti ti-edit"></i> تعديل
                        </a>

                        <form action="{{ route('items.destroy', $item->id) }}" method="POST"
                              onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                    class="btn btn-sm btn-outline-danger btn-icon"
                                    @if($childrenCount > 0) disabled title="لا يمكن حذف صنف له وحدات/أصناف مرتبطة" @endif>
                                <i class="ti ti-trash"></i> حذف
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>

    </div>

    {{-- Image Modal --}}
    <div class="modal fade" id="itemImageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">صورة الصنف</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center">
                    <img src="{{ $imgSrc }}" alt="{{ $item->name ?? 'Item' }}"
                         style="max-width: 100%; max-height: 70vh; object-fit: contain; border-radius: 14px;">
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    <script>
        (function () {
            const btn = document.getElementById('btnCopyBarcode');
            if (!btn) return;

            btn.addEventListener('click', async function () {
                const text = document.getElementById('barcodeText')?.innerText?.trim() || '';
                if (!text) return;

                try {
                    await navigator.clipboard.writeText(text);
                    btn.innerText = 'تم النسخ';
                    setTimeout(() => btn.innerText = 'نسخ', 1200);
                } catch (e) {
                    alert('تعذر نسخ الباركود');
                }
            });
        })();
    </script>
@endsection
