{{-- resources/views/admin/suppliers/show.blade.php --}}
@extends('admin.layouts.master')

@section('title', 'عرض مورد')

@section('css')
    <style>
        .stat {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 14px;
        }
        .stat .label { font-size: 12px; color: #64748b; }
        .stat .value { font-size: 18px; font-weight: 700; color: #0f172a; }
        .img-preview {
            width: 96px; height: 96px; border-radius: 16px; object-fit: cover;
            border: 1px solid #e5e7eb; background: #f8fafc;
        }
        .chip {
            display: inline-flex; align-items: center; gap: .4rem;
            padding: .25rem .6rem; border-radius: 999px; font-size: 12px;
            background: #f1f5f9; color: #0f172a;
        }
    </style>
@endsection

@section('content')

    <div class="content-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">بطاقة مورد</h5>
                <small class="text-muted">
                    {{ $supplier->name }}
                    @if($supplier->account_number)
                        — <span class="chip">{{ $supplier->account_number }}</span>
                    @endif
                </small>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('suppliers.index') }}" class="btn btn-sm btn-light">رجوع</a>
                <a href="{{ route('suppliers.edit', $supplier->id) }}" class="btn btn-sm btn-primary">تعديل</a>

                <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST"
                      onsubmit="return confirm('متأكد من حذف المورد؟')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-danger">حذف</button>
                </form>
            </div>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="row">

        {{-- Left: Profile --}}
        <div class="col-lg-4 mb-3">
            <div class="card">
                <div class="card-body">

                    <div class="d-flex align-items-center gap-3">
                        <img class="img-preview"
                             src="{{ $supplier->image ? asset('storage/'.$supplier->image) : 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'96\' height=\'96\'%3E%3Crect width=\'100%25\' height=\'100%25\' fill=\'%23f1f5f9\'/%3E%3Ctext x=\'50%25\' y=\'52%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' fill=\'%2364748b\' font-size=\'10\'%3ENo Image%3C/text%3E%3C/svg%3E' }}"
                             alt="supplier">

                        <div>
                            <div class="fw-bold" style="font-size: 18px;">{{ $supplier->name }}</div>
                            <div class="text-muted" style="font-size: 13px;">
                                {{ $supplier->city ?? '—' }}
                            </div>

                            <div class="mt-2">
                                @if($supplier->status)
                                    <span class="badge bg-success">نشط</span>
                                @else
                                    <span class="badge bg-danger">غير نشط</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-2">
                        <div class="text-muted" style="font-size: 12px;">الهاتف</div>
                        <div class="fw-semibold">{{ $supplier->phone ?? '—' }}</div>
                    </div>

                    <div class="mb-2">
                        <div class="text-muted" style="font-size: 12px;">كود المورد</div>
                        <div class="fw-semibold">{{ $supplier->code ?? '—' }}</div>
                    </div>

                    <div class="mb-2">
                        <div class="text-muted" style="font-size: 12px;">تصنيف المورد</div>
                        <div class="fw-semibold">
                            {{ $supplier->supplierCategory->name ?? '—' }}
                        </div>
                    </div>

                    <div class="mb-2">
                        <div class="text-muted" style="font-size: 12px;">البريد</div>
                        <div class="fw-semibold">{{ $supplier->email ?? '—' }}</div>
                    </div>

                    <div class="mb-2">
                        <div class="text-muted" style="font-size: 12px;">تاريخ فتح الحساب</div>
                        <div class="fw-semibold">{{ $supplier->date ? $supplier->date->format('Y-m-d') : '—' }}</div>
                    </div>

                    <div class="mb-2">
                        <div class="text-muted" style="font-size: 12px;">آخر تحديث بواسطة</div>
                        <div class="fw-semibold">{{ $supplier->updated_by ?? '—' }}</div>
                    </div>

                    <div class="mb-0">
                        <div class="text-muted" style="font-size: 12px;">ملاحظات</div>
                        <div class="fw-semibold">{!! nl2br(e($supplier->notes ?? '—')) !!}</div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Right: Financial Summary + Activity --}}
        <div class="col-lg-8 mb-3">

            {{-- Summary --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">ملخص مالي</h6>
                    <span class="badge bg-warning-subtle text-warning">Accounting</span>
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-4">
                            <div class="stat">
                                <div class="label">الرصيد الافتتاحي</div>
                                <div class="value">{{ number_format((float)$supplier->start_balance, 2) }}</div>
                                <div class="form-hint mt-1">
                                    @if(($supplier->start_balance ?? 0) > 0)
                                        المورد <b>دائن</b> (عليك له)
                                    @elseif(($supplier->start_balance ?? 0) < 0)
                                        المورد <b>مدين</b> (لك عليه / سلفة)
                                    @else
                                        متزن
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="stat">
                                <div class="label">الرصيد الحالي</div>
                                <div class="value">{{ number_format((float)$supplier->current_balance, 2) }}</div>
                                <div class="form-hint mt-1">
                                    @if(($supplier->current_balance ?? 0) > 0)
                                        المورد <b>دائن</b> (عليك له)
                                    @elseif(($supplier->current_balance ?? 0) < 0)
                                        المورد <b>مدين</b> (لك عليه)
                                    @else
                                        متزن
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="stat">
                                <div class="label">التغير منذ الافتتاح</div>
                                @php
                                    $delta = (float)($supplier->current_balance ?? 0) - (float)($supplier->start_balance ?? 0);
                                @endphp
                                <div class="value">{{ number_format($delta, 2) }}</div>
                                <div class="form-hint mt-1">فرق الرصيد الحالي عن الافتتاحي.</div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">آخر الحركات</h6>
                    <span class="badge bg-light text-dark">Latest Activity</span>
                </div>

                <div class="card-body">
                    <div class="alert alert-info mb-0">
                        <div class="fw-semibold mb-1">جاهز للربط</div>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>المصدر</th>
                                    <th>المرجع</th>
                                    <th>الوصف</th>
                                    <th class="text-end">مدين</th>
                                    <th class="text-end">دائن</th>
                                    <th class="text-end">الرصيد بعد الحركة</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($movements as $m)
                                    <tr>
                                        <td>{{ $m->date }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark">{{ $m->type }}</span>
                                        </td>
                                        <td class="fw-semibold">{{ $m->ref }}</td>
                                        <td style="max-width: 360px;">
                                            <div class="text-truncate" title="{{ $m->description }}">{{ $m->description }}</div>
                                        </td>

                                        <td class="text-end">{{ number_format((float)$m->debit, 2) }}</td>
                                        <td class="text-end">{{ number_format((float)$m->credit, 2) }}</td>

                                        <td class="text-end fw-bold">
                                            {{ number_format((float)$m->balance_after, 2) }}
                                            <div class="text-muted" style="font-size:12px;">
                                                @if($m->balance_after > 0)
                                                    دائن (عليك له)
                                                @elseif($m->balance_after < 0)
                                                    مدين (لك عليه)
                                                @else
                                                    متزن
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">لا توجد حركات لهذا المورد</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                    </div>

                    {{--
                    <div class="table-responsive mt-3">
                        <table class="table table-striped align-middle">
                            <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>النوع</th>
                                <th>المرجع</th>
                                <th class="text-end">مدين</th>
                                <th class="text-end">دائن</th>
                                <th class="text-end">الرصيد بعد الحركة</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($movements as $m)
                                <tr>
                                    <td>{{ $m->date }}</td>
                                    <td>{{ $m->type }}</td>
                                    <td>{{ $m->ref }}</td>
                                    <td class="text-end">{{ number_format($m->debit,2) }}</td>
                                    <td class="text-end">{{ number_format($m->credit,2) }}</td>
                                    <td class="text-end">{{ number_format($m->balance_after,2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">لا توجد حركات</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    --}}
                </div>
            </div>

        </div>
    </div>

@endsection
