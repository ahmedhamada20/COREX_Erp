{{-- Alerts --}}
@if(session('success'))
    <div id="successAlert"
         class="alert alert-success d-flex align-items-center justify-content-between"
         role="alert">
        <div>
            <i class="ti ti-check me-1"></i>
            {{ session('success') }}
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger d-flex align-items-center justify-content-between" role="alert">
        <div><i class="ti ti-alert-triangle me-1"></i>{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <div class="fw-bold mb-2">
            <i class="ti ti-alert-triangle me-1"></i>
            يوجد أخطاء في البيانات:
        </div>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
