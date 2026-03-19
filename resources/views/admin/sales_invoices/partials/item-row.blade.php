@php
    // $index required
    $itemId   = data_get($row, 'item_id', null);
    $itemText = data_get($row, 'item_text', null);

    $qty   = data_get($row, 'qty', 1);
    $price = data_get($row, 'price', 0);
    $disc  = data_get($row, 'discount', 0);
    $vatR  = data_get($row, 'vat_rate', 0);
    $lineT = data_get($row, 'line_total', 0);
@endphp

<tr>
    <td class="text-center fw-bold row-index">1</td>

    <td>
        <select class="form-select item-select"
                data-placeholder="ابحث عن صنف بالاسم / الباركود / الكود"
                data-name="items[__i__][item_id]">
            <option value=""></option>

            {{-- preserve selected on validation --}}
            @if($itemId && $itemText)
                <option value="{{ $itemId }}" selected>{{ $itemText }}</option>
            @endif
        </select>

        <input type="hidden" data-name="items[__i__][item_text]" value="{{ $itemText }}">
        <div class="hint">اختيار الصنف يحدد حركة المخزون وقيود الإيراد.</div>
    </td>

    <td>
        <input type="number" step="0.0001" min="0" class="form-control qty"
               value="{{ $qty }}" data-name="items[__i__][qty]">
    </td>

    <td>
        <input type="number" step="0.0001" min="0" class="form-control price"
               value="{{ $price }}" data-name="items[__i__][price]">
    </td>

    <td>
        <input type="number" step="0.0001" min="0" class="form-control discount"
               value="{{ $disc }}" data-name="items[__i__][discount]">
        <div class="hint">خصم بند (قيمة).</div>
    </td>

    <td>
        <input type="number" step="0.0001" min="0" class="form-control vat_rate"
               value="{{ $vatR }}" data-name="items[__i__][vat_rate]">
    </td>

    <td>
        <input type="text" class="form-control line_total money" value="{{ number_format((float)$lineT,2) }}" readonly
               data-name="items[__i__][line_total]">
    </td>

    <td class="text-center">
        <button type="button" class="btn btn-outline-danger btn-icon remove-row" title="حذف">
            <i class="ti ti-x"></i>
        </button>
    </td>
</tr>
