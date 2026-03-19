@extends('admin.layouts.master')

@section('title', 'ميزان المراجعة')

@section('content')
    <div class="row">
        <div class="col-12">
            @include('admin.Alerts')

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3"
                     style="background:linear-gradient(135deg,#fd7e14,#dc6c0c);border-radius:.5rem;">
                    <div>
                        <h4 class="text-white fw-bold mb-1"><i class="ti ti-list-check me-1"></i> ميزان المراجعة</h4>
                        <p class="text-white-50 mb-0">
                            من {{ \Carbon\Carbon::parse($fromDate)->format('Y/m/d') }}
                            إلى {{ \Carbon\Carbon::parse($toDate)->format('Y/m/d') }}
                        </p>
                    </div>
                    <a href="{{ route('reports.index') }}" class="btn btn-light btn-sm">
                        <i class="ti ti-arrow-right me-1"></i> عودة
                    </a>
                </div>
            </div>

            {{-- Date Filter --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.trial_balance') }}" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">من تاريخ</label>
                            <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">إلى تاريخ</label>
                            <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-warning w-100 text-white"><i class="ti ti-search me-1"></i> عرض</button>
                        </div>
                    </form>
                </div>
            </div>

            @php
                $isBalanced = abs($totalDebit - $totalCredit) < 0.01;
            @endphp

            @if(!$isBalanced)
                <div class="alert alert-warning mb-3">
                    <i class="ti ti-alert-triangle me-1"></i>
                    تحذير: ميزان المراجعة غير متوازن —
                    مجموع المدين: {{ number_format($totalDebit, 2) }} |
                    مجموع الدائن: {{ number_format($totalCredit, 2) }}
                </div>
            @endif

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>رقم الحساب</th>
                                <th>اسم الحساب</th>
                                <th>نوع الحساب</th>
                                <th class="text-end">مدين</th>
                                <th class="text-end">دائن</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accounts as $account)
                                <tr>
                                    <td><span class="badge bg-secondary">{{ $account->account_number }}</span></td>
                                    <td>{{ $account->name }}</td>
                                    <td><span class="text-muted small">{{ $account->type_name }}</span></td>
                                    <td class="text-end">
                                        @if($account->normal_side === 'debit')
                                            <span class="text-success fw-semibold">{{ number_format((float)$account->balance, 2) }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($account->normal_side === 'credit')
                                            <span class="text-danger fw-semibold">{{ number_format((float)$account->balance, 2) }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-4 text-muted">لا توجد بيانات</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-dark">
                            <tr>
                                <th colspan="3">الإجمالي</th>
                                <th class="text-end text-success">{{ number_format($totalDebit, 2) }}</th>
                                <th class="text-end text-danger">{{ number_format($totalCredit, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

