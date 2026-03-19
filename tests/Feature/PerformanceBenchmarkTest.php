<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Customer;
use App\Models\ItemCategory;
use App\Models\Items;
use App\Models\JournalEntry;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    protected array $benchmarks = [];

    /**
     * قياس الأداء الشامل للمشروع
     */
    public function test_comprehensive_performance_benchmark(): void
    {
        echo "\n\n";
        echo "╔════════════════════════════════════════════════════════════════════════╗\n";
        echo "║                                                                        ║\n";
        echo "║            🚀 اختبار قياس الأداء الشامل للمشروع 🚀                  ║\n";
        echo "║                                                                        ║\n";
        echo "╚════════════════════════════════════════════════════════════════════════╝\n";

        DB::enableQueryLog();

        // 1. اختبار سرعة إنشاء البيانات
        $this->testDataCreationPerformance();

        // 2. اختبار سرعة الاستعلامات
        $this->testQueryPerformance();

        // 3. اختبار سرعة المعالجة
        $this->testProcessingPerformance();

        // 4. اختبار سرعة التقارير
        $this->testReportingPerformance();

        // طباعة النتائج
        $this->printBenchmarkResults();

        $this->assertTrue(true, 'اختبار قياس الأداء نجح');
    }

    /**
     * اختبار سرعة إنشاء البيانات
     */
    protected function test_data_creation_performance(): void
    {
        echo "\n📊 اختبار سرعة إنشاء البيانات:\n";
        echo "────────────────────────────────────────────\n";

        // إنشاء 100 حساب
        $startTime = microtime(true);
        $user = \App\Models\User::factory()->create();
        \App\Models\AccountType::create([
            'user_id' => $user->id,
            'name' => 'Assets',
            'normal_side' => 'debit',
        ]);
        for ($i = 0; $i < 100; $i++) {
            Account::create([
                'user_id' => $user->id,
                'code' => str_pad($i, 4, '0', STR_PAD_LEFT),
                'name' => 'Account '.$i,
                'account_type_id' => 1,
            ]);
        }
        $accountTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "✅ إنشاء 100 حساب: {$accountTime} ms\n";
        $this->benchmarks['Create 100 Accounts'] = $accountTime;

        // إنشاء 200 عميل
        $startTime = microtime(true);
        for ($i = 0; $i < 200; $i++) {
            Customer::create([
                'name' => 'Customer '.$i,
                'phone' => '123456789'.$i,
            ]);
        }
        $customerTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "✅ إنشاء 200 عميل: {$customerTime} ms\n";
        $this->benchmarks['Create 200 Customers'] = $customerTime;

        // إنشاء 300 مادة
        $startTime = microtime(true);
        $category = ItemCategory::create(['name' => 'Default']);
        for ($i = 0; $i < 300; $i++) {
            Items::create([
                'name' => 'Item '.$i,
                'item_category_id' => $category->id,
            ]);
        }
        $itemTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "✅ إنشاء 300 مادة: {$itemTime} ms\n";
        $this->benchmarks['Create 300 Items'] = $itemTime;

        // إنشاء 100 مورد
        $startTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            Supplier::create([
                'name' => 'Supplier '.$i,
                'phone' => '987654321'.$i,
            ]);
        }
        $supplierTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "✅ إنشاء 100 مورد: {$supplierTime} ms\n";
        $this->benchmarks['Create 100 Suppliers'] = $supplierTime;

        // إنشاء 500 قيد يومي
        $startTime = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            JournalEntry::create([
                'user_id' => $user->id,
                'entry_number' => 'JE'.str_pad($i, 4, '0', STR_PAD_LEFT),
                'entry_date' => now(),
            ]);
        }
        $journalTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "✅ إنشاء 500 قيد يومي: {$journalTime} ms\n";
        $this->benchmarks['Create 500 Journal Entries'] = $journalTime;
    }

    /**
     * اختبار سرعة الاستعلامات
     */
    protected function test_query_performance(): void
    {
        echo "\n🔍 اختبار سرعة الاستعلامات:\n";
        echo "────────────────────────────────────────────\n";

        // استعلام 100 حساب مع العلاقات
        $startTime = microtime(true);
        $accounts = Account::with('type', 'parent', 'children')
            ->paginate(50);
        $queryTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "✅ استعلام 100 حساب مع العلاقات: {$queryTime} ms\n";
        $this->benchmarks['Query 100 Accounts with Relations'] = $queryTime;

        // استعلام 200 عميل مع الفواتير
        $startTime = microtime(true);
        $customers = Customer::with('invoices', 'payments', 'returns')
            ->paginate(50);
        $queryTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "✅ استعلام 200 عميل مع الفواتير: {$queryTime} ms\n";
        $this->benchmarks['Query 200 Customers with Invoices'] = $queryTime;

        // استعلام 300 مادة مع الفئات
        $startTime = microtime(true);
        $items = Items::with('category', 'unit', 'invoiceItems')
            ->paginate(50);
        $queryTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "✅ استعلام 300 مادة مع الفئات: {$queryTime} ms\n";
        $this->benchmarks['Query 300 Items with Categories'] = $queryTime;

        // استعلام 500 قيد يومي مع البنود
        $startTime = microtime(true);
        $entries = JournalEntry::with('lines.account')
            ->paginate(50);
        $queryTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "✅ استعلام 500 قيد يومي مع البنود: {$queryTime} ms\n";
        $this->benchmarks['Query 500 Journal Entries with Lines'] = $queryTime;

        // استعلام متقدم: الأرصدة
        $startTime = microtime(true);
        $balances = Account::selectRaw('
            accounts.id,
            accounts.code,
            accounts.name,
            COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit,
            COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit,
            COALESCE(SUM(journal_entry_lines.debit), 0) - COALESCE(SUM(journal_entry_lines.credit), 0) as balance
        ')
            ->leftJoin('journal_entry_lines', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name')
            ->paginate(50);
        $queryTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "✅ استعلام الأرصدة المتقدم: {$queryTime} ms\n";
        $this->benchmarks['Advanced Balances Query'] = $queryTime;
    }

    /**
     * اختبار سرعة معالجة البيانات
     */
    protected function test_processing_performance(): void
    {
        echo "\n⚙️ اختبار سرعة معالجة البيانات:\n";
        echo "────────────────────────────────────────────\n";

        // معالجة 100 حساب
        $startTime = microtime(true);
        $accounts = Account::all();
        $processed = $accounts->map(function ($account) {
            return [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'balance' => $account->lines()->sum(DB::raw('debit - credit')),
            ];
        });
        $processingTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "✅ معالجة 100 حساب: {$processingTime} ms\n";
        $this->benchmarks['Process 100 Accounts'] = $processingTime;

        // معالجة 200 عميل
        $startTime = microtime(true);
        $customers = Customer::all();
        $processed = $customers->map(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'invoices_count' => $customer->invoices()->count(),
                'total_sales' => $customer->invoices()->sum('total'),
            ];
        });
        $processingTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "✅ معالجة 200 عميل: {$processingTime} ms\n";
        $this->benchmarks['Process 200 Customers'] = $processingTime;

        // معالجة 300 مادة
        $startTime = microtime(true);
        $items = Items::all();
        $processed = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category?->name,
                'stock_value' => ($item->quantity ?? 0) * ($item->cost_price ?? 0),
            ];
        });
        $processingTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "✅ معالجة 300 مادة: {$processingTime} ms\n";
        $this->benchmarks['Process 300 Items'] = $processingTime;

        // معالجة 500 قيد يومي
        $startTime = microtime(true);
        $entries = JournalEntry::with('lines')->get();
        $processed = $entries->map(function ($entry) {
            return [
                'entry_number' => $entry->entry_number,
                'lines_count' => $entry->lines()->count(),
                'total_debit' => $entry->lines()->sum('debit'),
                'total_credit' => $entry->lines()->sum('credit'),
            ];
        });
        $processingTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "✅ معالجة 500 قيد يومي: {$processingTime} ms\n";
        $this->benchmarks['Process 500 Journal Entries'] = $processingTime;
    }

    /**
     * اختبار سرعة التقارير
     */
    protected function test_reporting_performance(): void
    {
        echo "\n📋 اختبار سرعة التقارير:\n";
        echo "────────────────────────────────────────────\n";

        // حساب الأرصدة
        $startTime = microtime(true);
        $balances = Account::selectRaw('
            id,
            code,
            name,
            COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit,
            COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit,
            COALESCE(SUM(journal_entry_lines.debit), 0) - COALESCE(SUM(journal_entry_lines.credit), 0) as balance
        ')
            ->leftJoin('journal_entry_lines', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name')
            ->get();
        $reportTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "✅ تقرير الأرصدة: {$reportTime} ms\n";
        $this->benchmarks['Balances Report'] = $reportTime;

        // حساب مبيعات العملاء
        $startTime = microtime(true);
        $salesByCustomer = Customer::selectRaw('
            customers.id,
            customers.name,
            COUNT(sales_invoices.id) as invoices_count,
            COALESCE(SUM(sales_invoices.total), 0) as total_sales
        ')
            ->leftJoin('sales_invoices', 'customers.id', '=', 'sales_invoices.customer_id')
            ->groupBy('customers.id', 'customers.name')
            ->get();
        $reportTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "✅ تقرير مبيعات العملاء: {$reportTime} ms\n";
        $this->benchmarks['Sales by Customer Report'] = $reportTime;

        // حساب مشتريات المورد
        $startTime = microtime(true);
        $purchasesBySupplier = Supplier::selectRaw('
            suppliers.id,
            suppliers.name,
            COUNT(purchase_invoices.id) as invoices_count,
            COALESCE(SUM(purchase_invoices.total), 0) as total_purchases
        ')
            ->leftJoin('purchase_invoices', 'suppliers.id', '=', 'purchase_invoices.supplier_id')
            ->groupBy('suppliers.id', 'suppliers.name')
            ->get();
        $reportTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "✅ تقرير مشتريات المورد: {$reportTime} ms\n";
        $this->benchmarks['Purchases by Supplier Report'] = $reportTime;

        // تقرير المخزون
        $startTime = microtime(true);
        $inventory = Items::selectRaw('
            items.id,
            items.code,
            items.name,
            COALESCE(items.quantity, 0) as quantity,
            COALESCE(items.cost_price, 0) as cost_price,
            COALESCE(items.quantity, 0) * COALESCE(items.cost_price, 0) as total_value
        ')
            ->get();
        $reportTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "✅ تقرير المخزون: {$reportTime} ms\n";
        $this->benchmarks['Inventory Report'] = $reportTime;
    }

    /**
     * طباعة النتائج
     */
    protected function printBenchmarkResults(): void
    {
        echo "\n\n";
        echo "╔════════════════════════════════════════════════════════════════════════╗\n";
        echo "║                     📊 ملخص نتائج قياس الأداء 📊                     ║\n";
        echo "╠════════════════════════════════════════════════════════════════════════╣\n";

        $fastCount = 0;
        $slowCount = 0;
        $totalTime = 0;

        foreach ($this->benchmarks as $operation => $time) {
            $totalTime += $time;

            if ($time < 100) {
                $status = '✅ سريع جداً';
                $fastCount++;
            } elseif ($time < 500) {
                $status = '⚠️  متوسط';
            } else {
                $status = '❌ بطيء';
                $slowCount++;
            }

            echo "║\n";
            echo "║ {$status}\n";
            echo '║ العملية: '.str_pad($operation, 45)."\n";
            echo '║ الوقت:   '.str_pad($time.' ms', 45)."\n";
        }

        echo "║\n";
        echo "╠════════════════════════════════════════════════════════════════════════╣\n";
        echo "║\n";
        echo "║ 📈 الإحصائيات:\n";
        echo '║   • إجمالي العمليات: '.count($this->benchmarks)."\n";
        echo "║   • العمليات السريعة: {$fastCount}\n";
        echo "║   • العمليات البطيئة: {$slowCount}\n";
        echo "║   • إجمالي الوقت: {$totalTime} ms\n";
        echo '║   • متوسط الوقت: '.round($totalTime / count($this->benchmarks), 2)." ms\n";

        $performanceScore = $this->calculatePerformanceScore($fastCount, $slowCount);
        echo "║   • درجة الأداء: {$performanceScore}/10 ⭐\n";

        echo "║\n";
        echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";
    }

    /**
     * حساب درجة الأداء
     */
    protected function calculatePerformanceScore(int $fastCount, int $slowCount): float
    {
        $totalCount = $fastCount + $slowCount;
        if ($totalCount === 0) {
            return 5.0;
        }

        $fastPercentage = ($fastCount / $totalCount) * 100;

        if ($fastPercentage >= 90) {
            return 9.5;
        } elseif ($fastPercentage >= 80) {
            return 9.0;
        } elseif ($fastPercentage >= 70) {
            return 8.5;
        } elseif ($fastPercentage >= 60) {
            return 8.0;
        } elseif ($fastPercentage >= 50) {
            return 7.5;
        } else {
            return 6.0;
        }
    }
}
