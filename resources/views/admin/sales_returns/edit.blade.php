@extends('admin.layouts.master')

@section('title', 'تعديل مرتجع مبيعات #' . $return->id)

@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h4 class="mb-1"><i class="ti ti-edit me-1"></i> تعديل مرتجع مبيعات #{{ $return->id }}</h4>
                <div class="text-muted small">
                    الفاتورة الأصلية: {{ $return->invoice?->invoice_code ?? '-' }} |
                    القيمة: {{ number_format((float)$return->total, 2) }}
                </div>
            </div>
            <a href="{{ route('sales_returns.show', $return->id) }}" class="btn btn-outline-secondary btn-sm">
                <i class="ti ti-arrow-right me-1"></i> عودة
            </a>
        </div>

        @include('admin.Alerts')

        <div class="card border-0 shadow-sm" style="max-width:600px;">
            <div class="card-body">
                <form method="POST" action="{{ route('sales_returns.update', $return->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">تاريخ المرتجع</label>
                        <input type="date" name="return_date" class="form-control @error('return_date') is-invalid @enderror"
                               value="{{ old('return_date', $return->return_date) }}">
                        @error('return_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">ملاحظات</label>
                        <textarea name="notes" rows="3"
                                  class="form-control @error('notes') is-invalid @enderror"
                                  placeholder="ملاحظات اختيارية...">{{ old('notes', $return->notes ?? '') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i> حفظ التعديلات
                        </button>
                        <a href="{{ route('sales_returns.show', $return->id) }}" class="btn btn-outline-secondary">
                            إلغاء
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

