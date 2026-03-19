@extends('admin.layouts.master')

@section('title', 'تفاصيل الأصل الثابت')

@section('content')
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">{{ $asset->asset_code }} - {{ $asset->name }}</h4>
                <p class="text-muted mb-0">{{ $asset->is_group ? 'مجموعة' : 'أصل' }}</p>
            </div>
            <a href="{{ route('fixed_assets.index') }}" class="btn btn-light btn-sm">عودة</a>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="card mb-3">
        <div class="card-body row g-2">
            <div class="col-md-4"><strong>التكلفة:</strong> {{ number_format((float) $asset->cost, 2) }}</div>
            <div class="col-md-4"><strong>القيمة التخريدية:</strong> {{ number_format((float) $asset->salvage_value, 2) }}</div>
            <div class="col-md-4"><strong>العمر الإنتاجي:</strong> {{ $asset->useful_life_months }} شهر</div>
            <div class="col-md-4"><strong>الأب:</strong> {{ $asset->parent?->name ?? '—' }}</div>
            <div class="col-md-4"><strong>بداية الإهلاك:</strong> {{ optional($asset->depreciation_start_date)->format('Y-m-d') ?? '—' }}</div>
            <div class="col-md-4"><strong>إجمالي الإهلاك المرحل:</strong> {{ number_format((float) $asset->depreciations->sum('amount'), 2) }}</div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('fixed_assets.update', $asset) }}" class="row g-2 align-items-end">
                @csrf
                @method('PUT')
                <div class="col-md-3">
                    <label class="form-label">الحالة</label>
                    <select class="form-select" name="status">
                        <option value="1" @selected($asset->status)>نشط</option>
                        <option value="0" @selected(! $asset->status)>غير نشط</option>
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label">ملاحظات</label>
                    <input type="text" class="form-control" name="notes" value="{{ $asset->notes }}">
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
                    <th>الفترة</th>
                    <th class="text-end">المبلغ</th>
                    <th>رقم القيد</th>
                </tr>
                </thead>
                <tbody>
                @forelse($asset->depreciations as $depreciation)
                    <tr>
                        <td>{{ $depreciation->period_key }}</td>
                        <td class="text-end">{{ number_format((float) $depreciation->amount, 2) }}</td>
                        <td>
                            @if($depreciation->journalEntry)
                                <a href="{{ route('journal_entries.show', $depreciation->journalEntry->id) }}">{{ $depreciation->journalEntry->entry_number }}</a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center py-4 text-muted">لا توجد حركات إهلاك</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

