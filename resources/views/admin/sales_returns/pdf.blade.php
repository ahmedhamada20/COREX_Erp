{{-- resources/views/admin/sales_returns/pdf.blade.php --}}

@php
    /** @var \App\Models\SalesReturn $return */
    $invoice = $return->invoice ?? null;

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

    $companyNameRaw    = $setting->name ?? config('app.name');
    $companyPhoneRaw   = $setting->phone ?? null;
    $companyAddressRaw = $setting->address ?? null;

    $customerNameRaw   = $return->customer->name ?? ('Customer #'.$return->customer_id);
    $customerPhoneRaw  = $return->customer->phone ?? null;
    $customerCodeRaw   = $return->customer->code ?? null;

    $invCode = $invoice?->invoice_code ?? $invoice?->invoice_number ?? null;
    $invDate = $invoice?->invoice_date ?? null;
    $payLabel = ($invoice?->payment_type ?? 'cash') === 'cash' ? 'كاش' : 'آجل';

    // Return code (optional)
    $returnCode = $return->sales_return_code ?? ('SR-'.$return->id);

    // Cairo fonts
    $cairoR = storage_path('fonts/Cairo-Regular.ttf');
    $cairoB = storage_path('fonts/Cairo-Bold.ttf');
@endphp

    <!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>مرتجع مبيعات</title>

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

        table { page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }

        /* ✅ Force RTL on tables for DomPDF */
        table, .header-table, .mini, .info-table, .totals-table, .two-col {
            direction: rtl;
        }
        th, td { text-align: right; }

        .card{
            border:1px solid #e2e8f0;
            border-radius: 14px;
            padding: 10px;
            margin-bottom: 10px;
            background:#fff;
        }

        .header-table{ width:100%; border-collapse: collapse; table-layout: fixed; }
        .header-table td{ vertical-align: top; }

        .inv-title{ font-size: 16px; font-weight: 700; margin: 0; line-height: 1.2; }

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

        .logo{
            width:56px; height:56px;
            border:1px solid #e2e8f0;
            border-radius:14px;
            overflow:hidden;
            background:#fff;
            text-align:center;
        }
        .logo img{ width:56px; height:56px; object-fit:cover; display:block; }

        .card-title{ font-weight:700; margin:0 0 8px; font-size: 12px; }

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

<div class="watermark">{!! $renderAr('مرتجع مبيعات') !!}</div>

@php
    // DOMPDF inline base64 logo (most reliable)
    $logo64 = null;
    if (!empty($setting?->logo)) {
        $p = public_path('storage/' . ltrim($setting->logo, '/'));
        if (is_file($p)) {
            $mime = mime_content_type($p) ?: 'image/png';
            $logo64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($p));
        }
    }
@endphp

{{-- ✅ HEADER --}}
<div class="card">
    <table class="header-table">
        <tr>
            <td style="width:70%; padding-left:18px;">
                <div class="inv-title">
                    {!! $renderAr('مرتجع مبيعات') !!} — {!! $renderAr($companyNameRaw) !!}
                </div>

                <table class="mini" style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td class="strong" style="width:14%;">
                            <span class="num">{{ $returnCode }}</span>
                        </td>
                        <td class="muted" style="width:16%;">{!! $renderAr('كود المرتجع') !!}</td>

                        <td class="strong" style="width:14%;">
                            {{ $date($return->return_date) }}
                        </td>
                        <td class="muted" style="width:16%;">{!! $renderAr('تاريخ المرتجع') !!}</td>

                        <td class="strong" style="width:14%;">
                            <span class="num">{{ $invCode ?: '-' }}</span>
                        </td>
                        <td class="muted" style="width:16%;">{!! $renderAr('فاتورة أصلية') !!}</td>
                    </tr>

                    <tr>
                        <td class="strong">
                            {!! $renderAr($payLabel) !!}
                        </td>
                        <td class="muted">{!! $renderAr('نوع الدفع') !!}</td>

                        <td class="strong">
                            {{ $date($invDate) }}
                        </td>
                        <td class="muted">{!! $renderAr('تاريخ الفاتورة') !!}</td>

                        <td class="strong">
                            <span class="num">{{ $fmt($return->total, 2) }}</span>
                        </td>
                        <td class="muted">{!! $renderAr('إجمالي المرتجع') !!}</td>
                    </tr>
                </table>
            </td>

            <td style="width:30%;" class="logo-wrap">
                <div class="logo" style="margin-left:auto;">
                    @if($logo64)
                        <img src="{{ $logo64 }}" alt="logo">
                    @endif
                </div>

                {{-- اختياري: بيانات الشركة الصغيرة --}}
                <div style="margin-top:8px; font-size:10.5px;">
                    @if($companyAddressRaw)
                        <div class="muted">{!! $renderAr($companyAddressRaw) !!}</div>
                    @endif
                    @if($companyPhoneRaw)
                        <div class="muted">
                            <span class="num">{{ $companyPhoneRaw }}</span> {!! $renderAr('هاتف') !!}
                        </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ✅ DETAILS --}}
<div class="card">
    <div class="card-title">{!! $renderAr('بيانات العميل والمرتجع') !!}</div>

    <table class="two-col">
        <tr>
            {{-- RIGHT: Totals --}}
            <td style="width:45%; padding-left:10px;">
                <div class="card-title">{!! $renderAr('الإجماليات') !!}</div>

                <div class="totals-box">
                    <table class="totals-table">
                        <tr>
                            <td class="num strong">{{ $fmt($return->subtotal, 2) }}</td>
                            <td class="muted">{!! $renderAr('قيمة المرتجع قبل الضريبة') !!}</td>
                        </tr>
                        <tr>
                            <td class="num">{{ $fmt($return->vat_amount, 2) }}</td>
                            <td class="muted">VAT</td>
                        </tr>
                        <tr class="final-row">
                            <td class="num strong">{{ $fmt($return->total, 2) }}</td>
                            <td class="strong">{!! $renderAr('الإجمالي النهائي') !!}</td>
                        </tr>
                    </table>
                </div>

                <div style="margin-top:8px;" class="muted">
                    {!! $renderAr('ملاحظة: في الكاش يتم رد المبلغ إلى الخزنة، وفي الآجل يتم عكس/تخفيض حساب العميل.') !!}
                </div>
            </td>

            {{-- LEFT: Info --}}
            <td style="width:55%; padding-right:10px;">
                <div class="card-title">{!! $renderAr('معلومات') !!}</div>

                <table class="info-table">
                    <tr>
                        <td class="strong">{!! $renderAr($customerNameRaw) !!}</td>
                        <td class="muted" style="width:38%;">{!! $renderAr('اسم العميل') !!}</td>
                    </tr>

                    <tr>
                        <td class="strong">
                            @if($customerPhoneRaw)
                                <span class="num">{{ $customerPhoneRaw }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="muted">{!! $renderAr('هاتف العميل') !!}</td>
                    </tr>

                    <tr>
                        <td class="strong">
                            @if($customerCodeRaw)
                                <span class="num">{{ $customerCodeRaw }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="muted">{!! $renderAr('كود العميل') !!}</td>
                    </tr>

                    <tr>
                        <td class="strong">
                            @if($invCode)
                                <span class="num">{{ $invCode }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="muted">{!! $renderAr('الفاتورة الأصلية') !!}</td>
                    </tr>

                    <tr>
                        <td class="strong num">{{ $date($return->created_at) }}</td>
                        <td class="muted">{!! $renderAr('تاريخ الإنشاء') !!}</td>
                    </tr>

                    @if(!empty($return->journal_entry_id))
                        <tr>
                            <td class="strong"><span class="num">#{{ $return->journal_entry_id }}</span></td>
                            <td class="muted">{!! $renderAr('قيد اليومية') !!}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>
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
