{{-- resources/views/admin/accounts/show.blade.php --}}
@extends('admin.layouts.master')

@section('title', 'عرض حساب مالي')

@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        .kpi {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px;
            background: #fff;
        }

        .kpi .label {
            font-size: 12px;
            color: #64748b;
        }

        .kpi .value {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
        }

        .badge-soft-success {
            background: rgba(34, 197, 94, .12);
            color: #16a34a;
        }

        .badge-soft-danger {
            background: rgba(239, 68, 68, .12);
            color: #dc2626;
        }

        .badge-soft-info {
            background: rgba(59, 130, 246, .12);
            color: #2563eb;
        }

        .badge-soft-warning {
            background: rgba(245, 158, 11, .14);
            color: #b45309;
        }

        .meta {
            font-size: 12px;
            color: #64748b;
        }

        .path {
            font-size: 13px;
            color: #334155;
            font-weight: 700;
        }

        .table td, .table th {
            vertical-align: middle;
        }

        .copy-btn {
            cursor: pointer;
            text-decoration: underline;
        }

        .line {
            height: 1px;
            background: #e2e8f0;
            margin: 12px 0;
        }

        /* Ledger */
        .ledger-kpi {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px;
            background: #fff;
        }

        .ledger-kpi .label {
            font-size: 12px;
            color: #64748b;
        }

        .ledger-kpi .value {
            font-size: 16px;
            font-weight: 900;
        }

        .amt-dr {
            color: #16a34a;
            font-weight: 800;
        }

        .amt-cr {
            color: #dc2626;
            font-weight: 800;
        }

        .net-pill {
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 900;
            display: inline-block;
        }

        .net-dr {
            background: rgba(34, 197, 94, .12);
            color: #16a34a;
        }

        .net-cr {
            background: rgba(239, 68, 68, .12);
            color: #dc2626;
        }

        .ledger-table td, .ledger-table th {
            white-space: nowrap;
        }

        .ledger-memo {
            white-space: normal;
            color: #334155;
            font-weight: 700;
        }

        .small-muted {
            font-size: 12px;
            color: #64748b;
        }

        .card-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
    </style>
@endsection

@section('content')
    @php
        // ==== Safe defaults لو الكنترولر مبعتش حاجات ====
        $balances = $balances ?? collect();
        $lines    = $lines ?? null;

        // Summary from account_balances (أفضل وأسرع)
        $balDebit  = (float) $balances->sum('debit_total');
        $balCredit = (float) $balances->sum('credit_total');
        $balNet    = $balDebit - $balCredit; // + مدين / - دائن
        $balSide   = $balNet >= 0 ? 'مدين' : 'دائن';
        $balAbs    = abs($balNet);

        // Ledger totals from displayed page (مش إجمالي النظام كله)
        $pageDebit  = 0.0;
        $pageCredit = 0.0;

        // رصيد افتتاحي للـ running balance داخل الصفحة
        // لو عندك طريقة تضبطه من الكنترولر ابعته في $openingBalance
        $running = (float)($openingBalance ?? 0);
    @endphp

    <div class="page-content">
        <div class="container-fluid">

            {{-- Header --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-sm-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-1">{{ $account->name }}</h4>
                            <div class="meta">
                                <span class="badge badge-soft-info">{{ $account->type?->name ?? '-' }}</span>
                                <span class="mx-2">•</span>
                                @if($account->status)
                                    <span class="badge badge-soft-success">نشط</span>
                                @else
                                    <span class="badge badge-soft-danger">غير نشط</span>
                                @endif
                                <span class="mx-2">•</span>
                                <span>رقم الحساب: <span
                                        class="fw-bold">{{ $account->account_number ?? '-' }}</span></span>
                            </div>
                            <div class="mt-2 path">
                                {{ $account->path }}
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="{{ route('accounts.index') }}" class="btn btn-light btn-sm">
                                <i class="ti ti-arrow-left"></i> رجوع
                            </a>
                            <a href="{{ route('accounts.edit', $account->id) }}" class="btn btn-warning btn-sm">
                                تعديل
                            </a>
                            <form action="{{ route('accounts.destroy', $account->id) }}" method="POST"
                                  onsubmit="return confirm('تأكيد الحذف؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm">حذف</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KPIs --}}
            <div class="row g-3 mb-3">
                <div class="col-md-6 col-xl-3">
                    <div class="kpi">
                        <div class="label">الرصيد الافتتاحي</div>
                        <div class="value">{{ number_format((float)($account->start_balance ?? 0), 2) }}</div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="kpi">
                        <div class="label">الرصيد الحالي</div>
                        <div class="value">{{ number_format((float)($account->current_balance ?? 0), 2) }}</div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="kpi">
                        <div class="label">إجمالي حركة (من account_balances)</div>
                        <div class="value" style="font-size:14px;">
                            {{ number_format((float)$balAbs, 2) }}
                            <span class="ms-2 net-pill {{ $balSide === 'مدين' ? 'net-dr' : 'net-cr' }}">
                                {{ $balSide }}
                            </span>
                        </div>
                        <div class="meta mt-1">صافي = إجمالي مدين - إجمالي دائن</div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="kpi">
                        <div class="label">الأب</div>
                        <div class="value" style="font-size:14px;">
                            {{ $account->parent?->name ?? '— (حساب رئيسي)' }}
                        </div>
                    </div>
                </div>
            </div>


            <div class="row g-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">إجراءات سريعة</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('accounts.edit', $account->id) }}" class="btn btn-warning">
                                    تعديل الحساب
                                </a>

                                <a href="{{ route('accounts.create', ['parent_account_id' => $account->id]) }}"
                                   class="btn btn-primary">
                                    إضافة حساب فرعي
                                </a>

                                <a href="{{ route('accounts.index', ['search' => $account->name]) }}"
                                   class="btn btn-light">
                                    بحث عن حسابات مشابهة
                                </a>
                            </div>

                            <div class="line"></div>

                            <div class="alert alert-info mb-0" style="font-size: 13px;">
                                <div class="fw-bold mb-1">معلومة محاسبية</div>
                                <div>
                                    لا يُفضل حذف الحساب بعد استخدامه في القيود.
                                    الأفضل إيقاف الحساب للحفاظ على سلامة التقارير.
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Helper --}}

                </div>
            </div>
            <div class="row g-3">
                {{-- Details --}}
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title-row">
                                <h5 class="mb-0">تفاصيل الحساب</h5>
                                <span class="meta">
                                    آخر تحديث: {{ $account->updated_at?->format('Y-m-d H:i') ?? '-' }}
                                </span>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="meta">اسم الحساب</div>
                                    <div class="fw-bold">{{ $account->name }}</div>
                                </div>

                                <div class="col-md-6">
                                    <div class="meta">رقم الحساب</div>
                                    <div class="fw-bold">
                                        {{ $account->account_number ?? '-' }}
                                        @if($account->account_number)
                                            <span class="text-muted ms-2 copy-btn"
                                                  data-copy="{{ $account->account_number }}">نسخ</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="meta">نوع الحساب</div>
                                    <div class="fw-bold">{{ $account->type?->name ?? '-' }}</div>
                                </div>

                                <div class="col-md-6">
                                    <div class="meta">الحالة</div>
                                    <div class="fw-bold">
                                        @if($account->status)
                                            <span class="badge badge-soft-success">نشط</span>
                                        @else
                                            <span class="badge badge-soft-danger">غير نشط</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="meta">تاريخ</div>
                                    <div class="fw-bold">{{ $account->date?->format('Y-m-d') ?? '-' }}</div>
                                </div>

                                <div class="col-md-6">
                                    <div class="meta">مرجع خارجي</div>
                                    <div class="fw-bold">{{ $account->other_table_id ?? '-' }}</div>
                                </div>

                                <div class="col-12">
                                    <div class="meta">الملاحظات</div>
                                    <div class="fw-bold">{{ $account->notes ?: '—' }}</div>
                                </div>

                                <div class="col-12">
                                    <div class="line"></div>
                                </div>

                                <div class="col-md-4">
                                    <div class="meta">تم الإنشاء</div>
                                    <div class="fw-bold">{{ $account->created_at?->format('Y-m-d H:i') ?? '-' }}</div>
                                </div>

                                <div class="col-md-4">
                                    <div class="meta">آخر تحديث</div>
                                    <div class="fw-bold">{{ $account->updated_at?->format('Y-m-d H:i') ?? '-' }}</div>
                                </div>

                                <div class="col-md-4">
                                    <div class="meta">آخر تحديث بواسطة</div>
                                    <div class="fw-bold">{{ $account->updated_by ?? '-' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- account_balances breakdown --}}
                    <div class="card mt-3">
                        <div class="card-header">
                            <div class="card-title-row">
                                <h5 class="mb-0">ملخص الأرصدة (حسب العملة/الفرع)</h5>
                                <span class="meta">مصدر البيانات: account_balances</span>
                            </div>
                        </div>
                        <div class="card-body">
                            @if($balances->count())
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                        <tr>
                                            <th class="text-start">العملة</th>
                                            <th class="text-start">الفرع</th>
                                            <th class="text-start">إجمالي مدين</th>
                                            <th class="text-start">إجمالي دائن</th>
                                            <th class="text-start">الرصيد</th>
                                            <th class="text-start">الاتجاه</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($balances as $b)
                                            @php
                                                $d = (float)($b->debit_total ?? 0);
                                                $c = (float)($b->credit_total ?? 0);
                                                $bal = (float)($b->balance ?? ($d - $c));
                                                $side = $bal >= 0 ? 'مدين' : 'دائن';
                                            @endphp
                                            <tr>
                                                <td class="text-start fw-bold">{{ $b->currency_code ?? 'EGP' }}</td>
                                                <td class="text-start">
                                                    @if($b->branch_id)
                                                        <span
                                                            class="badge badge-soft-warning">فرع #{{ $b->branch_id }}</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-start"><span
                                                        class="amt-dr">{{ number_format($d, 2) }}</span></td>
                                                <td class="text-start"><span
                                                        class="amt-cr">{{ number_format($c, 2) }}</span></td>
                                                <td class="text-start fw-bold">{{ number_format(abs($bal), 2) }}</td>
                                                <td class="text-start">
                                                    <span
                                                        class="net-pill {{ $side === 'مدين' ? 'net-dr' : 'net-cr' }}">{{ $side }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                        <tfoot>
                                        <tr>
                                            <th class="text-start" colspan="2">الإجمالي</th>
                                            <th class="text-start"><span
                                                    class="amt-dr">{{ number_format($balDebit, 2) }}</span></th>
                                            <th class="text-start"><span
                                                    class="amt-cr">{{ number_format($balCredit, 2) }}</span></th>
                                            <th class="text-start fw-bold">{{ number_format($balAbs, 2) }}</th>
                                            <th class="text-start">
                                                <span
                                                    class="net-pill {{ $balSide === 'مدين' ? 'net-dr' : 'net-cr' }}">{{ $balSide }}</span>
                                            </th>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <div class="text-muted">لا يوجد أرصدة محفوظة لهذا الحساب في account_balances.</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Children --}}
                    <div class="card mt-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">الحسابات الفرعية</h5>
                            <span class="text-muted" style="font-size:12px;">
                                {{ $account->children?->count() ? $account->children->count() : 0 }} حساب
                            </span>
                        </div>

                        <div class="card-body">
                            @if($account->children && $account->children->count())
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                        <tr>
                                            <th class="text-start">#</th>
                                            <th class="text-start">الاسم</th>
                                            <th class="text-start">النوع</th>
                                            <th class="text-start">الرصيد الحالي</th>
                                            <th class="text-start">الحالة</th>
                                            <th class="text-start">إجراءات</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($account->children as $i => $child)
                                            <tr>
                                                <td class="text-start">{{ $i + 1 }}</td>
                                                <td class="text-start">
                                                    <div class="fw-bold">{{ $child->name }}</div>
                                                    <div class="meta">{{ $child->path }}</div>
                                                </td>
                                                <td class="text-start">
                                                    <span
                                                        class="badge badge-soft-info">{{ $child->type?->name ?? '-' }}</span>
                                                </td>
                                                <td class="text-start">{{ number_format((float)($child->current_balance ?? 0), 2) }}</td>
                                                <td class="text-start">
                                                    @if($child->status)
                                                        <span class="badge badge-soft-success">نشط</span>
                                                    @else
                                                        <span class="badge badge-soft-danger">غير نشط</span>
                                                    @endif
                                                </td>
                                                <td class="text-start">
                                                    <div class="d-flex gap-1">
                                                        <a href="{{ route('accounts.show', $child->id) }}"
                                                           class="btn btn-light btn-sm">عرض</a>
                                                        <a href="{{ route('accounts.edit', $child->id) }}"
                                                           class="btn btn-warning btn-sm">تعديل</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <div class="text-muted mb-2">لا توجد حسابات فرعية لهذا الحساب.</div>
                                    <a href="{{ route('accounts.create', ['parent_account_id' => $account->id]) }}"
                                       class="btn btn-primary btn-sm">
                                        إضافة حساب فرعي
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Ledger (حركات الحساب) --}}
                    {{-- Ledger (حركات الحساب) --}}
                    <div class="card mt-3">
                        <div class="card-header">
                            <div class="card-title-row">
                                <div>
                                    <h5 class="mb-0">الحركات على الحساب (Ledger)</h5>
                                    <div class="small-muted mt-1">مدين / دائن + رصيد جاري داخل الصفحة</div>
                                </div>

                                <span class="net-pill {{ $balSide === 'مدين' ? 'net-dr' : 'net-cr' }}">
                                    صافي إجمالي: {{ number_format($balAbs, 2) }} ({{ $balSide }})
                                </span>
                            </div>
                        </div>

                        <div class="card-body">
                            {{-- KPIs للحركات --}}
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <div class="ledger-kpi">
                                        <div class="label">إجمالي مدين (account_balances)</div>
                                        <div class="value amt-dr">{{ number_format($balDebit, 2) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="ledger-kpi">
                                        <div class="label">إجمالي دائن (account_balances)</div>
                                        <div class="value amt-cr">{{ number_format($balCredit, 2) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="ledger-kpi">
                                        <div class="label">الرصيد الحالي (من الحساب)</div>
                                        <div
                                            class="value">{{ number_format((float)($account->current_balance ?? 0), 2) }}</div>
                                    </div>
                                </div>
                            </div>

                            @if($lines && $lines->count())
                                @php
                                    // إجماليات الصفحة + رصيد جاري للعرض
                                    $pageDebit  = 0.0;
                                    $pageCredit = 0.0;

                                    // لو مش مبعوت من الكنترولر يبقى 0، تقدر تبعته صح لو عملت Pagination
                                    $running = (float)($openingBalance ?? 0);
                                @endphp

                                <div class="table-responsive">
                                    <table class="table table-hover ledger-table align-middle">
                                        <thead>
                                        <tr>
                                            <th class="text-start">التاريخ</th>
                                            <th class="text-start">رقم القيد</th>
                                            <th class="text-start">البيان</th>
                                            <th class="text-start">مدين</th>
                                            <th class="text-start">دائن</th>
                                            <th class="text-start">الرصيد الجاري</th>
                                            <th class="text-start">عملة</th>
                                            <th class="text-start">ملاحظة</th>
                                        </tr>
                                        </thead>

                                        <tbody>
                                        @foreach($lines as $line)
                                            @php
                                                $je = $line->journalEntry;

                                                // ✅ لازم نعرّفها هنا (كانت سبب Undefined variable)
                                                $d = (float)($line->debit  ?? 0);
                                                $c = (float)($line->credit ?? 0);

                                                $pageDebit  += $d;
                                                $pageCredit += $c;

                                                // running balance داخل الصفحة
                                                $running += ($d - $c);
                                                $runAbs  = abs($running);
                                                $runSide = $running >= 0 ? 'مدين' : 'دائن';

                                                // العملة (حسب تصميمك)
                                                $currency = $line->currency_code ?? $je->currency_code ?? ($account->currency_code ?? 'EGP');

                                                $entryNumber = $je?->entry_number ?? '-';
                                                $entryDate   = $je?->entry_date ? $je->entry_date->format('Y-m-d') : '-';

                                                $refText = '';
                                                if ($je?->reference_type && $je?->reference_id) {
                                                    $refText = $je->reference_type . ':' . $je->reference_id;
                                                }
                                            @endphp

                                            <tr>
                                                <td class="text-start">
                                                    <div class="fw-bold">{{ $entryDate }}</div>
                                                    <div class="small-muted">#{{ $line->line_no ?? 1 }}</div>
                                                </td>

                                                <td class="text-start">
                                                    <div class="fw-bold">{{ $entryNumber }}</div>

                                                    @if($je?->source)
                                                        <div class="small-muted">Source: {{ $je->source }}</div>
                                                    @endif

                                                    @if($refText)
                                                        <div class="small-muted">Ref: {{ $refText }}</div>
                                                    @endif
                                                </td>

                                                <td class="text-start">
                                                    <div class="ledger-memo">
                                                        {{ $je?->description ?? ($line->memo ?? '-') }}
                                                    </div>
                                                    @if(!empty($je?->status))
                                                        <div class="small-muted">الحالة: {{ $je->status }}</div>
                                                    @endif
                                                </td>

                                                <td class="text-start">
                                                    @if($d > 0)
                                                        <span class="amt-dr">{{ number_format($d, 2) }}</span>
                                                    @else
                                                        —
                                                    @endif
                                                </td>

                                                <td class="text-start">
                                                    @if($c > 0)
                                                        <span class="amt-cr">{{ number_format($c, 2) }}</span>
                                                    @else
                                                        —
                                                    @endif
                                                </td>

                                                <td class="text-start">
                                                    <div class="fw-bold">{{ number_format($runAbs, 2) }}</div>
                                                    <span
                                                        class="net-pill {{ $runSide === 'مدين' ? 'net-dr' : 'net-cr' }}">{{ $runSide }}</span>
                                                </td>

                                                <td class="text-start">
                                                    {{ $currency }}
                                                    @if(!empty($line->fx_rate))
                                                        <div class="small-muted">
                                                            FX: {{ number_format((float)$line->fx_rate, 6) }}</div>
                                                    @endif
                                                </td>

                                                <td class="text-start">
                                                    <div class="small-muted">{{ $line->memo ?? '—' }}</div>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>

                                        <tfoot>
                                        @php
                                            $pageNet  = $pageDebit - $pageCredit;
                                            $pageSide = $pageNet >= 0 ? 'مدين' : 'دائن';
                                            $pageAbs  = abs($pageNet);
                                        @endphp
                                        <tr>
                                            <th class="text-start" colspan="3">إجمالي الصفحة</th>
                                            <th class="text-start"><span
                                                    class="amt-dr">{{ number_format($pageDebit, 2) }}</span></th>
                                            <th class="text-start"><span
                                                    class="amt-cr">{{ number_format($pageCredit, 2) }}</span></th>
                                            <th class="text-start">
                                                <span class="fw-bold">{{ number_format($pageAbs, 2) }}</span>
                                                <span
                                                    class="net-pill {{ $pageSide === 'مدين' ? 'net-dr' : 'net-cr' }}">{{ $pageSide }}</span>
                                            </th>
                                            <th class="text-start" colspan="2"></th>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <div class="text-muted mb-2">لا توجد حركات على هذا الحساب حتى الآن.</div>
                                </div>
                            @endif

                            <div class="alert alert-info mt-3 mb-0" style="font-size: 13px;">
                                <div class="fw-bold mb-1">قراءة سريعة</div>
                                <div>
                                    لو <b>المدين</b> أكبر من <b>الدائن</b> → الصافي <b>مدين</b>.
                                    ولو <b>الدائن</b> أكبر → الصافي <b>دائن</b>.
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
@endsection

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        @if(session('success')) toastr.success(@json(session('success')));
        @endif
        @if(session('error')) toastr.error(@json(session('error')));
        @endif

        document.addEventListener('click', async function (e) {
            const btn = e.target.closest('.copy-btn');
            if (!btn) return;

            const text = btn.dataset.copy || '';
            if (!text) return;

            try {
                await navigator.clipboard.writeText(text);
                toastr.success('تم نسخ الرقم');
            } catch (err) {
                toastr.error('تعذر النسخ');
            }
        });
    </script>
@endsection
