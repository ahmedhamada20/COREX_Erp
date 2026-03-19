@extends('admin.layouts.master')

@section('title', 'فتح شفت')

@section('css')
@endsection

@section('content')

    <div class="content-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">
            فتح شفت
            <span class="text-muted fw-normal ms-2">— {{ auth()->user()->name }}</span>
        </h5>

        <a href="{{ route('shifts.index') }}" class="btn btn-sm btn-light">
            <i class="ti ti-arrow-right"></i>
            رجوع
        </a>
    </div>

    <div class="row">
        <div class="col-12">

            @include('admin.Alerts')

            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="fw-semibold">بيانات الشفت</div>
                    <span class="badge bg-info">ERP</span>
                </div>

                <div class="card-body">
                    <div class="alert alert-info small mb-4">
                        <i class="ti ti-info-circle"></i>
                        لا يمكن تسجيل أي حركة خزنة إلا بوجود شفت مفتوح على نفس الخزنة.
                    </div>

                    <form action="{{ route('shifts.store') }}" method="POST">
                        @csrf

                        {{-- Treasury --}}
                        <div class="mb-3">
                            <label class="form-label" for="treasury_id">الخزنة</label>
                            <select name="treasury_id"
                                    id="treasury_id"
                                    class="form-select @error('treasury_id') is-invalid @enderror"
                                    required>
                                <option value="">اختر خزنة...</option>

                                @foreach($treasuries as $t)
                                    <option value="{{ $t->id }}"
                                        @selected(old('treasury_id', $selectedTreasury ?? null) == $t->id)>
                                        {{ $t->name }} @if($t->is_master) — (رئيسية) @endif
                                        @if(!$t->status) — (غير مفعلة) @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('treasury_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                اختر الخزنة التي ستعمل عليها في هذا الشفت.
                            </div>
                        </div>

                        {{-- Opening Balance --}}
                        <div class="mb-3">
                            <label class="form-label" for="opening_balance">الرصيد الافتتاحي</label>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   name="opening_balance"
                                   id="opening_balance"
                                   class="form-control @error('opening_balance') is-invalid @enderror"
                                   value="{{ old('opening_balance', 0) }}"
                                   placeholder="مثال: 1000.00">

                            @error('opening_balance')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <div class="form-text">
                                اكتب المبلغ الموجود فعليًا داخل الخزنة عند بداية الشفت (اختياري).
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('treasuries.index') }}" class="btn btn-light">إلغاء</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-play"></i>
                                فتح الشفت
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

@endsection

@section('js')
@endsection
