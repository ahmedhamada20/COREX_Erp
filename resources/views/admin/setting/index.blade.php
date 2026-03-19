@extends('admin.layouts.master')

@section('css')
    <style>
        .setting-card-header{
            background: linear-gradient(135deg, rgba(13,110,253,.95), rgba(13,110,253,.75));
            color:#fff;
        }
        .preview-img{
            width:54px;height:54px;
            object-fit:cover;
            border-radius:12px;
            border:1px solid rgba(255,255,255,.35);
            background:rgba(255,255,255,.15);
            padding:4px;
        }
        .form-hint{
            font-size:.85rem;
            color:rgba(255,255,255,.85);
        }
        .preview-box{
            display:flex;
            align-items:center;
            gap:.6rem;
            margin-top:.5rem;
        }
        .file-meta{ line-height: 1.2; }
    </style>
@endsection

@section('title')
    إعدادات النظام
@endsection

@section('content')
    @php
        use Illuminate\Support\Facades\Storage;

        $statusChecked = (bool) old('status', (bool)($data->status ?? false));

        $logoUrl    = !empty($data->logo) ? Storage::url($data->logo) : null;
        $faviconUrl = !empty($data->favicon) ? Storage::url($data->favicon) : null;

        // prevent empty src="" (better UX + no broken requests)
        $hasLogo    = !empty($logoUrl);
        $hasFavicon = !empty($faviconUrl);
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
                                <i class="ti ti-settings me-1"></i>
                                إعدادات النظام
                            </h4>

                            <div class="form-hint">
                                تخص هذه الإعدادات حساب:
                                <span class="fw-semibold">{{ auth()->user()->name }}</span>
                            </div>

                            @if(!empty($data->updated_by))
                                <div class="mt-2">
                                    <span class="badge bg-light text-primary fw-semibold">
                                        <i class="ti ti-pencil me-1"></i>
                                        آخر تعديل بواسطة: {{ $data->updated_by }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Header previews --}}
                        <div class="d-flex align-items-center gap-2">
                            <img id="headerLogoPreview" class="preview-img"
                                 src="{{ $hasLogo ? $logoUrl : '' }}"
                                 style="{{ $hasLogo ? '' : 'display:none' }}"
                                 alt="Logo">

                            <div id="headerLogoPlaceholder"
                                 class="preview-img d-flex align-items-center justify-content-center"
                                 style="{{ $hasLogo ? 'display:none' : '' }}">
                                <i class="ti ti-photo fs-3 text-white"></i>
                            </div>

                            <img id="headerFaviconPreview" class="preview-img"
                                 src="{{ $hasFavicon ? $faviconUrl : '' }}"
                                 style="{{ $hasFavicon ? '' : 'display:none' }}"
                                 alt="Favicon">

                            <div id="headerFaviconPlaceholder"
                                 class="preview-img d-flex align-items-center justify-content-center"
                                 style="{{ $hasFavicon ? 'display:none' : '' }}">
                                <i class="ti ti-brand-chrome fs-3 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Body --}}
                <div class="card-body">
                    <form id="settingsForm" action="{{ route('setting.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        {{-- ❌ لا تبعت updated_by من الفرونت (أمان) --}}

                        <div class="row g-3">

                            {{-- Name --}}
                            <div class="col-md-6">
                                <label for="settingName" class="form-label fw-semibold">
                                    اسم النظام/الشركة <span class="text-danger">*</span>
                                </label>
                                <input id="settingName" type="text" required name="name"
                                       value="{{ old('name', $data->name ?? '') }}"
                                       class="form-control @error('name') is-invalid @enderror"
                                       placeholder="مثال: COREX ERP">
                                <div class="invalid-feedback" id="nameErr"></div>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Status --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">حالة النظام</label>

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
                                          class="badge {{ $statusChecked ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $statusChecked ? 'مفعل' : 'غير مفعل' }}
                                    </span>
                                </div>

                                <small class="text-muted d-block mt-1">
                                    عند الإلغاء، يمكنك إخفاء بعض أجزاء النظام حسب احتياجك.
                                </small>
                            </div>

                            {{-- Phone --}}
                            <div class="col-md-6">
                                <label for="settingPhone" class="form-label fw-semibold">رقم الهاتف</label>
                                <input id="settingPhone" type="text" name="phone"
                                       value="{{ old('phone', $data->phone ?? '') }}"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       placeholder="مثال: 010xxxxxxxx">
                                <div class="invalid-feedback" id="phoneErr"></div>
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Address --}}
                            <div class="col-md-6">
                                <label for="settingAddress" class="form-label fw-semibold">العنوان</label>
                                <input id="settingAddress" type="text" name="address"
                                       value="{{ old('address', $data->address ?? '') }}"
                                       class="form-control @error('address') is-invalid @enderror"
                                       placeholder="مثال: القاهرة - مصر">
                                <div class="invalid-feedback" id="addressErr"></div>
                                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- General Alert --}}
                            <div class="col-12">
                                <label for="generalAlert" class="form-label fw-semibold">تنبيه عام</label>
                                <input id="generalAlert" type="text" name="general_alert"
                                       value="{{ old('general_alert', $data->general_alert ?? '') }}"
                                       class="form-control @error('general_alert') is-invalid @enderror"
                                       placeholder="مثال: سيتم إجراء صيانة مساء الجمعة">
                                <div class="invalid-feedback" id="alertErr"></div>
                                @error('general_alert') <div class="invalid-feedback">{{ $message }}</div> @enderror

                                <small class="text-muted d-block mt-1">
                                    يظهر في لوحة التحكم أو أعلى الصفحات حسب تصميمك.
                                </small>
                            </div>

                            {{-- Logo --}}
                            <div class="col-md-6">
                                <label for="logoInput" class="form-label fw-semibold">اللوجو (Logo)</label>
                                <input id="logoInput" type="file" name="logo" accept="image/*"
                                       class="form-control @error('logo') is-invalid @enderror">
                                <div class="invalid-feedback" id="logoErr"></div>
                                @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror

                                <div class="preview-box">
                                    <img id="logoPreview" class="preview-img"
                                         src="{{ $hasLogo ? $logoUrl : '' }}"
                                         style="{{ $hasLogo ? '' : 'display:none' }}"
                                         alt="Logo Preview">

                                    <div class="small text-muted file-meta">
                                        <div class="fw-semibold">معاينة اللوجو</div>
                                        <div id="logoFileName" class="text-muted">
                                            {{ !empty($data->logo) ? basename($data->logo) : 'لم يتم اختيار ملف بعد' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Favicon --}}
                            <div class="col-md-6">
                                <label for="faviconInput" class="form-label fw-semibold">الأيقونة (Favicon)</label>
                                <input id="faviconInput" type="file" name="favicon" accept="image/*"
                                       class="form-control @error('favicon') is-invalid @enderror">
                                <div class="invalid-feedback" id="faviconErr"></div>
                                @error('favicon') <div class="invalid-feedback">{{ $message }}</div> @enderror

                                <div class="preview-box">
                                    <img id="faviconPreview" class="preview-img"
                                         src="{{ $hasFavicon ? $faviconUrl : '' }}"
                                         style="{{ $hasFavicon ? '' : 'display:none' }}"
                                         alt="Favicon Preview">

                                    <div class="small text-muted file-meta">
                                        <div class="fw-semibold">معاينة الأيقونة</div>
                                        <div id="faviconFileName" class="text-muted">
                                            {{ !empty($data->favicon) ? basename($data->favicon) : 'لم يتم اختيار ملف بعد' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Meta --}}
                            <div class="col-12">
                                <div class="p-3 rounded-3 bg-light border">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <div class="text-muted small">
                                            <i class="ti ti-clock me-1"></i>
                                            آخر تحديث:
                                        </div>

                                        @if(!empty($data->updated_by) && !empty($data->updated_at))
                                            @php
                                                $updatedAt = $data->updated_at->copy()->timezone('Africa/Cairo');
                                                $period = $updatedAt->format('H') < 12 ? 'صباحًا' : 'مساءً';
                                                $formattedDate = $updatedAt->translatedFormat('d F Y - h:i');
                                            @endphp

                                            <div class="text-muted small d-flex align-items-center gap-1">
                                                <i class="ti ti-user-check me-1"></i>
                                                تم التعديل بواسطة:
                                                <span class="fw-semibold text-dark">{{ $data->updated_by }}</span>
                                                <span class="mx-1">•</span>
                                                <span class="fw-semibold text-dark">
                                                    {{ $formattedDate }} {{ $period }}
                                                </span>
                                            </div>
                                        @else
                                            <div class="text-muted small">
                                                <span class="fw-semibold">—</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-end gap-2 mt-2">
                                    <a href="{{ route('admin') }}" class="btn btn-light">
                                        <i class="ti ti-arrow-back me-1"></i>
                                        رجوع
                                    </a>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-device-floppy me-1"></i>
                                        حفظ الإعدادات
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
        // ===== Helpers =====
        const blocked = /[<>@{}]/g;

        function sanitizeInput(el){
            if (!el) return;
            el.value = (el.value || '').replace(blocked, '');
        }

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
        function clearValidation(input, errEl) {
            input.classList.remove('is-invalid', 'is-valid');
            if (errEl) errEl.textContent = '';
        }

        // ===== Preview (form + header) =====
        function setPreview(inputEl, previewImgId, fileNameId, headerImgId, headerPlaceholderId) {
            const file = inputEl.files && inputEl.files[0] ? inputEl.files[0] : null;
            if (!file) return;

            const previewImg = document.getElementById(previewImgId);
            const headerImg = document.getElementById(headerImgId);
            const fileNameEl = document.getElementById(fileNameId);
            const headerPlaceholder = document.getElementById(headerPlaceholderId);

            if (fileNameEl) fileNameEl.textContent = file.name;

            const reader = new FileReader();
            reader.onload = function (e) {
                if (previewImg) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'inline-block';
                }
                if (headerImg) {
                    headerImg.src = e.target.result;
                    headerImg.style.display = 'inline-block';
                }
                if (headerPlaceholder) {
                    headerPlaceholder.style.display = 'none';
                }
            };
            reader.readAsDataURL(file);
        }

        // ===== Validation =====
        const form = document.getElementById('settingsForm');

        const elName    = document.getElementById('settingName');
        const elPhone   = document.getElementById('settingPhone');
        const elAddress = document.getElementById('settingAddress');
        const elAlert   = document.getElementById('generalAlert');
        const elLogo    = document.getElementById('logoInput');
        const elFavicon = document.getElementById('faviconInput');

        const nameErr    = document.getElementById('nameErr');
        const phoneErr   = document.getElementById('phoneErr');
        const addressErr = document.getElementById('addressErr');
        const alertErr   = document.getElementById('alertErr');
        const logoErr    = document.getElementById('logoErr');
        const faviconErr = document.getElementById('faviconErr');

        function validateName() {
            sanitizeInput(elName);
            const v = (elName.value || '').trim();
            if (!v) return setInvalid(elName, nameErr, 'اسم النظام مطلوب');
            if (v.length > 255) return setInvalid(elName, nameErr, 'اسم النظام يجب ألا يزيد عن 255 حرفًا');
            return setValid(elName, nameErr);
        }

        function validatePhone() {
            sanitizeInput(elPhone);
            const v = (elPhone.value || '').trim();
            if (!v) return clearValidation(elPhone, phoneErr);
            if (v.length > 50) return setInvalid(elPhone, phoneErr, 'رقم الهاتف يجب ألا يزيد عن 50 حرفًا');
            if (!/^[0-9+\s()-]+$/.test(v)) return setInvalid(elPhone, phoneErr, 'رقم الهاتف غير صحيح');
            return setValid(elPhone, phoneErr);
        }

        function validateAddress() {
            sanitizeInput(elAddress);
            const v = (elAddress.value || '').trim();
            if (!v) return clearValidation(elAddress, addressErr);
            if (v.length > 255) return setInvalid(elAddress, addressErr, 'العنوان يجب ألا يزيد عن 255 حرفًا');
            return setValid(elAddress, addressErr);
        }

        function validateAlert() {
            sanitizeInput(elAlert);
            const v = (elAlert.value || '').trim();
            if (!v) return clearValidation(elAlert, alertErr);
            if (v.length > 255) return setInvalid(elAlert, alertErr, 'التنبيه العام يجب ألا يزيد عن 255 حرفًا');
            return setValid(elAlert, alertErr);
        }

        function validateFile(inputEl, errEl, allowedExt, maxMB) {
            const file = inputEl.files && inputEl.files[0] ? inputEl.files[0] : null;
            if (!file) return clearValidation(inputEl, errEl);

            const ext = (file.name.split('.').pop() || '').toLowerCase();
            const sizeMB = file.size / (1024 * 1024);

            if (!allowedExt.includes(ext)) {
                return setInvalid(inputEl, errEl, `الملف يجب أن يكون بصيغة: ${allowedExt.join(', ')}`);
            }
            if (sizeMB > maxMB) {
                return setInvalid(inputEl, errEl, `حجم الملف يجب ألا يزيد عن ${maxMB}MB`);
            }
            return setValid(inputEl, errEl);
        }

        function validateLogo()    { return validateFile(elLogo, logoErr, ['png','jpg','jpeg','webp'], 2); }
        function validateFavicon() { return validateFile(elFavicon, faviconErr, ['png','jpg','jpeg','webp','ico'], 1); }

        // ===== Status live UI =====
        const statusSwitch = document.getElementById('statusSwitch');
        const statusLabel  = document.getElementById('statusLabel');
        const statusBadge  = document.getElementById('statusBadge');

        if (statusSwitch) {
            statusSwitch.addEventListener('change', function () {
                const active = this.checked;
                statusLabel.textContent = active ? 'مفعل' : 'غير مفعل';
                statusBadge.textContent = active ? 'مفعل' : 'غير مفعل';
                statusBadge.classList.toggle('bg-success', active);
                statusBadge.classList.toggle('bg-secondary', !active);
            });
        }

        // ===== Events =====
        [elName, elPhone, elAddress, elAlert].forEach(el => {
            if (!el) return;
            el.addEventListener('input', () => sanitizeInput(el));
        });

        elName?.addEventListener('input', validateName);
        elPhone?.addEventListener('input', validatePhone);
        elAddress?.addEventListener('input', validateAddress);
        elAlert?.addEventListener('input', validateAlert);

        elLogo?.addEventListener('change', function () {
            setPreview(this, 'logoPreview', 'logoFileName', 'headerLogoPreview', 'headerLogoPlaceholder');
            validateLogo();
        });

        elFavicon?.addEventListener('change', function () {
            setPreview(this, 'faviconPreview', 'faviconFileName', 'headerFaviconPreview', 'headerFaviconPlaceholder');
            validateFavicon();
        });

        form?.addEventListener('submit', function (e) {
            validateName();
            validatePhone();
            validateAddress();
            validateAlert();
            validateLogo();
            validateFavicon();

            const invalid = this.querySelector('.is-invalid');
            if (invalid) {
                e.preventDefault();
                invalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        // initial
        if (elName) validateName();
    </script>
@endsection
