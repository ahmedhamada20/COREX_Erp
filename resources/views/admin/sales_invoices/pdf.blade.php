{{-- resources/views/admin/sales_invoices/pdf.blade.php --}}

@php
    $fmt  = fn($n, $d=2) => number_format((float)($n ?? 0), $d);
    $date = fn($v) => $v ? \Carbon\Carbon::parse($v)->format('Y-m-d') : '-';

    // $shape coming from controller (optional)
    $shape = $shape ?? fn($t) => $t;

    // Detect mixed Arabic+Latin/Numbers (avoid shaping + avoid .ar class)
    $isMixed = function (?string $text): bool {
        if (!$text) return false;
        return (bool) preg_match('/[A-Za-z0-9]/', $text);
    };

    // Render helper:
    $renderAr = function (?string $text) use ($shape, $isMixed) {
        $text = (string)($text ?? '');
        if ($text === '') return '';
        if ($isMixed($text)) return '<span>' . e($text) . '</span>';
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

    $companyNameRaw    = $setting->name ?? config('app.name');
    $companyPhoneRaw   = $setting->phone ?? null;
    $companyAddressRaw = $setting->address ?? null;

    $customerNameRaw   = $invoice->customer?->name ?? ('Customer #'.$invoice->customer_id);
    $customerPhoneRaw  = $invoice->customer?->phone ?? null;
    $customerCodeRaw   = $invoice->customer?->code ?? null;

    $invCode   = $invoice->invoice_code ?? ('SI-'.$invoice->id);
    $invNumber = $invoice->invoice_number ?? $invCode;

    // Cairo fonts
    $cairoR = storage_path('fonts/Cairo-Regular.ttf');
    $cairoB = storage_path('fonts/Cairo-Bold.ttf');

    // DomPDF logo base64
    $logo64 = null;
    if (!empty($setting?->logo)) {
        $p = public_path('storage/' . ltrim($setting->logo, '/'));
        if (is_file($p)) {
            $mime = mime_content_type($p) ?: 'image/png';
            $logo64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($p));
        }
    }

    // Items
    $items = $invoice->items ?? collect();

    // Payments (optional - if loaded)
    $payments = $invoice->payments ?? collect();
@endphp

    <!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>فاتورة مبيعات</title>

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

        /* PURE Arabic shaped */
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

        table, .header-table, .mini, .items-table, .two-col,
        .info-table, .totals-table, .signatures {
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

        .logo{
            width:56px; height:56px;
            border:1px solid #e2e8f0;
            border-radius:14px;
            overflow:hidden;
            background:#fff;
            text-align:center;
        }
        .logo img{ width:56px; height:56px; object-fit:cover; display:block; }

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

        .footer{
            position: fixed;
            bottom: -10mm;
            left: 0; right: 0;
            text-align: center;
            font-size: 9.5px;
            color: #94a3b8;
        }
    </style>
</head>

<body>

{{-- HEADER --}}
<div class="card">
    <table class="header-table">
        <tr>
            <td style="width:70%; padding-left:30px;">
                <div class="inv-title">
                    {!! $renderAr('فاتورة مبيعات') !!} — {!! $renderAr($companyNameRaw) !!}
                </div>

                <table class="mini" style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td class="strong" style="width:12%;"><span class="num">{{ $invNumber }}</span></td>
                        <td class="muted" style="width:13%;">{!! $renderAr('رقم الفاتورة') !!}</td>

                        <td class="strong" style="width:12%;"><span class="num">{{ $invCode }}</span></td>
                        <td class="muted" style="width:13%;">{!! $renderAr('كود الفاتورة') !!}</td>

                        <td class="strong" style="width:12%;"><span class="num">{!! $renderAr($payLabel) !!}</span></td>
                        <td class="muted" style="width:13%;">{!! $renderAr('الدفع') !!}</td>
                    </tr>

                    <tr>
                        <td class="strong"><span class="num">{!! $renderAr($statusLabel) !!}</span></td>
                        <td class="muted">{!! $renderAr('الحالة') !!}</td>

                        <td class="strong"><span class="num">{{ $date($invoice->invoice_date) }}</span></td>
                        <td class="muted">{!! $renderAr('تاريخ الفاتورة') !!}</td>

                        <td class="strong">
                            {{ ($invoice->payment_type ?? 'cash') === 'cash'
                                ? '-'
                                : $date($invoice->due_date) }}
                        </td>
                        <td class="muted">{!! $renderAr('استحقاق') !!}</td>
                    </tr>
                </table>
            </td>

            <td style="width:30%;" class="logo-wrap">
                <div class="logo" style="margin-left:auto;">
                    @if($logo64)
                        <img src="{{ $logo64 }}" alt="logo">
                    @endif
                </div>

                <div style="margin-top:8px;">
                    @if($companyAddressRaw)
                        <div class="muted" style="font-size:10.5px;">{!! $renderAr($companyAddressRaw) !!}</div>
                    @endif
                    @if($companyPhoneRaw)
                        <div class="muted" style="font-size:10.5px;">
                            <span class="num">{{ $companyPhoneRaw }}</span> {!! $renderAr('هاتف') !!}
                        </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ITEMS --}}
<div class="card">
    <div class="card-title">{!! $renderAr('بنود الفاتورة') !!}</div>

    <table class="items-table">
        <thead>
        <tr>


            <th style="width:110px; direction:ltr !important;" class="text-end">{!! $renderAr('الإجمالي') !!}</th>
            <th style="width:95px; direction:ltr !important;" class="text-end">VAT</th>
            <th style="width:95px; direction:ltr !important;" class="text-end">{!! $renderAr('خصم') !!}</th>
            <th style="width:95px; direction:ltr !important;" class="text-end">{!! $renderAr('السعر') !!}</th>
            <th style="width:90px; direction:ltr !important;" class="text-end">{!! $renderAr('الكمية') !!}</th>
            <th style="width:260px;">{!! $renderAr('الصنف') !!}</th>
            <th style="width:70px; direction:ltr !important;" class="text-end">#</th>



        </tr>
        </thead>

        <tbody>
        @forelse($items as $i => $line)
            @php
                $it = $line->item;
                $itemNameRaw = $it?->name ?? ('Item #'.$line->item_id);
                $code = $it?->items_code ?? $it?->code ?? null;
                $barcode = $it?->barcode ?? null;
            @endphp
            <tr>
                <td style="direction:ltr !important;" class="text-end strong">{{ $fmt($line->total, 4) }}</td>
                <td style="direction:ltr !important;" class="text-end strong">{{ $fmt($line->vat, 4) }}</td>
                <td style="direction:ltr !important;" class="text-end strong">{{ $fmt($line->discount, 4) }}</td>
                <td style="direction:ltr !important;" class="text-end strong">{{ $fmt($line->price, 4) }}</td>
                <td style="direction:ltr !important;" class="text-end strong">{{ $fmt($line->quantity, 4) }}</td>
                <td>
                    <div class="item-name">{!! $renderAr($itemNameRaw) !!}</div>
                    <div class="item-sub">
                        @if($code) #{{ $code }} @endif
                        @if($code && $barcode) — @endif
                        @if($barcode) {{ $barcode }} @endif
                    </div>
                </td>

                <td style="direction:ltr !important;" class="text-end num strong">{{ $i + 1 }}</td>










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

{{-- TOTALS + INFO --}}
<div class="card">
    <table class="two-col">
        <tr>
            {{-- RIGHT: Totals --}}
            <td style="width:45%; padding-left:10px;">
                <div class="card-title">{!! $renderAr('الإجماليات') !!}</div>

                <div class="totals-box">
                    <table class="totals-table">
                        <tr>
                            <td class="num strong">{{ $fmt($invoice->subtotal, 2) }}</td>
                            <td class="muted">{!! $renderAr('Subtotal') !!}</td>
                        </tr>
                        <tr>
                            <td class="num">{{ $fmt($invoice->discount_amount, 2) }}</td>
                            <td class="muted">{!! $renderAr('خصم الفاتورة') !!}</td>
                        </tr>
                        <tr>
                            <td class="num">{{ $fmt($invoice->vat_amount, 2) }}</td>
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

                @if($payments->count())
                    <div style="margin-top:10px;">
                        <div class="card-title">{!! $renderAr('المدفوعات') !!}</div>
                        <table class="info-table">
                            @foreach($payments as $p)
                                <tr>
                                    <td class="strong">
                                        <span class="num">{{ $date($p->payment_date) }}</span>
                                        — {!! $renderAr($p->treasury?->name ?? ('#'.$p->treasury_id)) !!}
                                    </td>
                                    <td class="muted" style="width:38%;">
                                        <span class="num">{{ $fmt($p->amount, 2) }}</span>
                                        ({{ $p->method ?? '-' }})
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif
            </td>

            {{-- LEFT: Customer Info --}}
            <td style="width:55%; padding-right:10px;">
                <div class="card-title">{!! $renderAr('معلومات العميل') !!}</div>

                <table class="info-table">
                    <tr>
                        <td class="strong">{!!  $renderAr($customerNameRaw)  !!}  </td>
                        <td class="muted" style="width:38%;">{!! $renderAr('اسم العميل') !!}</td>
                    </tr>

                    <tr>
                        <td class="strong">
                            @if($customerPhoneRaw)
                                <span class="num">{!!  $renderAr($customerPhoneRaw)  !!}</span>
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
                        <td class="strong"><span class="num">{{ $date($invoice->created_at) }}</span></td>
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
                            <td class="strong">{!! $renderAr($invoice->notes) !!}</td>
                            <td class="muted">{!! $renderAr('ملاحظات') !!}</td>
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
