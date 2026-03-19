@extends('admin.layouts.master')

@section('title', 'الأصول الثابتة')

@section('content')
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">الأصول الثابتة</h4>
                <p class="text-muted mb-0">شجرة الأصول وسجل الإهلاك الدوري.</p>
            </div>
            <a href="{{ route('fixed_assets.create') }}" class="btn btn-primary btn-sm">أصل جديد</a>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="card mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('fixed_assets.run_depreciation') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" name="period_from" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" name="period_to" class="form-control" value="{{ now()->endOfMonth()->toDateString() }}" required>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-success w-100">تشغيل الإهلاك الدوري</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                <tr>
                    <th>الكود</th>
                    <th>الاسم</th>
                    <th>الأب</th>
                    <th class="text-end">التكلفة</th>
                    <th class="text-end">مجمع الإهلاك</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($assets as $asset)
                    <tr>
                        <td>{{ $asset->asset_code }}</td>
                        <td>{{ $asset->name }}</td>
                        <td>{{ $asset->parent?->name ?? '—' }}</td>
                        <td class="text-end">{{ number_format((float) $asset->cost, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $asset->depreciations->sum('amount'), 2) }}</td>
                        <td class="text-end"><a href="{{ route('fixed_assets.show', $asset) }}" class="btn btn-outline-primary btn-sm">عرض</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">لا توجد أصول ثابتة</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $assets->links() }}</div>
@endsection

