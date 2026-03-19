<?php

return [

    'show_warnings' => false,

    // مهم جداً: dompdf بيقرأ ملفات من هنا
    'public_path' => public_path(),

    // غالباً الأفضل للعربي
    'convert_entities' => true,

    'options' => [
        // ✅ خليها Cairo
        'defaultFont' => 'cairo',

        // Parser
        'isHtml5ParserEnabled' => true,

        // لو بتجيب صور/خطوط من URL (مش لازم لو local path)
        'isRemoteEnabled' => true,

        // ✅ مهم جداً: السماح بقراءة ملفات النظام (fonts/images)
        'chroot' => [
            public_path(),
            storage_path(),
        ],

        // ✅ لوجات مفيدة أثناء التجربة
        // 'logOutputFile' => storage_path('logs/dompdf.html'),
    ],

    // ✅ مسارات الخطوط (لازم تكون موجودة ومسموح قراءتها)
    'font_dir' => storage_path('fonts'),
    'font_cache' => storage_path('fonts'),

    /**
     * ⚠️ dompdf غالباً مش بيقرأ font_data لوحدها بدون تحميل الخطوط في الـ cache
     * لكن هنخليها موجودة + هنستخدم @font-face fallback في الـ blade.
     */
    'font_data' => [
        'cairo' => [
            'R' => 'Cairo-Regular.ttf',
            'B' => 'Cairo-Bold.ttf',
            'useOTL' => 0xFF,
            'useKashida' => 75,
        ],
    ],
];
