@extends('admin.layouts.master')

@section('title', 'سندات القيد اليدوي')

@section('content')
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">سندات القيد اليدوي</h4>
                <p class="text-muted mb-0">عرض القيود اليدوية المرحّلة على دفتر الأستاذ.</p>
            </div>
            <a href="{{ route('journal_entries.create') }}" class="btn btn-primary btn-sm">
                <i class="ti ti-plus"></i> قيد يدوي جديد
            </a>
        </div>
    </div>

    @include('admin.Alerts')

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                <tr>
                    <th>رقم القيد</th>
                    <th>التاريخ</th>
                    <th>الوصف</th>
                    <th class="text-end">مدين</th>
                    <th class="text-end">دائن</th>
                    <th class="text-center">السطور</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td>{{ $entry->entry_number }}</td>
                        <td>{{ optional($entry->entry_date)->format('Y-m-d') ?? $entry->entry_date }}</td>
                        <td>{{ $entry->description ?: '—' }}</td>
                        <td class="text-end text-success">{{ number_format((float) $entry->total_debit, 2) }}</td>
                        <td class="text-end text-danger">{{ number_format((float) $entry->total_credit, 2) }}</td>
                        <td class="text-center">{{ $entry->lines_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('journal_entries.show', $entry) }}" class="btn btn-outline-primary btn-sm">عرض</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">لا توجد سندات قيد يدوي حتى الآن</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $entries->links() }}
    </div>
@endsection

