{{-- resources/views/admin/sales_returns/show.blade.php --}}
@extends('admin.layouts.master')

@section('title', 'عرض مرتجع مبيعات')

@section('content')
    <div class="container-fluid">

        {{-- Header --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h4 class="mb-1">
                    مرتجع مبيعات #{{ $return->id }}
                </h4>
                <div class="text-muted">
                    تاريخ المرتجع: {{ $return->return_date ?? '-' }}
                </div>
            </div>

            <div class="d-flex gap-2">
                @if(Route::has('sales_returns.pdf'))
                    <a href="{{ route('sales_returns.pdf', $return->id) }}" class="btn btn-outline-primary">
                        <i class="ti ti-file-text"></i> PDF
                    </a>
                @endif

                @if(Route::has('sales_returns.index'))
                    <a href="{{ route('sales_returns.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-back"></i> رجوع
                    </a>
                @endif
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if(session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif

        <div class="row g-3">

            {{-- Return Summary --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <strong>ملخص المرتجع</strong>

                        {{-- Cancel button (only if not already cancelled by zero totals) --}}
                        @if(Route::has('sales_returns.cancel') && (float)($return->total ?? 0) > 0.0001)
                            <form action="{{ route('sales_returns.cancel', $return->id) }}" method="POST"
                                  onsubmit="return confirm('تأكيد إلغاء المرتجع؟ سيتم عمل قيد عكسي إذا كان موجودًا.');">
                                @csrf
                                <button class="btn btn-danger btn-sm">
                                    <i class="ti ti-x"></i> إلغاء المرتجع
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="card-body">
                        <div class="mb-2 d-flex justify-content-between">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-bold">{{ number_format((float)$return->subtotal, 2) }}</span>
                        </div>

                        <div class="mb-2 d-flex justify-content-between">
                            <span class="text-muted">VAT</span>
                            <span class="fw-bold">{{ number_format((float)$return->vat_amount, 2) }}</span>
                        </div>

                        <hr class="my-3">

                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Total</span>
                            <span class="fw-bold fs-5">{{ number_format((float)$return->total, 2) }}</span>
                        </div>

                        <hr class="my-3">

                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Journal Entry</span>

                            @if(!empty($return->journal_entry_id))
                                {{-- لو عندك route لعرض القيد --}}
                                @if(Route::has('journal_entries.show'))
                                    <a class="btn btn-outline-success btn-sm"
                                       href="{{ route('journal_entries.show', $return->journal_entry_id) }}">
                                        عرض القيد
                                    </a>
                                @else
                                    <span class="badge bg-success">#{{ $return->journal_entry_id }}</span>
                                @endif
                            @else
                                <span class="badge bg-secondary">لا يوجد</span>
                            @endif
                        </div>

                        @if((float)($return->total ?? 0) <= 0.0001)
                            <div class="alert alert-warning mt-3 mb-0">
                                هذا المرتجع يبدو أنه <strong>ملغي</strong> (القيم = 0).
                            </div>
                        @endif

                    </div>
                </div>
            </div>

            {{-- Customer --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <strong>بيانات العميل</strong>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <div class="text-muted">الاسم</div>
                            <div class="fw-bold">{{ $return->customer->name ?? '-' }}</div>
                        </div>

                        <div class="mb-2">
                            <div class="text-muted">الكود</div>
                            <div class="fw-bold">{{ $return->customer->code ?? '-' }}</div>
                        </div>

                        <div class="mb-2">
                            <div class="text-muted">الهاتف</div>
                            <div class="fw-bold">{{ $return->customer->phone ?? '-' }}</div>
                        </div>

                        {{-- رابط كشف حساب العميل لو عندك --}}
                        @if(isset($return->customer?->id) && Route::has('customers.show'))
                            <a href="{{ route('customers.show', $return->customer->id) }}" class="btn btn-outline-primary btn-sm mt-2">
                                فتح العميل
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Invoice --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <strong>الفاتورة الأصلية</strong>
                    </div>
                    <div class="card-body">
                        @php
                            $inv = $return->invoice ?? null;
                        @endphp

                        <div class="mb-2">
                            <div class="text-muted">Invoice</div>
                            <div class="fw-bold">
                                {{ $inv?->invoice_code ?? $inv?->invoice_number ?? '-' }}
                            </div>
                        </div>

                        <div class="mb-2">
                            <div class="text-muted">تاريخ الفاتورة</div>
                            <div class="fw-bold">{{ $inv?->invoice_date ?? '-' }}</div>
                        </div>

                        <div class="mb-2">
                            <div class="text-muted">إجمالي الفاتورة</div>
                            <div class="fw-bold">{{ isset($inv->total) ? number_format((float)$inv->total, 2) : '-' }}</div>
                        </div>

                        @if($inv && Route::has('sales_invoices.show'))
                            <a href="{{ route('sales_invoices.show', $inv->id) }}" class="btn btn-outline-secondary btn-sm mt-2">
                                فتح الفاتورة
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- (Optional) JE preview if loaded --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <strong>القيد المحاسبي</strong>
                        <span class="text-muted small"> (لو موجود)</span>
                    </div>

                    <div class="card-body">
                        @if(empty($return->journal_entry_id))
                            <div class="text-muted">لا يوجد قيد مرتبط بهذا المرتجع.</div>
                        @else
                            {{-- لو عندك relation return->journalEntry lines --}}
                            @if(isset($return->journalEntry) && $return->journalEntry)
                                <div class="row g-2 mb-3">
                                    <div class="col-md-3">
                                        <div class="text-muted">رقم القيد</div>
                                        <div class="fw-bold">{{ $return->journalEntry->entry_number ?? ('#'.$return->journal_entry_id) }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-muted">تاريخ القيد</div>
                                        <div class="fw-bold">{{ $return->journalEntry->entry_date ?? '-' }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-muted">إجمالي مدين</div>
                                        <div class="fw-bold">{{ number_format((float)($return->journalEntry->total_debit ?? 0), 2) }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-muted">إجمالي دائن</div>
                                        <div class="fw-bold">{{ number_format((float)($return->journalEntry->total_credit ?? 0), 2) }}</div>
                                    </div>
                                </div>

                                @if(isset($return->journalEntry->lines) && $return->journalEntry->lines->count())
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm mb-0">
                                            <thead class="table-light">
                                            <tr>
                                                <th style="width: 40%">الحساب</th>
                                                <th class="text-end" style="width: 20%">مدين</th>
                                                <th class="text-end" style="width: 20%">دائن</th>
                                                <th style="width: 20%">ملاحظة</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($return->journalEntry->lines as $l)
                                                <tr>
                                                    <td>
                                                        {{ $l->account->name ?? '-' }}
                                                        <div class="small text-muted">{{ $l->account->account_number ?? '' }}</div>
                                                    </td>
                                                    <td class="text-end">{{ number_format((float)$l->debit, 2) }}</td>
                                                    <td class="text-end">{{ number_format((float)$l->credit, 2) }}</td>
                                                    <td>{{ $l->memo ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-muted">تم إنشاء القيد لكن لم يتم تحميل سطوره في هذه الصفحة.</div>
                                @endif

                            @else
                                <div class="text-muted">
                                    القيد موجود (#{{ $return->journal_entry_id }}) لكن لم يتم تحميله عبر relationship في هذا العرض.
                                    <br>
                                    لو تحب أظبطلك العلاقات في Model علشان يظهر هنا تلقائيًا.
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

        </div>

    </div>
@endsection
