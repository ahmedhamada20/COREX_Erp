{{-- resources/views/admin/customers/show.blade.php --}}
@extends('admin.layouts.master')

@section('title', 'عرض عميل')

@section('css')
    <style>
        :root{
            --muted:#64748b;
            --card:#fff;
            --border:#e5e7eb;
            --soft:#f8fafc;
            --shadow: 0 2px 12px rgba(0,0,0,.04);
        }

        .page-title{ font-weight:900; letter-spacing:.2px; }
        .muted{ color:var(--muted); font-size:12px; }
        .num{ direction:ltr; text-align:left; font-variant-numeric: tabular-nums; }

        .card-soft{
            background:var(--card);
            border:1px solid var(--border);
            box-shadow: var(--shadow);
            border-radius:14px;
        }

        .stat{
            background:var(--soft);
            border:1px solid var(--border);
            border-radius:16px;
            padding:14px;
            height:100%;
        }
        .stat .label{ font-size:12px; color:var(--muted); }
        .stat .value{ font-size:18px; font-weight:900; color:#0f172a; letter-spacing:.2px; }
        .stat .sub{ font-size:12px; color:var(--muted); margin-top:6px; }

        .img-preview{
            width:96px; height:96px; border-radius:16px; object-fit:cover;
            border:1px solid var(--border); background:var(--soft);
        }

        .chip{
            display:inline-flex; align-items:center; gap:.4rem;
            padding:.25rem .6rem; border-radius:999px; font-size:12px;
            background:#f1f5f9; color:#0f172a;
            border:1px solid var(--border);
            white-space:nowrap;
        }
        .chip a{ color:inherit; text-decoration:none; }

        .kv{
            display:flex; justify-content:space-between; gap:10px;
            padding:8px 0; border-bottom:1px dashed #e5e7eb;
        }
        .kv:last-child{ border-bottom:0; }
        .kv .k{ color:var(--muted); font-size:12px; }
        .kv .v{ color:#0f172a; font-weight:800; font-size:13px; text-align:left; }

        .table td, .table th{ vertical-align: middle; }

        .amount{ font-weight:900; letter-spacing:.2px; }
        .amount.debit{ color:#0f766e; }
        .amount.credit{ color:#b91c1c; }

        .badge-soft{ background:#f1f5f9; border:1px solid var(--border); color:#0f172a; }
        .badge-soft-success{ background: rgba(34,197,94,.12); color:#16a34a; border:1px solid rgba(34,197,94,.25); }
        .badge-soft-danger { background: rgba(239,68,68,.12); color:#dc2626; border:1px solid rgba(239,68,68,.25); }
        .badge-soft-info   { background: rgba(59,130,246,.12); color:#2563eb; border:1px solid rgba(59,130,246,.25); }
        .badge-soft-warning{ background: rgba(245,158,11,.14); color:#b45309; border:1px solid rgba(245,158,11,.30); }

        .card-title-row{ display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .line{ height:1px; background:#e2e8f0; margin:12px 0; }

        .copy-btn{ cursor:pointer; text-decoration: underline; text-underline-offset:2px; }

        .no-print{}
        @media print{
            .no-print{ display:none !important; }
            .content-header, .breadcrumb, .navbar, .sidebar, .footer{ display:none !important; }
            .card-soft{ border:0 !important; box-shadow:none !important; }
        }
    </style>
@endsection

@section('content')

    @php
        $fmt = fn($n, $d=2) => number_format((float)($n ?? 0), $d);

        $netLabel = function ($net) {
            $net = (float)$net;
            if ($net > 0) return ['مدين', 'success'];
            if ($net < 0) return ['دائن', 'danger'];
            return ['متزن', 'info'];
        };

        $custStart = (float)($customer->start_balance ?? 0);
        $custCur   = (float)($customer->current_balance ?? 0);
        $delta     = $custCur - $custStart;

        $kpis = $kpis ?? [
            'total_debit' => (float)($lines?->sum('debit') ?? 0),
            'total_credit' => (float)($lines?->sum('credit') ?? 0),
            'net_move' => (float)(($lines?->sum('debit') ?? 0) - ($lines?->sum('credit') ?? 0)),
        ];

        [$custStartTxt, $custStartCls] = $netLabel($custStart);
        [$custCurTxt, $custCurCls]     = $netLabel($custCur);
        [$netMoveTxt, $netMoveCls]     = $netLabel($kpis['net_move'] ?? 0);

        $running = 0.0;
    @endphp

    <div class="content-header mb-3 no-print">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 page-title">بطاقة عميل</h5>
                <small class="text-muted">
                    {{ $customer->name }}
                    @if($customer->account_number)
                        — <span class="chip">
                        رقم الحساب:
                        <span class="fw-bold">{{ $customer->account_number }}</span>
                        <span class="text-muted ms-1 copy-btn" data-copy="{{ $customer->account_number }}">نسخ</span>
                    </span>
                    @endif
                </small>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('customers.index') }}" class="btn btn-sm btn-light">رجوع</a>
                @if(Route::has('customers.edit'))
                    <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-sm btn-primary">تعديل</a>
                @endif

                @if(Route::has('customers.destroy'))
                    <form action="{{ route('customers.destroy', $customer->id) }}" method="POST"
                          onsubmit="return confirm('متأكد من حذف العميل؟')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger">حذف</button>
                    </form>
                @endif

                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">طباعة</button>
            </div>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="row">

        {{-- Left --}}
        <div class="col-lg-4 mb-3">

            {{-- Profile --}}
            <div class="card card-soft">
                <div class="card-body">

                    <div class="d-flex align-items-center gap-3">
                        <img class="img-preview"
                             src="{{ $customer->image ? asset('storage/'.$customer->image) : 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'96\' height=\'96\'%3E%3Crect width=\'100%25\' height=\'100%25\' fill=\'%23f1f5f9\'/%3E%3Ctext x=\'50%25\' y=\'52%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' fill=\'%2364748b\' font-size=\'10\'%3ENo Image%3C/text%3E%3C/svg%3E' }}"
                             alt="customer">

                        <div>
                            <div class="fw-bold" style="font-size: 18px;">{{ $customer->name }}</div>
                            <div class="text-muted" style="font-size: 13px;">{{ $customer->city ?? '—' }}</div>

                            <div class="mt-2">
                                @if($customer->status)
                                    <span class="badge badge-soft-success">نشط</span>
                                @else
                                    <span class="badge badge-soft-danger">غير نشط</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="line"></div>

                    <div class="kv">
                        <div class="k">الهاتف</div>
                        <div class="v">{{ $customer->phone ?? '—' }}</div>
                    </div>
                    <div class="kv">
                        <div class="k">كود العميل</div>
                        <div class="v">{{ $customer->code ?? '—' }}</div>
                    </div>
                    <div class="kv">
                        <div class="k">البريد</div>
                        <div class="v">{{ $customer->email ?? '—' }}</div>
                    </div>
                    <div class="kv">
                        <div class="k">تاريخ فتح الحساب</div>
                        <div class="v">{{ $customer->date ? \Carbon\Carbon::parse($customer->date)->format('Y-m-d') : '—' }}</div>
                    </div>
                    <div class="kv">
                        <div class="k">آخر تحديث بواسطة</div>
                        <div class="v">{{ $customer->updated_by ?? '—' }}</div>
                    </div>

                    <div class="line"></div>

                    <div class="mb-0">
                        <div class="text-muted" style="font-size: 12px;">ملاحظات</div>
                        <div class="fw-semibold">{!! nl2br(e($customer->notes ?? '—')) !!}</div>
                    </div>

                </div>
            </div>

            {{-- Linked Account --}}
            <div class="card card-soft mt-3">
                <div class="card-header">
                    <div class="card-title-row">
                        <h6 class="mb-0">الحساب المالي المرتبط</h6>
                        @if($account)
                            <span class="badge badge-soft-info">Linked</span>
                        @else
                            <span class="badge badge-soft-warning">Not linked</span>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    @if(!$customer->account_number)
                        <div class="alert alert-warning mb-0" style="font-size: 13px;">
                            العميل غير مرتبط برقم حساب. تأكد من خدمة إنشاء العميل/الحساب.
                        </div>
                    @elseif(!$account)
                        <div class="alert alert-danger mb-0" style="font-size: 13px;">
                            رقم الحساب موجود على العميل لكن لم يتم العثور على الحساب في شجرة الحسابات.
                        </div>
                    @else
                        <div class="kv">
                            <div class="k">اسم الحساب</div>
                            <div class="v">{{ $account->name }}</div>
                        </div>
                        <div class="kv">
                            <div class="k">رقم الحساب</div>
                            <div class="v">
                                <span class="num">{{ $account->account_number }}</span>
                                <span class="text-muted ms-2 copy-btn" data-copy="{{ $account->account_number }}">نسخ</span>
                            </div>
                        </div>
                        <div class="kv">
                            <div class="k">النوع</div>
                            <div class="v">{{ $account->type?->name ?? '—' }}</div>
                        </div>
                        <div class="kv">
                            <div class="k">المسار</div>
                            <div class="v">
                                {{ $account->parent?->name ? ($account->parent->name . ' / ' . $account->name) : $account->name }}
                            </div>
                        </div>
                        <div class="kv">
                            <div class="k">الحالة</div>
                            <div class="v">
                                @if($account->status)
                                    <span class="badge badge-soft-success">نشط</span>
                                @else
                                    <span class="badge badge-soft-danger">غير نشط</span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2 no-print">
                            @if(Route::has('accounts.show'))
                                <a href="{{ route('accounts.show', $account->id) }}" class="btn btn-sm btn-light">فتح الحساب</a>
                            @endif
                            @if(Route::has('accounts.edit'))
                                <a href="{{ route('accounts.edit', $account->id) }}" class="btn btn-sm btn-warning">تعديل الحساب</a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- Right --}}
        <div class="col-lg-8 mb-3">

            {{-- Summary --}}
            <div class="card card-soft">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">ملخص مالي (العميل)</h6>
                    <span class="badge badge-soft">Accounting</span>
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-4">
                            <div class="stat">
                                <div class="label">الرصيد الافتتاحي</div>
                                <div class="value num">{{ $fmt($custStart) }}</div>
                                <div class="sub">
                                    <span class="badge badge-soft-{{ $custStartCls }}">{{ $custStartTxt }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="stat">
                                <div class="label">الرصيد الحالي</div>
                                <div class="value num">{{ $fmt($custCur) }}</div>
                                <div class="sub">
                                    <span class="badge badge-soft-{{ $custCurCls }}">{{ $custCurTxt }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="stat">
                                <div class="label">التغير منذ الافتتاح</div>
                                <div class="value num">{{ $fmt($delta) }}</div>
                                <div class="sub">فرق الرصيد الحالي عن الافتتاحي</div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Balances --}}
            <div class="card card-soft mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">أرصدة الحساب (حسب العملة/الفرع)</h6>
                    <span class="badge badge-soft-info">account_balances</span>
                </div>

                <div class="card-body">
                    @if(!$account)
                        <div class="alert alert-info mb-0" style="font-size: 13px;">
                            سيتم عرض الأرصدة هنا بعد ربط العميل بحساب مالي.
                        </div>
                    @elseif($balances->isEmpty())
                        <div class="alert alert-warning mb-0" style="font-size: 13px;">
                            لا توجد سجلات أرصدة للحساب حتى الآن.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>العملة</th>
                                    <th>الفرع</th>
                                    <th class="text-end">إجمالي مدين</th>
                                    <th class="text-end">إجمالي دائن</th>
                                    <th class="text-end">الرصيد</th>
                                    <th>الطبيعة</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($balances as $b)
                                    @php
                                        $bal = (float)($b->balance ?? 0);
                                        [$t, $c] = $netLabel($bal);
                                    @endphp
                                    <tr>
                                        <td>{{ $b->currency_code ?? 'EGP' }}</td>
                                        <td>{{ $b->branch_id ?? '—' }}</td>
                                        <td class="text-end amount debit num">{{ $fmt($b->debit_total) }}</td>
                                        <td class="text-end amount credit num">{{ $fmt($b->credit_total) }}</td>
                                        <td class="text-end fw-bold num">{{ $fmt($bal) }}</td>
                                        <td><span class="badge badge-soft-{{ $c }}">{{ $t }}</span></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Ledger --}}
            <div class="card card-soft mt-3">
                <div class="card-header">
                    <div class="card-title-row">
                        <h6 class="mb-0">كشف حساب العميل (آخر الحركات)</h6>
                        <span class="badge badge-soft-info">journal_entry_lines</span>
                    </div>
                </div>

                <div class="card-body">
                    @if(!$account)
                        <div class="alert alert-info mb-0" style="font-size: 13px;">
                            سيتم عرض الحركات هنا بعد ربط العميل بحساب مالي.
                        </div>
                    @elseif($lines->isEmpty())
                        <div class="alert alert-warning mb-0" style="font-size: 13px;">
                            لا توجد حركات محاسبية على حساب العميل حتى الآن.
                        </div>
                    @else

                        {{-- Ledger KPIs --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="stat">
                                    <div class="label">إجمالي مدين</div>
                                    <div class="value num">{{ $fmt($kpis['total_debit'] ?? 0) }}</div>
                                    <div class="sub">مجموع المدين (آخر 50 حركة)</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat">
                                    <div class="label">إجمالي دائن</div>
                                    <div class="value num">{{ $fmt($kpis['total_credit'] ?? 0) }}</div>
                                    <div class="sub">مجموع الدائن (آخر 50 حركة)</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat">
                                    <div class="label">صافي الحركة</div>
                                    <div class="value num">{{ $fmt($kpis['net_move'] ?? 0) }}</div>
                                    <div class="sub">
                                        <span class="badge badge-soft-{{ $netMoveCls }}">{{ $netMoveTxt }}</span>
                                        <span class="ms-2 muted">(مدين - دائن)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead>
                                <tr>
                                    <th style="width:110px;">التاريخ</th>
                                    <th style="width:150px;">رقم القيد</th>
                                    <th>البيان</th>
                                    <th style="width:90px;">العملة</th>
                                    <th class="text-end" style="width:130px;">مدين</th>
                                    <th class="text-end" style="width:130px;">دائن</th>
                                    <th class="text-end" style="width:150px;">الرصيد التراكمي</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($lines as $line)
                                    @php
                                        $d = (float)($line->debit ?? 0);
                                        $c = (float)($line->credit ?? 0);
                                        $running += ($d - $c);

                                        $je = $line->journalEntry;
                                        $entryId   = $je?->id;
                                        $entryNo   = $je?->entry_number ?? ($entryId ? ('JE#'.$entryId) : '-');
                                        $entryDate = $je?->entry_date ?? $line->created_at;

                                        $memo = $line->memo
                                            ?? $je?->description
                                            ?? '—';

                                        $ccy = $line->currency_code ?? 'EGP';

                                        // مصدر/مرجع (للعرض المحاسبي)
                                        $src = $je?->source ?? null;
                                        $refType = $je?->reference_type ?? null;
                                        $refId   = $je?->reference_id ?? null;
                                    @endphp
                                    <tr>
                                        <td class="num">
                                            {{ $entryDate ? \Carbon\Carbon::parse($entryDate)->format('Y-m-d') : '-' }}
                                        </td>

                                        <td>
                                        <span class="chip">
                                            @if($entryId && Route::has('journal_entries.show'))
                                                <a href="{{ route('journal_entries.show', $entryId) }}">
                                                    {{ $entryNo }}
                                                </a>
                                            @else
                                                {{ $entryNo }}
                                            @endif
                                        </span>

                                            @if($src)
                                                <div class="muted mt-1">
                                                    مصدر: <strong>{{ $src }}</strong>
                                                </div>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="fw-semibold">{{ $memo }}</div>

                                            <div class="muted">
                                                line #{{ (int)($line->line_no ?? 1) }}

                                                @if(!empty($line->branch_id)) • branch: {{ $line->branch_id }} @endif
                                                @if(!empty($line->cost_center_id)) • cc: {{ $line->cost_center_id }} @endif
                                                @if(!empty($line->project_id)) • project: {{ $line->project_id }} @endif
                                                @if(!empty($line->warehouse_id)) • wh: {{ $line->warehouse_id }} @endif

                                                @if($refType && $refId)
                                                    • ref: <span class="num">{{ class_basename($refType) }} #{{ $refId }}</span>
                                                @endif
                                            </div>
                                        </td>

                                        <td>{{ $ccy }}</td>

                                        <td class="text-end amount debit num">{{ $fmt($d) }}</td>
                                        <td class="text-end amount credit num">{{ $fmt($c) }}</td>

                                        <td class="text-end fw-bold num">{{ $fmt($running) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-2 muted">
                            * الرصيد التراكمي محسوب من الحركات المعروضة فقط (آخر 50 حركة).
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('click', async function(e){
            const btn = e.target.closest('.copy-btn');
            if(!btn) return;

            const text = btn.dataset.copy || '';
            if(!text) return;

            try {
                await navigator.clipboard.writeText(text);
                if (window.toastr) toastr.success('تم النسخ');
            } catch (err) {
                if (window.toastr) toastr.error('تعذر النسخ');
            }
        });
    </script>
@endsection
