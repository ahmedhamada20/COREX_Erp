@extends('admin.layouts.master')

@section('css')
    <style>
        .setting-card-header{
            background: linear-gradient(135deg, rgba(13,110,253,.95), rgba(13,110,253,.75));
            color:#fff;
        }
        .form-hint{
            font-size:.85rem;
            color:rgba(255,255,255,.85);
        }
    </style>
@endsection

@section('title')
    تعديل خزنة
@endsection

@section('content')
    @php
        $statusChecked   = (bool) old('status', (bool)$treasury->status);
        $isMasterChecked = (bool) old('is_master', (bool)$treasury->is_master);

        $defaultDate = old('date', optional($treasury->date)->format('Y-m-d') ?? now()->timezone('Africa/Cairo')->toDateString());

        $updatedAt = $treasury->updated_at?->copy()->timezone('Africa/Cairo');
        $period = $updatedAt ? ($updatedAt->format('H') < 12 ? 'صباحًا' : 'مساءً') : null;
    @endphp

    <div class="row">
        <div class="col-12">

            @include('admin.Alerts')

            <div class="card border-0 shadow-sm">

                {{-- Header --}}
                <div class="card-body setting-card-header rounded-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h4 class="mb-1 fw-bold">
                                <i class="ti ti-pencil me-1"></i>
                                تعديل خزنة
                            </h4>
                            <div class="form-hint">
                                تخص هذه الخزنة حساب:
                                <span class="fw-semibold">{{ auth()->user()->name }}</span>
                            </div>

                            @if(!empty($treasury->updated_by))
                                <div class="mt-2">
                                <span class="badge bg-light text-primary fw-semibold">
                                    <i class="ti ti-user-check me-1"></i>
                                    آخر تعديل بواسطة: {{ $treasury->updated_by }}
                                </span>
                                </div>
                            @endif
                        </div>

                        <span class="badge bg-light text-primary fw-semibold">
                        <i class="ti ti-safe me-1"></i>
                        {{ $treasury->name }}
                    </span>
                    </div>
                </div>

                {{-- Body --}}
                <div class="card-body">
                    <form id="treasuryForm" action="{{ route('treasuries.update', $treasury->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">

                            {{-- Name --}}
                            <div class="col-md-6">
                                <label for="treasuryName" class="form-label fw-semibold">
                                    اسم الخزنة <span class="text-danger">*</span>
                                </label>
                                <input id="treasuryName" type="text" name="name" required maxlength="255"
                                       value="{{ old('name', $treasury->name) }}"
                                       class="form-control @error('name') is-invalid @enderror"
                                       placeholder="مثال: الخزنة الرئيسية / خزنة الفرع">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="invalid-feedback" id="nameErr"></div>
                            </div>

                            {{-- Date --}}
                            <div class="col-md-6">
                                <label for="treasuryDate" class="form-label fw-semibold">التاريخ</label>
                                <input id="treasuryDate" type="date" name="date"
                                       value="{{ $defaultDate }}"
                                       class="form-control @error('date') is-invalid @enderror">
                                @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Is Master --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">نوع الخزنة</label>

                                <div class="d-flex align-items-center gap-3 mt-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               role="switch"
                                               id="isMasterSwitch"
                                               name="is_master"
                                               value="1"
                                            {{ $isMasterChecked ? 'checked' : '' }}>
                                        <label class="form-check-label" for="isMasterSwitch" id="isMasterLabel">
                                            {{ $isMasterChecked ? 'رئيسية' : 'فرعية' }}
                                        </label>
                                    </div>

                                    <span id="isMasterBadge"
                                          class="badge {{ $isMasterChecked ? 'bg-primary' : 'bg-secondary' }}">
                                    {{ $isMasterChecked ? 'رئيسية' : 'فرعية' }}
                                </span>
                                </div>

                                <small class="text-muted d-block mt-1">
                                    اجعلها “رئيسية” لو دي الخزنة الأساسية للنظام.
                                </small>
                            </div>

                            {{-- Status --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">حالة الخزنة</label>

                                <div class="d-flex align-items-center gap-3 mt-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               role="switch"
                                               id="statusSwitch"
                                               name="status"
                                               value="1"
                                            {{ $statusChecked ? 'checked' : '' }}>
                                        <label class="form-check-label" for="statusSwitch" id="statusLabel">
                                            {{ $statusChecked ? 'مفعل' : 'غير مفعل' }}
                                        </label>
                                    </div>

                                    <span id="statusBadge"
                                          class="badge {{ $statusChecked ? 'bg-success' : 'bg-danger' }}">
                                    {{ $statusChecked ? 'مفعل' : 'غير مفعل' }}
                                </span>
                                </div>
                            </div>

                            {{-- Receipts (readonly) --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">آخر إيصال صرف</label>
                                <input type="text" class="form-control" value="{{ $treasury->last_payment_receipt_no }}" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">آخر إيصال تحصيل</label>
                                <input type="text" class="form-control" value="{{ $treasury->last_collection_receipt_no }}" readonly>
                            </div>

                            {{-- Linked Account (readonly) --}}
                            <div class="col-12">
                                @if($treasury->account_id && $treasury->account)
                                    <div class="alert alert-info mb-0">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div>
                                                <strong>الحساب المحاسبي المرتبط بالخزنة:</strong>
                                                <span class="ms-1">{{ $treasury->account->name }}</span>
                                                @if($treasury->account->account_number)
                                                    <span class="text-muted">({{ $treasury->account->account_number }})</span>
                                                @endif
                                            </div>
                                            <span class="badge bg-primary">مرتبط تلقائيًا</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-warning mb-0">
                                        هذه الخزنة غير مربوطة بحساب محاسبي.
                                        <div class="mt-1 small text-muted">
                                            سيتم إنشاء الحساب وربطه تلقائيًا بمجرد حفظ التعديلات.
                                        </div>
                                    </div>
                                @endif
                            </div>


                            {{-- Meta --}}
                            <div class="col-12">
                                <div class="p-3 rounded-3 bg-light border">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <div class="text-muted small">
                                            <i class="ti ti-clock me-1"></i>
                                            آخر تحديث:
                                            <span class="fw-semibold text-dark">
                                            {{ $updatedAt ? $updatedAt->translatedFormat('d F Y - h:i') . ' ' . $period : '—' }}
                                        </span>
                                        </div>

                                        @if(!empty($treasury->updated_by) && $updatedAt)
                                            <div class="text-muted small d-flex align-items-center gap-1">
                                                <i class="ti ti-user-check me-1"></i>
                                                تم التعديل بواسطة:
                                                <span class="fw-semibold text-dark">{{ $treasury->updated_by }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-end gap-2 mt-2">
                                    <a href="{{ route('treasuries.index') }}" class="btn btn-light">
                                        <i class="ti ti-arrow-back me-1"></i>
                                        رجوع
                                    </a>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-device-floppy me-1"></i>
                                        حفظ التعديلات
                                    </button>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>

            </div>

        </div>
    </div>
@endsection

@section('js')
    <script>
        // منع إدخال أكواد/رموز غير مرغوبة في الاسم (UX)
        const treasuryName = document.getElementById('treasuryName');
        const nameErr = document.getElementById('nameErr');
        const blocked = /[<>@{}]/g;

        function setInvalid(input, errEl, msg) {
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
            if (errEl) errEl.textContent = msg || '';
        }
        function setValid(input, errEl) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            if (errEl) errEl.textContent = '';
        }

        treasuryName?.addEventListener('input', function () {
            const raw = this.value || '';
            const cleaned = raw.replace(blocked, '');

            if (raw !== cleaned) {
                this.value = cleaned;
                return setInvalid(this, nameErr, 'غير مسموح بإدخال أكواد أو رموز مثل < > @ { }');
            }

            const v = cleaned.trim();
            if (!v) return setInvalid(this, nameErr, 'اسم الخزنة مطلوب');
            if (v.length > 255) return setInvalid(this, nameErr, 'الاسم يجب ألا يزيد عن 255 حرفًا');
            return setValid(this, nameErr);
        });

        // Toggle: status
        const statusSwitch = document.getElementById('statusSwitch');
        const statusLabel  = document.getElementById('statusLabel');
        const statusBadge  = document.getElementById('statusBadge');

        statusSwitch?.addEventListener('change', function () {
            const active = this.checked;
            statusLabel.textContent = active ? 'مفعل' : 'غير مفعل';
            statusBadge.textContent = active ? 'مفعل' : 'غير مفعل';
            statusBadge.classList.toggle('bg-success', active);
            statusBadge.classList.toggle('bg-danger', !active);
        });

        // Toggle: is_master
        const isMasterSwitch = document.getElementById('isMasterSwitch');
        const isMasterLabel  = document.getElementById('isMasterLabel');
        const isMasterBadge  = document.getElementById('isMasterBadge');

        isMasterSwitch?.addEventListener('change', function () {
            const master = this.checked;
            isMasterLabel.textContent = master ? 'رئيسية' : 'فرعية';
            isMasterBadge.textContent = master ? 'رئيسية' : 'فرعية';
            isMasterBadge.classList.toggle('bg-primary', master);
            isMasterBadge.classList.toggle('bg-secondary', !master);
        });
    </script>
@endsection
