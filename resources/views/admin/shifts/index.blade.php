@extends('admin.layouts.master')

@section('title', 'الشفتات')

@section('css')
@endsection

@section('content')

    <div class="content-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">
            الشفتات
            <span class="text-muted fw-normal ms-2">— {{ auth()->user()->name }}</span>
        </h5>

        <div class="d-flex gap-2">
            <a href="{{ route('shifts.create') }}" class="btn btn-sm btn-primary">
                <i class="ti ti-play"></i>
                فتح شفت
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            @include('admin.Alerts')

            <div class="card table-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="fw-semibold">قائمة الشفتات</div>

                    <span class="badge bg-info">
                        لا يمكن تسجيل حركة خزنة بدون شفت مفتوح
                    </span>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="pc-dt-simple">
                            <thead>
                            <tr>
                                <th style="width:60px">#</th>
                                <th>الخزنة</th>
                                <th>المستخدم</th>
                                <th>فتح</th>
                                <th>إقفال</th>
                                <th>Opening</th>
                                <th>Expected</th>
                                <th>Actual</th>
                                <th>فرق</th>
                                <th>الحالة</th>
                                <th style="width:170px">إجراءات</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($shifts as $shift)
                                @php
                                    $isOpen = $shift->status === 'open';
                                @endphp

                                <tr>
                                    <td>{{ $loop->iteration + ($shifts->currentPage()-1)*$shifts->perPage() }}</td>

                                    <td>
                                        <div class="fw-semibold">{{ $shift->treasury?->name ?? '-' }}</div>
                                        <div class="text-muted small">#{{ $shift->treasury_id }}</div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold">{{ $shift->actor?->name ?? '-' }}</div>
                                        <div class="text-muted small">#{{ $shift->actor_user_id }}</div>
                                    </td>

                                    <td>
                                        <div class="small">
                                            {{ optional($shift->opened_at)->timezone('Africa/Cairo')->format('Y-m-d h:i A') }}
                                        </div>
                                    </td>

                                    <td>
                                        @if($shift->closed_at)
                                            <div class="small">
                                                {{ optional($shift->closed_at)->timezone('Africa/Cairo')->format('Y-m-d h:i A') }}
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td class="num">{{ number_format((float)$shift->opening_balance, 2) }}</td>
                                    <td class="num">{{ number_format((float)$shift->closing_expected, 2) }}</td>
                                    <td class="num">
                                        {{ $shift->closing_actual !== null ? number_format((float)$shift->closing_actual, 2) : '—' }}
                                    </td>
                                    <td class="num">
                                        @php $diff = (float)$shift->difference; @endphp
                                        @if(!$isOpen)
                                            <span class="{{ $diff == 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-primary') }}">
                                                {{ number_format($diff, 2) }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($isOpen)
                                            <span class="badge bg-warning text-dark">مفتوح</span>
                                        @else
                                            <span class="badge bg-success">مغلق</span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="d-flex gap-1 justify-content-end">
                                            {{-- Show --}}
                                            <a href="{{ route('shifts.show', $shift->id) }}"
                                               class="btn btn-sm btn-light"
                                               title="تفاصيل الشفت ">
                                                <i class="ti ti-eye"></i>
                                            </a>

                                            {{-- Show --}}
{{--                                            <a href="{{ route('treasuries.show', $shift->treasury_id) }}"--}}
{{--                                               class="btn btn-sm btn-light"--}}
{{--                                               title="عرض الخزنة">--}}
{{--                                                <i class="ti ti-eye"></i>--}}
{{--                                            </a>--}}

                                            @if($isOpen)
                                                {{-- Close --}}
                                                <a href="{{ route('shifts.close.form') }}"
                                                   class="btn btn-sm btn-warning"
                                                   title="قفل الشفت">
                                                    <i class="ti ti-lock"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">
                                        لا يوجد شفتات حتى الآن.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $shifts->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection

@section('js')
@endsection
