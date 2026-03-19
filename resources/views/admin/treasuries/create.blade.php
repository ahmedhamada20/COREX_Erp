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
    إضافة خزنة جديدة
@endsection

@section('content')
    @php
        $statusChecked   = (bool) old('status', false);
        $isMasterChecked = (bool) old('is_master', false);

        // افتراضي: تاريخ اليوم بتوقيت مصر
        $defaultDate = old('date', now()->timezone('Africa/Cairo')->toDateString());

        // Meta (عرض فقط)
        $nowCairo = now()->timezone('Africa/Cairo');
        $period   = $nowCairo->format('H') < 12 ? 'صباحًا' : 'مساءً';
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
                                <i class="ti ti-safe me-1"></i>
                                إضافة خزنة جديدة
                            </h4>
                            <div class="form-hint">
                                سيتم إضافة خزنة جديدة لحساب:
                                <span class="fw-semibold">{{ auth()->user()->name }}</span>
                            </div>
                        </div>

                        <span class="badge bg-light text-primary fw-semibold">
                        <i class="ti ti-user me-1"></i>
                        {{ auth()->user()->name }}
                    </span>
                    </div>
                </div>

                {{-- Body --}}
                <div class="card-body">
                    <form id="treasuryForm" action="{{ route('treasuries.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">

                            {{-- Name --}}
                            <div class="col-md-6">
                                <label for="treasuryName" class="form-label fw-semibold">
                                    اسم الخزنة <span class="text-danger">*</span>
                                </label>
                                <input id="treasuryName"
                                       type="text"
                                       name="name"
                                       required
                                       maxlength="255"
                                       value="{{ old('name') }}"
                                       class="form-control @error('name') is-invalid @enderror"
                                       placeholder="مثال: الخزنة الرئيسية / خزنة الفرع">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="invalid-feedback" id="nameErr"></div>
                            </div>

                            {{-- Date --}}
                            <div class="col-md-6">
                                <label for="treasuryDate" class="form-label fw-semibold">التاريخ</label>
                                <input id="treasuryDate"
                                       type="date"
                                       name="date"
                                       value="{{ $defaultDate }}"
                                       class="form-control @error('date') is-invalid @enderror">
                                @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted d-block mt-1">
                                    تاريخ إنشاء/تفعيل الخزنة (اختياري).
                                </small>
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

                                <small class="text-muted d-block mt-1">
                                    عند الإلغاء لن تظهر الخزنة في اختيارات الصرف/التحصيل.
                                </small>
                            </div>

                            {{-- Last receipt numbers (readonly defaults) --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">آخر إيصال صرف</label>
                                <input type="text"
                                       class="form-control"
                                       value="0"
                                       readonly>
                                <small class="text-muted d-block mt-1">
                                    سيتم توليد الرقم تلقائيًا مع أول إيصال صرف.
                                </small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">آخر إيصال تحصيل</label>
                                <input type="text"
                                       class="form-control"
                                       value="0"
                                       readonly>
                                <small class="text-muted d-block mt-1">
                                    سيتم توليد الرقم تلقائيًا مع أول إيصال تحصيل.
                                </small>
                            </div>

                            {{-- Meta --}}
                            <div class="col-12">
                                <div class="p-3 rounded-3 bg-light border">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <div class="text-muted small">
                                            <i class="ti ti-user me-1"></i>
                                            سيتم الحفظ بواسطة:
                                            <span class="fw-semibold text-dark">{{ auth()->user()->name }}</span>
                                        </div>

                                        <div class="text-muted small">
                                            <i class="ti ti-clock me-1"></i>
                                            {{ $nowCairo->translatedFormat('d F Y - h:i') }} {{ $period }}
                                        </div>
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
                                        حفظ الخزنة
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
        // منع إدخال أكواد/رموز غير مرغوبة في اسم الخزنة (UX)
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

        if (treasuryName) {
            treasuryName.addEventListener('input', function () {
                const raw = this.value || '';
                const cleaned = raw.replace(blocked, '');

                if (raw !== cleaned) {
                    this.value = cleaned;
                    setInvalid(this, nameErr, 'غير مسموح بإدخال أكواد أو رموز مثل < > @ { }');
                    return;
                }

                const v = cleaned.trim();
                if (!v) return setInvalid(this, nameErr, 'اسم الخزنة مطلوب');
                if (v.length > 255) return setInvalid(this, nameErr, 'الاسم يجب ألا يزيد عن 255 حرفًا');
                return setValid(this, nameErr);
            });
        }

        // Toggle: status
        const statusSwitch = document.getElementById('statusSwitch');
        const statusLabel  = document.getElementById('statusLabel');
        const statusBadge  = document.getElementById('statusBadge');

        if (statusSwitch) {
            statusSwitch.addEventListener('change', function () {
                const active = this.checked;

                statusLabel.textContent = active ? 'مفعل' : 'غير مفعل';
                statusBadge.textContent = active ? 'مفعل' : 'غير مفعل';

                statusBadge.classList.toggle('bg-success', active);
                statusBadge.classList.toggle('bg-danger', !active);
            });
        }

        // Toggle: is_master
        const isMasterSwitch = document.getElementById('isMasterSwitch');
        const isMasterLabel  = document.getElementById('isMasterLabel');
        const isMasterBadge  = document.getElementById('isMasterBadge');

        if (isMasterSwitch) {
            isMasterSwitch.addEventListener('change', function () {
                const master = this.checked;

                isMasterLabel.textContent = master ? 'رئيسية' : 'فرعية';
                isMasterBadge.textContent = master ? 'رئيسية' : 'فرعية';

                isMasterBadge.classList.toggle('bg-primary', master);
                isMasterBadge.classList.toggle('bg-secondary', !master);
            });
        }


        document.getElementById('treasuryForm')?.addEventListener('submit', function (e) {
            const v = (treasuryName?.value || '').trim();
            if (!v) {
                e.preventDefault();
                setInvalid(treasuryName, nameErr, 'اسم الخزنة مطلوب');
                treasuryName?.scrollIntoView({behavior:'smooth', block:'center'});
            }
        });
    </script>
@endsection
