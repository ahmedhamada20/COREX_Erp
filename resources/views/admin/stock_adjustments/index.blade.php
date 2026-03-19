@extends('admin.layouts.master')

@section('title', 'تسويات المخزون')

@section('content')
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">تسويات المخزون</h4>
                <p class="text-muted mb-0">سندات إضافة/خصم مخزون يدوية مع أثر على حركة المخزون.</p>
            </div>
            <a href="{{ route('stock_adjustments.create') }}" class="btn btn-primary btn-sm">تسوية جديدة</a>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>رقم التسوية</th>
                        <th>التاريخ</th>
                        <th>الحالة</th>
                        <th class="text-center">عدد السطور</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($adjustments as $adjustment)
                    <tr>
                        <td>{{ $adjustment->adjustment_number }}</td>
                        <td>{{ optional($adjustment->adjustment_date)->format('Y-m-d') ?? $adjustment->adjustment_date }}</td>
                        <td>{{ $adjustment->status }}</td>
                        <td class="text-center">{{ $adjustment->lines_count }}</td>
                        <td class="text-end"><a href="{{ route('stock_adjustments.show', $adjustment) }}" class="btn btn-outline-primary btn-sm">عرض</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">لا توجد تسويات مخزون</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $adjustments->links() }}</div>
@endsection

