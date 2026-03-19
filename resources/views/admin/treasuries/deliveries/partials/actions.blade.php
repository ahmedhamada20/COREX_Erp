<div class="d-flex gap-1">
    <a href="{{ route('treasuries.deliveries.show', [$treasury->id, $delivery->id]) }}"
       class="btn btn-sm btn-outline-primary">
        عرض
    </a>

    <a href="{{ route('treasuries.deliveries.edit', [$treasury->id, $delivery->id]) }}"
       class="btn btn-sm btn-outline-primary">
        تعديل
    </a>

    <form action="{{ route('treasuries.deliveries.destroy', [$treasury->id, $delivery->id]) }}"
          method="POST" class="d-inline"
          onsubmit="return confirm('هل أنت متأكد من حذف الحركة؟');">
        @csrf
        @method('DELETE')
        <button class="btn btn-sm btn-outline-danger">
            حذف
        </button>
    </form>
</div>
