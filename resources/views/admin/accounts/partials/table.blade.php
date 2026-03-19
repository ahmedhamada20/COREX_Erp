@if($items->count())
    <div class="table-responsive">
        <table class="table table-hover align-middle" id="accountsTable">
            <thead>
            <tr>
                <th class="text-start">#</th>
                <th class="text-start">الاسم</th>
{{--                <th class="text-start">المسار</th>--}}
                <th class="text-start">النوع</th>
                <th class="text-start">رقم الحساب</th>
                <th class="text-start">افتتاحي</th>
                <th class="text-start">حالي</th>
                <th class="text-start">الحالة</th>
                <th class="text-start">إجراءات</th>
            </tr>
            </thead>

            <tbody id="accountsTbody">
            @include('admin.accounts.partials.table-rows', ['items' => $items])
            </tbody>
        </table>
    </div>

    <div class="py-3 text-center text-muted" id="accountsLoader" style="display:none;">
        جاري تحميل المزيد...
    </div>

    <div class="py-3 text-center text-muted" id="accountsEnd" style="display:none;">
        انتهت النتائج ✅
    </div>

    {{-- Sentinel for IntersectionObserver --}}
    <div id="accountsSentinel" style="height: 1px;"></div>

@else
    <div class="text-center py-5">
        <div class="mb-2 text-muted">لا توجد نتائج مطابقة للبحث.</div>
        <a href="{{ route('accounts.index') }}" class="btn btn-light btn-sm">عرض الكل</a>
    </div>
@endif
