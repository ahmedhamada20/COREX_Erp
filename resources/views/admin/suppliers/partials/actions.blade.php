{{-- resources/views/admin/suppliers/partials/actions.blade.php --}}

<div class="d-flex justify-content-end gap-2 flex-wrap">

    {{-- Show --}}
    <a href="{{ route('suppliers.show', $row->id) }}"
       class="btn btn-sm btn-light"
       title="عرض">
        <i class="ti ti-eye"></i>
    </a>

    {{-- Edit --}}
    <a href="{{ route('suppliers.edit', $row->id) }}"
       class="btn btn-sm btn-primary"
       title="تعديل">
        <i class="ti ti-edit"></i>
    </a>

    {{-- Delete --}}
    <form action="{{ route('suppliers.destroy', $row->id) }}"
          method="POST"
          class="d-inline"
          onsubmit="return confirm('متأكد من حذف المورد؟ سيتم حذف حسابه المالي أيضًا لو مرتبط.')">
        @csrf
        @method('DELETE')

        <button type="submit"
                class="btn btn-sm btn-danger"
                title="حذف">
            <i class="ti ti-trash"></i>
        </button>
    </form>

</div>
