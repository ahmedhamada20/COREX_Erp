<div class="d-flex align-items-center gap-1 justify-content-end">

    {{-- Show --}}
    <a href="{{ route('treasuries.show', $row->id) }}"
       class="btn btn-sm btn-light"
       title="عرض">
        <i class="ti ti-eye"></i>
    </a>

    {{-- Edit --}}
    <a href="{{ route('treasuries.edit', $row->id) }}"
       class="btn btn-sm btn-primary"
       title="تعديل">
        <i class="ti ti-pencil"></i>
    </a>

    {{-- Treasury Deliveries --}}
    <a href="{{ route('treasuries.deliveries.index', $row->id) }}"
       class="btn btn-sm btn-info"
       title="حركات الخزنة">
        <i class="fa fa-exchange-alt"></i>
    </a>

    @php
        $hasOpenShift = (int)($row->open_shift_count ?? 0) > 0;
    @endphp

    {{-- Shift: Open/Close --}}
    @if($hasOpenShift)
        <a href="{{ route('shifts.close.form') }}"
           class="btn btn-sm btn-warning"
           title="قفل الشفت">
            <i class="ti ti-lock"></i>
        </a>
    @else
        <a href="{{ route('shifts.create', ['treasury_id' => $row->id]) }}"
           class="btn btn-sm btn-dark"
           title="فتح شفت على هذه الخزنة">
            <i class="fa fa-play"></i>
        </a>
    @endif

    {{-- Add Delivery (ERP: ممنوع بدون شفت مفتوح) --}}
    @if($hasOpenShift)
        <a href="{{ route('treasuries.deliveries.create', $row->id) }}"
           class="btn btn-sm btn-success"
           title="إضافة حركة">
            <i class="fa fa-plus"></i>
        </a>
    @else
        <button type="button"
                class="btn btn-sm btn-success"
                title="افتح شفت أولاً لإضافة حركة"
                disabled>
            <i class="fa fa-plus"></i>
        </button>
    @endif

    {{-- Set as Master --}}
    @if(!(bool)$row->is_master)
        <form action="{{ route('treasuries.set-master', $row->id) }}" method="POST" class="d-inline">
            @csrf
            @method('PATCH')
            <button type="submit"
                    class="btn btn-sm btn-warning"
                    title="تعيين كرئيسية"
                    onclick="return confirm('هل تريد تعيين هذه الخزنة كخزنة رئيسية؟ سيتم إلغاء الرئيسية عن الخزنة الحالية.')">
                <i class="ti ti-star"></i>
            </button>
        </form>
    @endif

    {{-- Delete (ERP: لا تحذف لو رئيسية أو عليها شفت مفتوح) --}}
    <form action="{{ route('treasuries.destroy', $row->id) }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit"
                class="btn btn-sm btn-danger"
                title="حذف"
                @if($hasOpenShift || (bool)$row->is_master) disabled @endif
                onclick="return confirm('هل أنت متأكد من حذف الخزنة؟')">
            <i class="ti ti-trash"></i>
        </button>
    </form>

</div>
