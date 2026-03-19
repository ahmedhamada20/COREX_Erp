@extends('admin.layouts.master')

@section('title', 'تفاصيل القيد اليدوي')

@section('content')
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">{{ $entry->entry_number }}</h4>
                <p class="text-muted mb-0">{{ optional($entry->entry_date)->format('Y-m-d') ?? $entry->entry_date }} - {{ $entry->description ?: 'بدون وصف' }}</p>
            </div>
            <a href="{{ route('journal_entries.index') }}" class="btn btn-light btn-sm">عودة</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>الحساب</th>
                    <th>البيان</th>
                    <th class="text-end">مدين</th>
                    <th class="text-end">دائن</th>
                </tr>
                </thead>
                <tbody>
                @foreach($entry->lines as $line)
                    <tr>
                        <td>{{ $line->line_no }}</td>
                        <td>{{ $line->account?->account_number }} - {{ $line->account?->name }}</td>
                        <td>{{ $line->memo ?: '—' }}</td>
                        <td class="text-end text-success">{{ number_format((float) $line->debit, 2) }}</td>
                        <td class="text-end text-danger">{{ number_format((float) $line->credit, 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot class="table-light">
                <tr>
                    <th colspan="3" class="text-end">الإجمالي</th>
                    <th class="text-end text-success">{{ number_format((float) $entry->total_debit, 2) }}</th>
                    <th class="text-end text-danger">{{ number_format((float) $entry->total_credit, 2) }}</th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection

