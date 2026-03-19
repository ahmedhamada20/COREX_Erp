{{-- resources/views/admin/purchase_invoices/pdf.blade.php --}}

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

    $statusLabel = match ($invoice->status ?? 'draft') {
        'draft'     => 'مسودة',
        'posted'    => 'مُرحّلة',
        'paid'      => 'مدفوعة',
        'partial'   => 'جزئي',
        'cancelled' => 'ملغاة',
        default     => (string)($invoice->status ?? '-'),
    };

    $payLabel = ($invoice->payment_type ?? 'cash') === 'cash' ? 'كاش' : 'آجل';

    $companyNameRaw    = $setting->name ?? config('app.name');  // could be mixed (COREX)
    $companyPhoneRaw   = $setting->phone ?? null;
    $companyAddressRaw = $setting->address ?? null;

    $supplierNameRaw   = $invoice->supplier->name ?? ('Supplier #'.$invoice->supplier_id);
    $invCode           = $invoice->purchase_invoice_code ?? ('PI-'.$invoice->id);
    $invnumber          = $invoice->invoice_number ?? ('PI-'.$invoice->id);

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
    <title>فاتورة مشتريات</title>

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

@php
    $logoPath = null;

    if (!empty($setting?->logo)) {
        $p = public_path('storage/' . ltrim($setting->logo, '/'));

        if (is_file($p)) {

            $logoPath = str_replace('\\', '/', realpath($p));
        }
    }
@endphp

<div class="card">
    <table class="header-table">
        <tr>

            <td style="width:65%; padding-left:40px;">
                <div class="inv-title">{!! $renderAr('فاتورة مشتريات') !!} -- {!! $renderAr($companyNameRaw) !!}

                </div>


                <table class="mini" style="width:100%; border-collapse:collapse;">
                    <tr>

                        <td class="strong" style="width:11%;">
                            <span class="num">{{ $invnumber }}</span>
                        </td>
                        <td class="muted" style="width:12%;">
                            {!! $renderAr('رقم الفاتورة') !!}
                        </td>


                        <td class="strong" style="width:11%;">
                            <span class="num">{{ $invCode }}</span>
                        </td>
                        <td class="muted" style="width:12%;">
                            {!! $renderAr('كود الفاتورة') !!}
                        </td>


                        <td class="strong" style="width:11%;">
                            <span class="num">{!! $renderAr($payLabel) !!}</span>
                        </td>
                        <td class="muted" style="width:12%;">
                            {!! $renderAr('الدفع') !!}
                        </td>
                    </tr>

                    <tr>

                        <td class="strong">
                            <span class="num">{!! $renderAr($statusLabel) !!}</span>
                        </td>
                        <td class="muted">
                            {!! $renderAr('الحالة') !!}
                        </td>


                        <td class="strong">
                            {{ $date($invoice->invoice_date) }}
                        </td>
                        <td class="muted">
                            {!! $renderAr('تاريخ الفاتورة') !!}
                        </td>


                        <td class="strong">
                            {{ ($invoice->payment_type ?? 'cash') === 'cash'
                                ? '-'
                                : $date($invoice->due_date) }}
                        </td>
                        <td class="muted">
                            {!! $renderAr('استحقاق') !!}
                        </td>
                    </tr>
                </table>


            </td>
            <td style="width:18%;" class="logo-wrap">

                <div class="logo" style="margin-left:auto;">
                    @php
                        $logo64 = null;
                        if (!empty($setting?->logo)) {
                            $p = public_path('storage/' . ltrim($setting->logo, '/'));
                            if (is_file($p)) {
                                $mime = mime_content_type($p) ?: 'image/png';
                                $logo64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($p));
                            }
                        }
                    @endphp

                    @if($logo64)
                        <img src="{{ $logo64 }}" alt="logo" style="width:58px;height:58px;object-fit:cover;">
                    @endif

                </div>
            </td>
            {{-- LEFT SIDE (Company) --}}
{{--            <td style="width:35%; padding-right:15px;">--}}
{{--                <table class="brand-table">--}}
{{--                    <tr>--}}
{{--                        <td style="padding-left:32px;">--}}


{{--                            <div class="company-name">{!! $renderAr($companyNameRaw) !!}</div>--}}

{{--                            @if($companyAddressRaw)--}}
{{--                                <div class="muted" style="margin-top:2px; font-size:10.5px;">--}}
{{--                                    {!! $renderAr($companyAddressRaw) !!}--}}
{{--                                </div>--}}
{{--                            @endif--}}

{{--                            @if($companyPhoneRaw)--}}
{{--                                <div class="muted" style="font-size:10.5px;">--}}
{{--                                    <span class="num">{{ $companyPhoneRaw }}</span>  {!! $renderAr('هاتف') !!}--}}
{{--                                </div>--}}
{{--                            @endif--}}
{{--                        </td>--}}
{{--                    </tr>--}}
{{--                </table>--}}
{{--            </td>--}}
        </tr>
    </table>

</div>
{{-- ✅ ITEMS --}}
<div class="card">
    <div class="card-title">{!! $renderAr('بنود الفاتورة') !!}</div>

    {{-- ✅ IMPORTANT: order columns visually RTL by writing them from right to left --}}
    <table class="items-table">
        <thead>
        <tr>
            <th style="width:84px ; direction: ltr !important;" class="text-end" >{!! $renderAr('الإجمالي') !!}</th>
            <th style="width:84px ; direction: ltr !important;" class="text-end">VAT</th>
            <th style="width:84px ; direction: ltr !important;" class="text-end">{!! $renderAr('خصم') !!}</th>
            <th style="width:84px ; direction: ltr !important;" class="text-end">{!! $renderAr('السعر') !!}</th>
            <th style="width:84px ; direction: ltr !important;" class="text-end">{!! $renderAr('الكمية') !!}</th>
            <th style="width:84px ; direction: ltr !important;" class="text-end">{!! $renderAr('الصنف') !!}</th>

            <th style="width:84px ; direction: ltr !important;" class="text-end">#</th>
        </tr>
        </thead>

        <tbody>
        @forelse($invoice->items as $i => $it)
            @php
                $discType = $it->discount_type ?? 'none';
                $disc = '-';
                if ($discType === 'percent') $disc = $fmt($it->discount_rate, 2).'%';
                elseif ($discType === 'fixed') $disc = $fmt($it->discount_value, 2);

                $taxRate  = $it->tax_rate !== null ? $fmt($it->tax_rate, 2) : '-';
                $itemNameRaw = $it->item->name ?? ('Item #'.$it->item_id);

                $code   = $it->item->items_code ?? '-';
                $barcode= $it->item->barcode ?? '-';
            @endphp

            <tr>
                <td style="direction: ltr !important;" class="text-end  strong">{{ $fmt($it->line_total ?? 0, 2) }}</td>
                <td style="direction: ltr !important;" class="text-end  strong">{{ $taxRate }}</td>
                <td style="direction: ltr !important;" class="text-end  strong">{{ $disc }}</td>
                <td style="direction: ltr !important;" class="text-end  strong">{{ $fmt($it->unit_price, 2) }}</td>
                <td style="direction: ltr !important;" class="text-end  strong">{{ $fmt($it->quantity, 2) }}</td>



                <td class=" strong">
                    {!! $renderAr($itemNameRaw) !!}
                </td>


                <td class="text-end num strong">{{ $i + 1 }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="center muted" style="padding:12px;">
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
                {{-- RIGHT: Totals (place first in HTML so it's on the right) --}}
                <td style="width:45%; padding-left:10px;">
                    <div class="card-title">{!! $renderAr('الإجماليات') !!}</div>

                    <div class="totals-box">
                        <table class="totals-table">
                            <tr>
                                <td class="num strong">{{ $fmt($invoice->subtotal_before_discount, 2) }}</td>
                                <td class="muted">{!! $renderAr('قبل الخصم') !!}</td>
                            </tr>
                            <tr>
                                <td class="num">{{ $fmt($invoice->discount_value, 2) }}</td>
                                <td class="muted">{!! $renderAr('خصم الفاتورة') !!}</td>
                            </tr>
                            <tr>
                                <td class="num">{{ number_format((float)($invoice->items?->sum('discount_value') ?? 0), 2) }}</td>
                                <td class="muted">{!! $renderAr('خصم السطور') !!}</td>
                            </tr>
                            <tr>
                                <td class="num strong">{{ $fmt($invoice->subtotal, 2) }}</td>
                                <td class="muted">{!! $renderAr('بعد الخصم') !!}</td>
                            </tr>
                            <tr>
                                <td class="num">{{ $fmt($invoice->tax_value, 2) }}</td>
                                <td class="muted">VAT</td>
                            </tr>

                            <tr class="final-row">
                                <td class="num strong">{{ $fmt($invoice->total, 2) }}</td>
                                <td class="strong">{!! $renderAr('الإجمالي النهائي') !!}</td>
                            </tr>

                            <tr>
                                <td class="num">{{ $fmt($invoice->paid_amount, 2) }}</td>
                                <td class="muted">{!! $renderAr('المدفوع') !!}</td>
                            </tr>
                            <tr>
                                <td class="num strong">{{ $fmt($invoice->remaining_amount, 2) }}</td>
                                <td class="muted">{!! $renderAr('المتبقي') !!}</td>
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
                                @if($invoice->supplier->phone ?? null)
                                    <span class="num">{{ $invoice->supplier->phone }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="muted">{!! $renderAr('رقم هاتف المورد') !!}</td>
                        </tr>

                        <tr>
                            <td class="strong"><span class="num">{{ $invoice->invoice_number ?? '-' }}</span></td>
                            <td class="muted">{!! $renderAr('رقم فاتورة المورد') !!}</td>
                        </tr>

                        <tr>
                            <td class="strong num">{{ $date($invoice->created_at) }}</td>
                            <td class="muted">{!! $renderAr('تاريخ الإنشاء') !!}</td>
                        </tr>

                        @if($invoice->posted_at)
                            <tr>
                                <td class="strong num">{{ \Carbon\Carbon::parse($invoice->posted_at)->format('Y-m-d H:i') }}</td>
                                <td class="muted">{!! $renderAr('تم الترحيل') !!}</td>
                            </tr>
                        @endif

                        @if(!empty($invoice->notes))
                            <tr>
                                <td class="muted">{!! $renderAr('ملاحظات') !!}</td>
                                <td class="strong">{!! $renderAr($invoice->notes) !!}</td>
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
