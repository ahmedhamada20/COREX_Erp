#!/usr/bin/env php
<?php

/**
 * 🔍 أداة الفحص السريع للمشروع
 *
 * هذا الملف يقوم بفحص سريع لهيكل المشروع والتحقق من:
 * 1. وجود جميع النماذج الأساسية
 * 2. وجود جميع الخدمات المحاسبية
 * 3. وجود قاعدة البيانات والترحيلات
 * 4. صحة الاتصالات والتكوينات
 */

require_once __DIR__.'/vendor/autoload.php';

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║                   🔍 فحص سريع لهيكل المشروع 🔍                       ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

$passed = 0;
$failed = 0;

// ========== 1. فحص النماذج ==========
echo "📋 فحص النماذج الأساسية:\n";
echo "──────────────────────────────────────────\n";

$models = [
    'Account' => 'App\Models\Account',
    'AccountType' => 'App\Models\AccountType',
    'AccountBalance' => 'App\Models\AccountBalance',
    'Customer' => 'App\Models\Customer',
    'Supplier' => 'App\Models\Supplier',
    'Items' => 'App\Models\Items',
    'ItemCategory' => 'App\Models\ItemCategory',
    'JournalEntry' => 'App\Models\JournalEntry',
    'JournalEntryLine' => 'App\Models\JournalEntryLine',
    'SalesInvoice' => 'App\Models\SalesInvoice',
    'SalesInvoiceItem' => 'App\Models\SalesInvoiceItem',
    'SalesPayment' => 'App\Models\SalesPayment',
    'SalesReturn' => 'App\Models\SalesReturn',
    'SalesReturnItem' => 'App\Models\SalesReturnItem',
    'PurchaseInvoice' => 'App\Models\PurchaseInvoice',
    'PurchaseInvoiceItem' => 'App\Models\PurchaseInvoiceItem',
    'PurchasePayment' => 'App\Models\PurchasePayment',
    'PurchaseReturn' => 'App\Models\PurchaseReturn',
    'PurchaseReturnItem' => 'App\Models\PurchaseReturnItem',
    'Treasuries' => 'App\Models\Treasuries',
    'TreasuriesDelivery' => 'App\Models\TreasuriesDelivery',
    'User' => 'App\Models\User',
];

foreach ($models as $name => $class) {
    if (class_exists($class)) {
        echo "✅ {$name}\n";
        $passed++;
    } else {
        echo "❌ {$name} - غير موجود\n";
        $failed++;
    }
}

// ========== 2. فحص الخدمات ==========
echo "\n📦 فحص الخدمات المحاسبية:\n";
echo "──────────────────────────────────────────\n";

$services = [
    'PostSalesInvoiceToLedger' => 'App\Services\Accounting\PostSalesInvoiceToLedger',
    'PostPurchaseInvoiceToLedger' => 'App\Services\Accounting\PostPurchaseInvoiceToLedger',
    'PostSalesReturnToLedger' => 'App\Services\Accounting\PostSalesReturnToLedger',
    'PostPurchaseReturnToLedger' => 'App\Services\Accounting\PostPurchaseReturnToLedger',
    'PostSalesPaymentToLedger' => 'App\Services\Accounting\PostSalesPaymentToLedger',
    'PostSupplierPayment' => 'App\Services\Accounting\PostSupplierPayment',
    'TrialBalanceGenerator' => 'App\Services\Accounting\TrialBalanceGenerator',
    'AccountReconciliation' => 'App\Services\Accounting\AccountReconciliation',
    'AccountValidator' => 'App\Services\Accounting\AccountValidator',
    'AccountBalanceUpdater' => 'App\Services\Accounting\AccountBalanceUpdater',
    'PeriodClosingService' => 'App\Services\Accounting\PeriodClosingService',
    'ReverseJournalEntry' => 'App\Services\Accounting\ReverseJournalEntry',
    'BalanceSheetGenerator' => 'App\Services\Reporting\BalanceSheetGenerator',
    'IncomeStatementGenerator' => 'App\Services\Reporting\IncomeStatementGenerator',
];

foreach ($services as $name => $class) {
    if (class_exists($class)) {
        echo "✅ {$name}\n";
        $passed++;
    } else {
        echo "❌ {$name} - غير موجود\n";
        $failed++;
    }
}

// ========== 3. فحص الترحيلات ==========
echo "\n📁 فحص ملفات الترحيلات (Migrations):\n";
echo "──────────────────────────────────────────\n";

$migrationFiles = [
    'create_users_table.php',
    'create_account_types_table.php',
    'create_accounts_table.php',
    'create_journal_entries_table.php',
    'create_journal_entry_lines_table.php',
    'create_sales_invoices_table.php',
    'create_purchase_invoices_table.php',
    'create_customers_table.php',
    'create_suppliers_table.php',
    'create_items_table.php',
];

$migrationPath = __DIR__.'/database/migrations';
foreach ($migrationFiles as $file) {
    $found = false;
    if (is_dir($migrationPath)) {
        $files = scandir($migrationPath);
        foreach ($files as $f) {
            if (strpos($f, $file) !== false) {
                $found = true;
                break;
            }
        }
    }

    if ($found) {
        echo "✅ {$file}\n";
        $passed++;
    } else {
        echo "❌ {$file} - غير موجود\n";
        $failed++;
    }
}

// ========== 4. فحص الملفات المهمة ==========
echo "\n📄 فحص الملفات والمجلدات الأساسية:\n";
echo "──────────────────────────────────────────\n";

$paths = [
    'app' => 'المجلد الرئيسي للتطبيق',
    'app/Models' => 'مجلد النماذج',
    'app/Services' => 'مجلد الخدمات',
    'app/Http' => 'مجلد التحكم',
    'database' => 'مجلد قاعدة البيانات',
    'database/migrations' => 'مجلد الترحيلات',
    'routes' => 'مجلد المسارات',
    'resources' => 'مجلد الموارد',
    'resources/views' => 'مجلد العروض',
    'tests' => 'مجلد الاختبارات',
    'config' => 'مجلد الإعدادات',
];

foreach ($paths as $path => $description) {
    if (is_dir(__DIR__.'/'.$path)) {
        echo "✅ {$path} - {$description}\n";
        $passed++;
    } else {
        echo "❌ {$path} - غير موجود\n";
        $failed++;
    }
}

// ========== 5. فحص ملفات التكوين ==========
echo "\n⚙️  فحص ملفات التكوين:\n";
echo "──────────────────────────────────────────\n";

$configFiles = [
    'composer.json' => 'ملف التبعيات',
    'package.json' => 'ملف npm',
    '.env.example' => 'ملف البيئة',
    'phpunit.xml' => 'تكوين الاختبارات',
    'vite.config.js' => 'تكوين Vite',
    'tailwind.config.js' => 'تكوين Tailwind',
];

foreach ($configFiles as $file => $description) {
    if (file_exists(__DIR__.'/'.$file)) {
        echo "✅ {$file} - {$description}\n";
        $passed++;
    } else {
        echo "❌ {$file} - غير موجود\n";
        $failed++;
    }
}

// ========== 6. فحص DataTables ==========
echo "\n📊 فحص ملفات DataTables:\n";
echo "──────────────────────────────────────────\n";

$datatables = [
    'CustomersDataTable.php',
    'SuppliersDataTable.php',
    'ItemsDataTable.php',
    'SalesInvoicesDataTable.php',
    'PurchaseInvoicesDataTable.php',
];

$datatablePath = __DIR__.'/app/DataTables';
foreach ($datatables as $file) {
    if (file_exists($datatablePath.'/'.$file)) {
        echo "✅ {$file}\n";
        $passed++;
    } else {
        echo "❌ {$file} - غير موجود\n";
        $failed++;
    }
}

// ========== ملخص النتائج ==========
echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║                        📊 ملخص النتائج 📊                            ║\n";
echo "╠════════════════════════════════════════════════════════════════════════╣\n";
echo "║                                                                        ║\n";

$total = $passed + $failed;
$percentage = $total > 0 ? round(($passed / $total) * 100, 2) : 0;

echo "║  ✅ نجح: {$passed}\n";
echo "║  ❌ فشل: {$failed}\n";
echo "║  📊 الإجمالي: {$total}\n";
echo "║  📈 النسبة: {$percentage}%\n";
echo "║                                                                        ║\n";

if ($percentage >= 95) {
    echo "║  🟢 الحالة: ممتاز - المشروع جاهز للاختبار!                          ║\n";
} elseif ($percentage >= 80) {
    echo "║  🟡 الحالة: جيد - بحاجة لبعض الإصلاحات البسيطة                     ║\n";
} else {
    echo "║  🔴 الحالة: ضعيف - يحتاج إلى إصلاحات عديدة                         ║\n";
}

echo "║                                                                        ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";

// ========== نصائح إضافية ==========
echo "\n💡 نصائح للخطوات التالية:\n";
echo "──────────────────────────────────────────\n";

if ($failed === 0) {
    echo "✅ جميع الفحوصات نجحت! يمكنك الآن:\n";
    echo "   1. تشغيل الاختبارات: php artisan test\n";
    echo "   2. بدء التطبيق: php artisan serve\n";
    echo "   3. عرض التقارير المحاسبية\n";
} else {
    echo "⚠️  هناك بعض المشاكل التي تحتاج إلى الإصلاح:\n";
    echo "   1. تحقق من الملفات المفقودة\n";
    echo "   2. تأكد من تثبيت التبعيات: composer install\n";
    echo "   3. قم بتشغيل الترحيلات: php artisan migrate\n";
    echo "   4. تحقق من الأخطاء في قاعدة البيانات\n";
}

echo "\n";

exit($failed > 0 ? 1 : 0);
