@extends('admin.layouts.master')

@section('title', 'تفاصيل أمر الشراء')

@section('content')
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">{{ $order->order_number }}</h4>
                <p class="text-muted mb-0">{{ $order->supplier?->name }} - {{ optional($order->order_date)->format('Y-m-d') ?? $order->order_date }}</p>
            </div>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('purchase_orders.convert_to_invoice', $order) }}">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm" @disabled($order->status === 'cancelled')>
                        تحويل إلى فاتورة مشتريات
                    </button>
                </form>
                <a href="{{ route('purchase_orders.index') }}" class="btn btn-light btn-sm">عودة</a>
            </div>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="card mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('purchase_orders.update', $order) }}" class="row g-2 align-items-end">
                @csrf
                @method('PUT')
                <div class="col-md-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select">
                        @foreach(['draft','approved','closed','cancelled'] as $status)
                            <option value="{{ $status }}" @selected($order->status === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-9">
                    <button class="btn btn-primary btn-sm">تحديث الحالة</button>
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
                    <th class="text-end">الكمية</th>
                    <th class="text-end">سعر الوحدة</th>
                    <th class="text-end">الإجمالي</th>
                </tr>
                </thead>
                <tbody>
                @foreach($order->items as $line)
                    <tr>
                        <td>{{ $line->item?->name }}</td>
                        <td class="text-end">{{ number_format((float) $line->quantity, 4) }}</td>
                        <td class="text-end">{{ number_format((float) $line->unit_price, 4) }}</td>
                        <td class="text-end">{{ number_format((float) $line->line_total, 4) }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot class="table-light">
                <tr>
                    <th colspan="3" class="text-end">الإجمالي</th>
                    <th class="text-end">{{ number_format((float) $order->total, 2) }}</th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection

