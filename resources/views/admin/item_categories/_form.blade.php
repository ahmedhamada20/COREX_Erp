<div class="row">

    <div class="col-md-6 mb-3">
        <label class="form-label">الاسم <span class="text-danger">*</span></label>
        <input type="text"
               name="name"
               value="{{ old('name', $item->name ?? '') }}"
               class="form-control @error('name') is-invalid @enderror"
               placeholder="اسم الفئة">
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">رقم الهاتف</label>
        <input type="text"
               name="phone"
               value="{{ old('phone', $item->phone ?? '') }}"
               class="form-control @error('phone') is-invalid @enderror"
               placeholder="010...">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">العنوان</label>
        <input type="text"
               name="address"
               value="{{ old('address', $item->address ?? '') }}"
               class="form-control @error('address') is-invalid @enderror"
               placeholder="العنوان">
        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">التاريخ</label>
        <input type="date"
               name="date"
               value="{{ old('date', isset($item) && $item->date ? \Carbon\Carbon::parse($item->date)->format('Y-m-d') : '') }}"
               class="form-control @error('date') is-invalid @enderror">
        @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label d-block">الحالة</label>
        <div class="form-check form-switch">
            <input class="form-check-input"
                   type="checkbox"
                   name="status"
                   value="1"
                {{ old('status', isset($item) ? (int)$item->status : 0) ? 'checked' : '' }}>
            <label class="form-check-label">نشط</label>
        </div>
    </div>

</div>
