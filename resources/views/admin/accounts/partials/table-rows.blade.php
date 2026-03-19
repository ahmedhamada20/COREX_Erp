@forelse($items as $row)
    <tr data-id="{{ $row->id }}">
        <td class="text-start"></td> {{-- هيتظبط بالـ JS --}}
        <td class="text-start">
            <div class="fw-bold"><a href="{{ route('accounts.show', $row->id) }}">{{ $row->name }}</a> </div>
            <div class="text-muted" style="font-size:12px;">
                {{ $row->isRoot() ? 'رئيسي' : 'فرعي' }}
                @if($row->parent)
                    • الأب: {{ $row->parent->name }}  <br>
                     المسار: .{{ $row->path  }}
                @endif
            </div>
        </td>

{{--        <td class="text-start"><div class="path">{{ $row->path }}</div></td>--}}

        <td class="text-start">
            <span class="badge badge-soft-info">{{ $row->type?->name ?? '-' }}</span>
        </td>

        <td class="text-start">{{ $row->account_number ?? '-' }}</td>

        <td class="text-start">{{ number_format((float)($row->start_balance ?? 0), 2) }}</td>
        <td class="text-start">{{ number_format((float)($row->current_balance ?? 0), 2) }}</td>

        <td class="text-center">
            <label class="switch">
                <input type="checkbox"
                       class="toggle-status"
                       data-id="{{ $row->id }}"
                    {{ $row->status ? 'checked' : '' }}>
                <span class="slider round"></span>
            </label>
        </td>

        <td class="text-start">
            <div class="dropdown">
                <button class="btn btn-info btn-sm dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                    الإجراءات
                </button>

                <ul class="dropdown-menu dropdown-menu-end">

                    <li>
                        <a class="dropdown-item"
                           href="{{ route('accounts.show', $row->id) }}">
                            <i class="ti ti-eye me-1"></i>
                            عرض
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item"
                           href="{{ route('accounts.edit', $row->id) }}">
                            <i class="ti ti-edit me-1"></i>
                            تعديل
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    <li>
                        <form action="{{ route('accounts.destroy', $row->id) }}"
                              method="POST"
                              onsubmit="return confirm('تأكيد الحذف؟')">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                    class="dropdown-item text-danger">
                                <i class="ti ti-trash me-1"></i>
                                حذف
                            </button>
                        </form>
                    </li>

                </ul>
            </div>
        </td>

    </tr>
@empty
    {{-- لو أول تحميل وطلع فاضي --}}
@endforelse
