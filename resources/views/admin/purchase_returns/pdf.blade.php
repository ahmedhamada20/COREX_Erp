{{-- resources/views/admin/purchase_returns/pdf.blade.php --}}

@php
    $fmt  = fn($n, $d=2) => number_format((float)($n ?? 0), $d);
    $date = fn($v) => $v ? \Carbon\Carbon::parse($v)->format('Y-m-d') : '-';

    // $shape coming from controller
    $shape = $shape ?? fn($t) => $t;

    // Detect mixed Arabic+Latin/Numbers (avoid shaping + avoid .ar class)
    $isMixed = function (?string $text): bool {
        if (!$text) return false;
        return (bool) preg_match('/[A-Za-z0-9]/', $text);
    };

    // ✅ Render helper:
    // - If mixed => print normal (RTL) without shaping
    // - Else => shape + wrap with .ar (LTR bidi embed) to fix reversed glyph order
    $renderAr = function (?string $text) use ($shape, $isMixed) {
        $text = (string)($text ?? '');
        if ($text === '') return '';

        if ($isMixed($text)) {
            return '<span>' . e($text) . '</span>';
        }

        return '<span class="ar">' . e($shape($text)) . '</span>';
    };

    // invoice relation name compatibility
    $invoice = $return->invoice ?? $return->purchaseInvoice ?? null;

    $statusLabel = match ($return->status ?? 'draft') {
        'draft'     => 'مسودة',
        'posted'    => 'مُرحّل',
        'cancelled' => 'ملغى',
        default     => (string)($return->status ?? '-'),
    };

    $companyNameRaw    = $setting->name ?? config('app.name');  // could be mixed (COREX)
    $companyPhoneRaw   = $setting->phone ?? null;
    $companyAddressRaw = $setting->address ?? null;

    $supplierNameRaw   = $return->supplier->name ?? ('Supplier #'.$return->supplier_id);

    $returnCode        = $return->purchase_return_code ?? ('PR-'.$return->id);
    // ✅ return_number عندك = رقم الفاتورة (required)
    $returnNumber      = $return->return_number ?? ($invoice->invoice_number ?? ('INV-'.$return->id));

    $invCode           = $invoice->purchase_invoice_code ?? ($invoice?->id ? ('PI-'.$invoice->id) : '-');
    $invnumber         = $invoice->invoice_number ?? ($returnNumber ?? '-');

    // DOMPDF local logo
    $logoPath = null;
    if (!empty($setting?->logo)) {
        $p = public_path('storage/' . $setting->logo);
        if (is_file($p)) $logoPath = $p;
    }

    // Cairo fonts
    $cairoR = storage_path('fonts/Cairo-Regular.ttf');
    $cairoB = storage_path('fonts/Cairo-Bold.ttf');
@endphp

    <!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>مرتجع مشتريات</title>

    <style>
        @page { margin: 12mm 10mm 16mm 10mm; }
        * { box-sizing: border-box; }

        @font-face{
            font-family: "Cairo";
            font-style: normal;
            font-weight: 400;
            src: url("file://{{ $cairoR }}") format("truetype");
        }
        @font-face{
            font-family: "Cairo";
            font-style: normal;
            font-weight: 700;
            src: url("file://{{ $cairoB }}") format("truetype");
        }

        html, body { direction: rtl; text-align: right; }
        body{
            font-family: "Cairo", "DejaVu Sans", sans-serif;
            font-size: 11px;
            color: #0f172a;
            margin: 0;
        }

        /* ✅ Only for PURE Arabic shaped text */
        .ar{
            direction: ltr;
            unicode-bidi: embed;
            text-align: right;
        }

        /* Numbers LTR */
        .num{
            direction: ltr;
            unicode-bidi: embed;
            text-align: left;
            white-space: nowrap;
            font-weight: 700;
        }

        .muted{ color:#64748b; }
        .strong{ font-weight:700; }
        .center{ text-align:center; }

        /* ⚠️ Avoid-break only when REALLY needed */
        .avoid-break{ page-break-inside: avoid; }

        table { page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }

        /* ✅ Force RTL on tables for DomPDF */
        table, .header-table, .brand-table, .mini, .items-table, .two-col,
        .info-table, .totals-table, .signatures {
            direction: rtl;
        }
        th, td { text-align: right; }

        .header{
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 10px;
            margin-bottom: 10px;
            background:#fff;
        }
        .header-table{ width:100%; border-collapse: collapse; table-layout: fixed; }
        .header-table td{ vertical-align: top; }

        .brand-table{ width:100%; border-collapse: collapse; table-layout: fixed; }

        .logo{
            width:56px; height:56px;
            border:1px solid #e2e8f0;
            border-radius:14px;
            overflow:hidden;
            background:#fff;
            text-align:center;
        }
        .logo img{ width:56px; height:56px; object-fit:cover; display:block; }

        .company-name{ font-size: 16px; font-weight: 700; line-height: 1.2; margin: 0; }
        .inv-title{ font-size: 16px; font-weight: 700; margin: 0; line-height: 1.2; }

        .pills{ margin-top: 6px; }
        .pill{
            display:inline-block;
            padding: 3px 9px;
            border-radius: 999px;
            background:#f8fafc;
            border:1px solid #e2e8f0;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
            margin-left: 6px;
            margin-top: 6px;
        }

        .mini{
            width:100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        .mini td{
            padding: 6px 7px;
            font-size: 10.5px;
            border-bottom: 1px dashed #e2e8f0;
        }
        .mini tr:last-child td{ border-bottom: 0; }

        .card{
            border:1px solid #e2e8f0;
            border-radius: 14px;
            padding: 10px;
            margin-bottom: 10px;
            background:#fff;
        }
        .card-title{ font-weight:700; margin:0 0 8px; font-size: 12px; }

        .items-table{ width:100%; border-collapse: collapse; table-layout: fixed; }
        .items-table thead th{
            background:#0f172a; color:#fff;
            padding: 7px 6px;
            font-size: 10.5px;
            font-weight:700;
            white-space: nowrap;
            text-align: right;
        }
        .items-table tbody td{
            border-bottom: 1px solid #f1f5f9;
            padding: 7px 6px;
            font-size: 10.7px;
            vertical-align: top;
            text-align: right;
        }
        .items-table tbody tr:nth-child(even) td{ background: #fafafa; }

        .item-name{ font-weight:700; margin-bottom:2px; }
        .item-sub{ font-size: 9.7px; color:#64748b; line-height:1.35; }

        .two-col{ width:100%; border-collapse: collapse; table-layout: fixed; }
        .two-col td{ vertical-align: top; padding: 0; }

        .info-table, .totals-table{ width:100%; border-collapse: collapse; }
        .info-table td, .totals-table td{
            padding: 6px;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 10.5px;
            text-align: right;
        }
        .info-table tr:last-child td,
        .totals-table tr:last-child td{ border-bottom: 0; }

        .totals-box{
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            background: #f8fafc;
        }
        .final-row td{
            border-top: 2px solid #e2e8f0 !important;
            font-weight: 700;
            font-size: 11.7px;
            padding-top: 8px;
        }

        .signatures{ width:100%; border-collapse: collapse; table-layout: fixed; margin-top: 10px; }
        .signatures td{
            border:1px solid #e2e8f0;
            border-radius: 14px;
            padding: 8px;
            height: 60px;
            vertical-align: top;
            background:#fff;
        }
        .sign-label{ color:#64748b; font-size: 10.5px; }
        .sign-line{ margin-top: 30px; border-top: 1px solid #cbd5e1; }

        .footer{
            position: fixed;
            bottom: -10mm;
            left: 0; right: 0;
            text-align: center;
            font-size: 9.5px;
            color: #94a3b8;
        }

        .watermark{
            position: fixed;
            top: 45%;
            left: 0; right: 0;
            text-align:center;
            font-size: 42px;
            color: #f8fafc;
            font-weight: 700;
            transform: rotate(-18deg);
            z-index: -1;
        }
    </style>
</head>

<body>

<div class="card">
    <table class="header-table">
        <tr>
            {{-- RIGHT SIDE (Return info) --}}
            <td style="width:65%; padding-left:40px;">
                <div class="inv-title">{!! $renderAr('مرتجع مشتريات') !!} -- {!! $renderAr($companyNameRaw) !!}</div>

                <table class="mini" style="width:100%; border-collapse:collapse;">
                    <tr>
                        {{-- Return Number (Invoice Number stored) --}}
                        <td class="strong" style="width:11%;">
                            <span class="num">{{ $returnNumber }}</span>
                        </td>
                        <td class="muted" style="width:12%;">
                            {!! $renderAr('رقم الفاتورة') !!}
                        </td>

                        {{-- Return Code --}}
                        <td class="strong" style="width:11%;">
                            <span class="num">{{ $returnCode }}</span>
                        </td>
                        <td class="muted" style="width:12%;">
                            {!! $renderAr('كود المرتجع') !!}
                        </td>

                        {{-- Invoice Code --}}
                        <td class="strong" style="width:11%;">
                            <span class="num">{{ $invCode }}</span>
                        </td>
                        <td class="muted" style="width:12%;">
                            {!! $renderAr('كود الفاتورة') !!}
                        </td>
                    </tr>

                    <tr>
                        {{-- Status --}}
                        <td class="strong">
                            <span class="num">{!! $renderAr($statusLabel) !!}</span>
                        </td>
                        <td class="muted">
                            {!! $renderAr('الحالة') !!}
                        </td>

                        {{-- Return Date --}}
                        <td class="strong">
                            {{ $date($return->return_date ?? $return->date) }}
                        </td>
                        <td class="muted">
                            {!! $renderAr('تاريخ المرتجع') !!}
                        </td>

                        {{-- Transaction ID --}}
                        <td class="strong">
                            <span class="num">{{ $return->transaction_id ?? '-' }}</span>
                        </td>
                        <td class="muted">
                            {!! $renderAr('Transaction') !!}
                        </td>
                    </tr>
                </table>
            </td>

            {{-- LEFT SIDE (Company) --}}
            {{-- نفس ملفك: متساب/مقفول حسب رغبتك --}}
            {{-- لو عايز ترجّعه افتحه --}}
        </tr>
    </table>
</div>

{{-- ✅ ITEMS --}}
<div class="card">
    <div class="card-title">{!! $renderAr('بنود المرتجع') !!}</div>

    <table class="items-table">
        <thead>
        <tr>
            <th style="width:84px ; direction: ltr !important;" class="text-end">{!! $renderAr('الإجمالي') !!}</th>
            <th style="width:84px ; direction: ltr !important;" class="text-end">VAT</th>
            <th style="width:84px ; direction: ltr !important;" class="text-end">{!! $renderAr('السعر') !!}</th>
            <th style="width:84px ; direction: ltr !important;" class="text-end">{!! $renderAr('الكمية') !!}</th>
            <th style="width:84px ; direction: ltr !important;" class="text-end">{!! $renderAr('الصنف') !!}</th>
            <th style="width:84px ; direction: ltr !important;" class="text-end">#</th>
        </tr>
        </thead>

        <tbody>
        @forelse($return->items as $i => $it)
            @php
                $taxRate  = $it->tax_rate !== null ? $fmt($it->tax_rate, 2) : '-';
                $itemNameRaw = $it->item->name ?? ('Item #'.$it->item_id);
                $code   = $it->item->items_code ?? '-';
                $barcode= $it->item->barcode ?? '-';
            @endphp

            <tr>
                <td style="direction: ltr !important;" class="text-end strong">{{ $fmt($it->line_total ?? 0, 2) }}</td>
                <td style="direction: ltr !important;" class="text-end strong">{{ $taxRate }}</td>
                <td style="direction: ltr !important;" class="text-end strong">{{ $fmt($it->unit_price, 2) }}</td>
                <td style="direction: ltr !important;" class="text-end strong">{{ $fmt($it->quantity, 2) }}</td>

                <td class="strong">
                    {!! $renderAr($itemNameRaw) !!}
                </td>

                <td class="text-end num strong">{{ $i + 1 }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="center muted" style="padding:12px;">
                    {!! $renderAr('لا يوجد بنود') !!}
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="card">
    <div class="card-body">
        <table class="two-col">
            <tr>
                {{-- RIGHT: Totals --}}
                <td style="width:45%; padding-left:10px;">
                    <div class="card-title">{!! $renderAr('الإجماليات') !!}</div>

                    <div class="totals-box">
                        <table class="totals-table">
                            <tr>
                                <td class="num strong">{{ $fmt($return->subtotal ?? 0, 2) }}</td>
                                <td class="muted">{!! $renderAr('Subtotal') !!}</td>
                            </tr>

                            <tr>
                                <td class="num">{{ $fmt($return->tax_value ?? 0, 2) }}</td>
                                <td class="muted">VAT</td>
                            </tr>

                            <tr class="final-row">
                                <td class="num strong">{{ $fmt($return->total ?? 0, 2) }}</td>
                                <td class="strong">{!! $renderAr('الإجمالي النهائي') !!}</td>
                            </tr>
                        </table>
                    </div>
                </td>

                {{-- LEFT: Info --}}
                <td style="width:55%; padding-right:10px;">
                    <div class="card-title">{!! $renderAr('معلومات') !!}</div>

                    <table class="info-table">
                        <tr>
                            <td class="strong">{!! $renderAr($supplierNameRaw) !!}</td>
                            <td class="muted" style="width:38%;">{!! $renderAr('اسم المورد') !!}</td>
                        </tr>

                        <tr>
                            <td class="strong">
                                @if($return->supplier->phone ?? null)
                                    <span class="num">{{ $return->supplier->phone }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="muted">{!! $renderAr('رقم هاتف المورد') !!}</td>
                        </tr>

                        {{-- Invoice Reference --}}
                        <tr>
                            <td class="strong"><span class="num">{{ $invnumber }}</span></td>
                            <td class="muted">{!! $renderAr('رقم الفاتورة المرجعية') !!}</td>
                        </tr>

                        <tr>
                            <td class="strong num">{{ $date($return->created_at) }}</td>
                            <td class="muted">{!! $renderAr('تاريخ الإنشاء') !!}</td>
                        </tr>

                        @if(!empty($return->posted_at))
                            <tr>
                                <td class="strong num">{{ \Carbon\Carbon::parse($return->posted_at)->format('Y-m-d H:i') }}</td>
                                <td class="muted">{!! $renderAr('تم الترحيل') !!}</td>
                            </tr>
                        @endif

                        @if(!empty($return->notes))
                            <tr>
                                <td class="muted">{!! $renderAr('ملاحظات') !!}</td>
                                <td class="strong">{!! $renderAr($return->notes) !!}</td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="footer">
    {!! $renderAr($companyNameRaw) !!}
</div>

<script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->get_font("DejaVu Sans", "normal");
        $size = 9;
        $text = "صفحة {PAGE_NUM} / {PAGE_COUNT}";
        $pdf->page_text(270, 820, $text, $font, $size, [148/255,163/255,184/255]);
    }
</script>

</body>
</html>
