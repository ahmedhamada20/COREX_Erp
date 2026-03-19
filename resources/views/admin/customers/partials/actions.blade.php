<div class="d-flex gap-2 align-items-center">

    <a href="{{ route('customers.show', $row->id) }}"
       class="btn btn-sm btn-light"
       title="عرض">
        عرض
    </a>

    <a href="{{ route('customers.edit', $row->id) }}"
       class="btn btn-sm btn-primary"
       title="تعديل">
        تعديل
    </a>


    {{-- Delete --}}
    <form action="{{ route('customers.destroy', $row->id) }}" method="POST" class="d-inline"
          onsubmit="return confirm('متأكد من حذف العميل؟')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-danger" title="حذف">
            حذف
        </button>
    </form>

</div>
