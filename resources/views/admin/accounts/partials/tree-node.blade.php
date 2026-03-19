@php
    $level = $level ?? 1;
    $hasChildren = $node->children && $node->children->count();
    $isRoot = is_null($node->parent_account_id);
@endphp

<li class="tree-item" data-id="{{ $node->id }}">

    <div class="tree-row" data-level="{{ $level }}">
        {{-- Toggle --}}
        @if($hasChildren)
            <button type="button" class="tree-toggle" aria-expanded="false" title="فتح/إغلاق">
                <span class="toggle-icon">+</span>
            </button>
        @else
            <span class="tree-toggle--empty"></span>
        @endif

        {{-- Main --}}
        <div class="tree-main">
            <div class="tree-title">
                <a href="{{ route('accounts.show', $node->id) }}" class="tree-link">
                    {{ $node->name }}
                </a>

                {{-- Root/Child --}}
                @if($isRoot)
                    <span class="badge badge-soft-info">رئيسي</span>
                @else
                    <span class="badge badge-soft-info">فرعي</span>
                @endif

                {{-- Status --}}
                @if($node->status)
                    <span class="badge badge-soft-success">نشط</span>
                @else
                    <span class="badge badge-soft-danger">غير نشط</span>
                @endif
            </div>

            <div class="tree-meta">
                <span>الحالي: {{ number_format((float)($node->current_balance ?? 0), 2) }}</span>

                @if($node->type)
                    <span class="badge badge-soft-info">{{ $node->type->name }}</span>
                @endif

                {{-- ✅ المسار: يوضح تبع مين بشكل نهائي --}}
                @if(!$isRoot)
                    <span class="tree-path">
                        المسار: <strong>{{ $node->path }}</strong>
                    </span>
                @endif
            </div>
        </div>

        {{-- Actions --}}
        <div class="tree-actions">
            <a href="{{ route('accounts.create', ['parent_account_id' => $node->id]) }}"
               class="btn btn-light btn-sm">
                + فرعي
            </a>
        </div>
    </div>

    {{-- Children --}}
    @if($hasChildren)
        <ul class="tree-children" style="display:none;">
            @foreach($node->children as $child)
                @include('admin.accounts.partials.tree-node', [
                    'node' => $child,
                    'level' => $level + 1
                ])
            @endforeach
        </ul>
    @endif

</li>
