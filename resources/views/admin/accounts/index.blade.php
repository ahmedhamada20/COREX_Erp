@extends('admin.layouts.master')

@section('title', 'الحسابات المالية')

@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        /* ===== KPIs ===== */
        .kpi {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px;
            background: #fff;
        }
        .kpi .label { font-size: 12px; color: #64748b; }
        .kpi .value { font-size: 20px; font-weight: 700; }

        .badge-soft-success { background: rgba(34, 197, 94, .12); color: #16a34a; }
        .badge-soft-danger { background: rgba(239, 68, 68, .12); color: #dc2626; }
        .badge-soft-info { background: rgba(59, 130, 246, .12); color: #2563eb; }

        .table td, .table th { vertical-align: middle; }
        .path { font-size: 12px; color: #64748b; }

        /* ===== Tree (clean + RTL) ===== */
        .tree { direction: rtl; }
        .tree ul { list-style: none; margin: 0; padding: 0; }
        .tree-item { margin: 8px 0; }

        .tree-row{
            display:flex;
            align-items:center;
            gap:10px;
            padding:10px 12px;
            border:1px solid #e2e8f0;
            border-radius:12px;
            background:#fff;
        }
        .tree-row:hover{ background:#f8fafc; }

        .tree-toggle{
            width:28px; height:28px;
            display:inline-flex; align-items:center; justify-content:center;
            border:1px solid #e2e8f0;
            background:#fff;
            border-radius:8px;
            cursor:pointer;
            flex: 0 0 auto;
        }
        .tree-toggle--empty{
            width:28px; height:28px;
            border-color: transparent;
            background: transparent;
            cursor: default;
            flex: 0 0 auto;
        }
        .toggle-icon{ font-weight:900; font-size:16px; line-height:1; color:#0f172a; }

        .tree-main{ flex:1; min-width:0; }
        .tree-title{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .tree-link{ font-weight:800; color:#0f172a; text-decoration:none; }
        .tree-link:hover{ text-decoration:underline; }
        .tree-meta{ font-size:12px; color:#64748b; margin-top:2px; display:flex; gap:10px; flex-wrap:wrap; }

        .tree-actions{ display:flex; gap:6px; flex:0 0 auto; }

        .tree-children{
            margin-top: 10px;
            padding-right: 44px;
            border-right: 4px solid #dbeafe;
            background: #f8fafc;
            border-radius: 12px;
            padding-top: 10px;
            padding-bottom: 10px;
        }
        .tree-children .tree-row{ background: #ffffff; }

        .tree-scroll{
            max-height: 560px;
            overflow: auto;
            padding-left: 4px;
            padding-right: 4px;
        }

        .tree-toolbar{
            display:flex;
            gap:8px;
            align-items:center;
            justify-content:space-between;
            margin-bottom: 10px;
        }
        .tree-toolbar .btn{ white-space: nowrap; }

        /* ===== Levels ===== */
        .tree-row{ transition: 0.2s ease; }
        .tree-row[data-level="1"]{
            background:#f1f5f9;
            border-color:#cbd5e1;
            font-weight:800;
        }
        .tree-row[data-level="2"]{ padding-right: 22px; border-right: 4px solid #93c5fd; }
        .tree-row[data-level="3"]{ padding-right: 42px; border-right: 4px solid #bfdbfe; }
        .tree-row[data-level="4"],
        .tree-row[data-level="5"],
        .tree-row[data-level="6"]{ padding-right: 60px; border-right: 4px solid #dbeafe; }

        .tree-path{ color:#64748b; font-size:12px; }
        .tree-path strong{ color:#0f172a; font-weight:800; }
    </style>
@endsection

@section('content')

    {{-- Header --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">الحسابات المالية</h4>

                <div class="d-flex gap-2">
                    <a href="{{ route('accounts.create') }}" class="btn btn-primary btn-sm">
                        <i class="ti ti-plus"></i> إضافة حساب
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-6 col-xl-3">
                    <div class="kpi">
                        <div class="label">إجمالي رصيد الافتتاحي</div>
                        <div class="value">{{ number_format((float)($summary->total_start ?? 0), 2) }}</div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="kpi">
                        <div class="label">إجمالي الرصيد الحالي</div>
                        <div class="value">{{ number_format((float)($summary->total_current ?? 0), 2) }}</div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="kpi">
                        <div class="label">عدد الحسابات</div>
                        <div class="value">{{ number_format($totalAccounts ?? 0) }}</div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="kpi">
                        <div class="label">الحسابات النشطة</div>
                        <div class="value">{{ number_format($activeAccounts ?? 0) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" id="accountsFilterForm" class="row g-2 align-items-end">

                <div class="col-md-3">
                    <label class="form-label">بحث</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control form-control-sm" placeholder="اسم الحساب / رقم الحساب">
                </div>

                <div class="col-md-2">
                    <label class="form-label">نوع الحساب</label>
                    <select name="account_type_id" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        @foreach($types as $t)
                            <option value="{{ $t->id }}" @selected(request('account_type_id') == $t->id)>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        <option value="1" @selected(request('status') === '1')>نشط</option>
                        <option value="0" @selected(request('status') === '0')>غير نشط</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">الجذور</label>
                    <select name="is_root" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        <option value="1" @selected(request('is_root') === '1')>حسابات رئيسية فقط</option>
                        <option value="0" @selected(request('is_root') === '0')>حسابات فرعية فقط</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">التاريخ</label>
                    <div class="d-flex gap-2">
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                    </div>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary btn-sm">
                        <i class="ti ti-search"></i> تصفية
                    </button>

                    <a href="{{ route('accounts.index') }}" class="btn btn-light btn-sm">
                        <i class="ti ti-refresh"></i> إعادة ضبط
                    </a>
                </div>

            </form>
        </div>
    </div>

    <div class="row g-3">
        {{-- Tree --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">شجرة الحسابات</h5>
                        <span class="text-muted" style="font-size:12px;">عرض سريع</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="tree-toolbar">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-light btn-sm" id="expandAllBtn">فتح الكل</button>
                            <button type="button" class="btn btn-light btn-sm" id="collapseAllBtn">قفل الكل</button>
                        </div>
                        <span class="text-muted" style="font-size:12px;">اضغط + لعرض الفروع</span>
                    </div>

                    @if($roots->count())
                        <div class="tree tree-scroll" id="accountsTree">
                            <ul>
                                @foreach($roots as $root)
                                    @include('admin.accounts.partials.tree-node', ['node' => $root])
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="mb-2 text-muted">لا يوجد حسابات بعد</div>
                            <a href="{{ route('accounts.create') }}" class="btn btn-primary btn-sm">إضافة أول حساب</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="col-lg-8">
            <div class="card table-card">
                <div class="card-header">
                    <h5 class="mb-0">قائمة الحسابات</h5>
                </div>

                <div class="card-body" id="accountsTableWrapper">
                    @include('admin.accounts.partials.table', ['items' => $items])
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        @if(session('success')) toastr.success(@json(session('success'))); @endif
        @if(session('error')) toastr.error(@json(session('error'))); @endif
    </script>




    {{-- AJAX Table (Real-time) --}}


    <script>
        const ajaxUrl   = @json(route('accounts.ajax'));
        const form      = document.getElementById('accountsFilterForm');

        const tbody     = document.getElementById('accountsTbody');
        const loader    = document.getElementById('accountsLoader');
        const endEl     = document.getElementById('accountsEnd');
        const sentinel  = document.getElementById('accountsSentinel');

        let debounceTimer = null;
        let controller = null;

        let cursor = null;
        let loading = false;
        let hasMore = true;

        function showLoader(v){ if(loader) loader.style.display = v ? 'block' : 'none'; }
        function showEnd(v){ if(endEl) endEl.style.display = v ? 'block' : 'none'; }

        function buildQuery() {
            const fd = new FormData(form);
            const params = new URLSearchParams();
            for (const [k, v] of fd.entries()) {
                const val = (v ?? '').toString().trim();
                if (val !== '') params.set(k, val);
            }
            return params.toString();
        }

        function updateUrl() {
            const qs = buildQuery();
            const cleanUrl = qs ? `${location.pathname}?${qs}` : location.pathname;
            window.history.replaceState({}, '', cleanUrl);
        }

        function reindexRows() {
            const rows = tbody.querySelectorAll('tr');
            rows.forEach((tr, idx) => {
                const cell = tr.querySelector('td');
                if (cell) cell.textContent = (idx + 1).toString();
            });
        }

        async function loadMore({reset=false} = {}) {
            if (loading) return;
            if (!hasMore && !reset) return;

            loading = true;
            showLoader(true);
            showEnd(false);

            if (controller) controller.abort();
            controller = new AbortController();

            try {
                const qs = buildQuery();
                const params = new URLSearchParams(qs);

                params.set('limit', '50');
                if (!reset && cursor) params.set('cursor', cursor);

                const url = params.toString() ? `${ajaxUrl}?${params.toString()}` : ajaxUrl;

                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    signal: controller.signal
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) throw new Error('failed');

                if (reset) {
                    tbody.innerHTML = data.html || '';
                } else {
                    tbody.insertAdjacentHTML('beforeend', data.html || '');
                }

                cursor  = data.next_cursor ?? null;
                hasMore = !!data.has_more;

                reindexRows();
                updateUrl();

                if (!hasMore) showEnd(true);

            } catch (e) {
                if (e.name !== 'AbortError') toastr.error('حدث خطأ أثناء تحميل البيانات');
            } finally {
                loading = false;
                showLoader(false);
            }
        }

        function resetAndLoad() {
            cursor = null;
            hasMore = true;
            showEnd(false);
            loadMore({reset:true});
        }

        // ✅ IntersectionObserver
        const io = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) loadMore({reset:false});
            });
        }, { root: null, threshold: 0.1 });

        if (sentinel) io.observe(sentinel);

        // ✅ submit = reset
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            resetAndLoad();
        });

        const searchInput = form.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    resetAndLoad();
                }, 300);
            });
        }

        // ✅ other filters
        form.querySelectorAll('select, input[type="date"]').forEach(el => {
            el.addEventListener('change', resetAndLoad);
        });

        // ✅ ترقيم أولي
        reindexRows();
    </script>

    <script>
        document.addEventListener('change', async function (e) {
            const checkbox = e.target.closest('.toggle-status');
            if (!checkbox) return;

            const id = checkbox.dataset.id;
            const originalState = !checkbox.checked;

            try {
                const res = await fetch(`/accounts/${id}/toggle-status`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok || !data.success) {
                    checkbox.checked = originalState;
                    toastr.error(data.message || 'فشل تحديث الحالة');
                    return;
                }

                toastr.success(data.message || (data.status ? 'تم التفعيل' : 'تم الإيقاف'));

            } catch (err) {
                checkbox.checked = originalState;
                toastr.error('حدث خطأ أثناء تغيير الحالة');
            }
        });
    </script>

    {{-- Tree Expand/Collapse --}}
    <script>
        function setNodeOpen(li, open) {
            const children = li.querySelector(':scope > .tree-children');
            const btn = li.querySelector(':scope > .tree-row .tree-toggle');
            if (!children || !btn) return;

            children.style.display = open ? 'block' : 'none';
            const icon = btn.querySelector('.toggle-icon');
            if (icon) icon.textContent = open ? '−' : '+';
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        document.addEventListener('click', function(e){
            const btn = e.target.closest('.tree-toggle');
            if(!btn) return;

            const li  = btn.closest('.tree-item');
            const children = li.querySelector(':scope > .tree-children');
            if(!children) return;

            const isOpen = children.style.display !== 'none';
            setNodeOpen(li, !isOpen);
        });

        const tree = document.getElementById('accountsTree');
        const expandAllBtn = document.getElementById('expandAllBtn');
        const collapseAllBtn = document.getElementById('collapseAllBtn');

        if (expandAllBtn && tree) {
            expandAllBtn.addEventListener('click', function(){
                tree.querySelectorAll('.tree-item').forEach(li => setNodeOpen(li, true));
            });
        }

        if (collapseAllBtn && tree) {
            collapseAllBtn.addEventListener('click', function(){
                tree.querySelectorAll('.tree-item').forEach(li => setNodeOpen(li, false));
            });
        }
    </script>
@endsection
