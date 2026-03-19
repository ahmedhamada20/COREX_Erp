@extends('admin.layouts.master')

@section('title', 'تفاصيل تسوية المخزون')

@section('content')
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">{{ $adjustment->adjustment_number }}</h4>
                <p class="text-muted mb-0">{{ optional($adjustment->adjustment_date)->format('Y-m-d') ?? $adjustment->adjustment_date }}</p>
            </div>
            <a href="{{ route('stock_adjustments.index') }}" class="btn btn-light btn-sm">عودة</a>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="card mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('stock_adjustments.update', $adjustment) }}" class="row g-2 align-items-end">
                @csrf
                @method('PUT')
                <div class="col-md-10">
                    <label class="form-label">ملاحظات</label>
                    <input type="text" name="notes" class="form-control" value="{{ $adjustment->notes }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100">تحديث</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                <tr>
                    <th>الصنف</th>
                    <th>المخزن</th>
                    <th class="text-end">فرق الكمية</th>
                    <th class="text-end">تكلفة الوحدة</th>
                </tr>
                </thead>
                <tbody>
                @foreach($adjustment->lines as $line)
                    <tr>
                        <td>{{ $line->item?->name }}</td>
                        <td>{{ $line->store?->name ?? '—' }}</td>
                        <td class="text-end {{ (float) $line->quantity_diff >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format((float) $line->quantity_diff, 4) }}
                        </td>
                        <td class="text-end">{{ number_format((float) $line->unit_cost, 4) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

