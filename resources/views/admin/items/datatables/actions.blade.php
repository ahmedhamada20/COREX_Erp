<div class="btn-group btn-info" role="group">
    {{-- Show --}}
    <a href="{{ route('items.show', $row->id) }}"
       class="btn btn-sm btn-light"
       title="عرض">
        <i class="ti ti-eye"></i>
    </a>

    {{-- Edit --}}
    <a href="{{ route('items.edit', $row->id) }}"
       class="btn btn-sm btn-light"
       title="تعديل">
        <i class="ti ti-edit"></i>
    </a>

    {{-- Dropdown --}}
    <button type="button"
            class="btn btn-sm btn-light dropdown-toggle dropdown-toggle-split"
            data-bs-toggle="dropdown"
            aria-expanded="false">
        <span class="visually-hidden">Actions</span>
    </button>

    <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <a class="dropdown-item" href="{{ route('items.show', $row->id) }}">
                <i class="ti ti-eye me-1"></i> عرض
            </a>
        </li>

        <li>
            <a class="dropdown-item" href="{{ route('items.edit', $row->id) }}">
                <i class="ti ti-edit me-1"></i> تعديل
            </a>
        </li>

        <li><hr class="dropdown-divider"></li>

        <li>
            <form action="{{ route('items.destroy', $row->id) }}" method="POST"
                  onsubmit="return confirm('هل أنت متأكد من حذف هذا الصنف؟');">
                @csrf
                @method('DELETE')

                <button type="submit" class="dropdown-item text-danger">
                    <i class="ti ti-trash me-1"></i> حذف
                </button>
            </form>
        </li>
    </ul>
</div>
