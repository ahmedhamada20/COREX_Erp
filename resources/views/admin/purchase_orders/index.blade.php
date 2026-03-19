@extends('admin.layouts.master')

@section('title', 'أوامر الشراء')

@section('content')
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">أوامر الشراء</h4>
                <p class="text-muted mb-0">إدارة أوامر الشراء قبل تحويلها إلى فاتورة.</p>
            </div>
            <a href="{{ route('purchase_orders.create') }}" class="btn btn-primary btn-sm">أمر شراء جديد</a>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>رقم الأمر</th>
                        <th>التاريخ</th>
                        <th>المورد</th>
                        <th>الحالة</th>
                        <th class="text-end">الإجمالي</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ optional($order->order_date)->format('Y-m-d') ?? $order->order_date }}</td>
                        <td>{{ $order->supplier?->name }}</td>
                        <td>{{ $order->status }}</td>
                        <td class="text-end">{{ number_format((float) $order->total, 2) }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('purchase_orders.convert_to_invoice', $order) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-success btn-sm" @disabled($order->status === 'cancelled')>تحويل</button>
                            </form>
                            <a href="{{ route('purchase_orders.show', $order) }}" class="btn btn-outline-primary btn-sm">عرض</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">لا توجد أوامر شراء</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $orders->links() }}</div>
@endsection

