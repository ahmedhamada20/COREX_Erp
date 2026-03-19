<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $owners = User::whereNull('owner_user_id')->orderBy('id')->get();

            if ($owners->isEmpty()) {
                $this->command?->warn('No owner user found. Seed UsersSeeder first.');

                return;
            }

            foreach ($owners as $owner) {

                // ==============================
                // Account Types
                // ==============================
                $types = [
                    ['name' => 'الأصول', 'code' => 'AST', 'normal_side' => 'debit'],
                    ['name' => 'الخصوم', 'code' => 'LIA', 'normal_side' => 'credit'],
                    ['name' => 'حقوق الملكية', 'code' => 'EQT', 'normal_side' => 'credit'],
                    ['name' => 'الإيرادات', 'code' => 'REV', 'normal_side' => 'credit'],
                    ['name' => 'المصروفات', 'code' => 'EXP', 'normal_side' => 'debit'],
                    ['name' => 'تكلفة المبيعات', 'code' => 'COG', 'normal_side' => 'debit'],
                    ['name' => 'أخرى', 'code' => 'OTH', 'normal_side' => 'debit'],
                ];

                $typeIds = [];
                foreach ($types as $typeMeta) {
                    $type = AccountType::updateOrCreate(
                        ['user_id' => $owner->id, 'name' => $typeMeta['name']],
                        [
                            'code' => $typeMeta['code'],
                            'normal_side' => $typeMeta['normal_side'],
                            'status' => true,
                            'allow_posting' => false,
                        ]
                    );
                    $typeIds[$typeMeta['name']] = $type->id;
                }

                /**
                 * ✅ Upsert ثابت:
                 * - نفس رقم الحساب = Update
                 * - لا يغيّر current_balance إن كان موجود
                 */
                $upsert = function (array $data) use ($owner) {

                    $start = (float) ($data['start_balance'] ?? 0);

                    $existing = Account::query()
                        ->where('user_id', $owner->id)
                        ->where('account_number', (string) $data['account_number'])
                        ->first();

                    return Account::updateOrCreate(
                        [
                            'user_id' => $owner->id,
                            'account_number' => (string) $data['account_number'],
                        ],
                        [
                            'account_type_id' => $data['account_type_id'],
                            'parent_account_id' => $data['parent_account_id'] ?? null,
                            'name' => $data['name'],
                            'start_balance' => $start,
                            'current_balance' => $existing?->current_balance ?? ($data['current_balance'] ?? $start),
                            'status' => $data['status'] ?? true,
                            'notes' => $data['notes'] ?? null,
                            'date' => $data['date'] ?? now()->toDateString(),
                            'updated_by' => $owner->name,
                        ]
                    );
                };

                // =========================================================
                // 1) الأصول (Assets)
                // =========================================================
                $assets = $upsert([
                    'account_type_id' => $typeIds['الأصول'],
                    'parent_account_id' => null,
                    'name' => 'الأصول',
                    'account_number' => '1000',
                ]);

                $currentAssets = $upsert([
                    'account_type_id' => $typeIds['الأصول'],
                    'parent_account_id' => $assets->id,
                    'name' => 'الأصول المتداولة',
                    'account_number' => '1010',
                ]);

                // 1100 الصندوق (Group)
                $cashGroup = $upsert([
                    'account_type_id' => $typeIds['الأصول'],
                    'parent_account_id' => $currentAssets->id,
                    'name' => 'الصندوق',
                    'account_number' => '1100',
                    'notes' => 'Cash Group',
                ]);

                // 1101 خزنة نقدي
                $upsert([
                    'account_type_id' => $typeIds['الأصول'],
                    'parent_account_id' => $cashGroup->id,
                    'name' => 'خزنة نقدي (Cash Drawer)',
                    'account_number' => '1101',
                    'notes' => 'Cash on Hand / POS Drawer',
                ]);

                // 1114 تحصيل بطاقات
                $upsert([
                    'account_type_id' => $typeIds['الأصول'],
                    'parent_account_id' => $cashGroup->id,
                    'name' => 'تحصيل بطاقات (Card Clearing)',
                    'account_number' => '1114',
                    'notes' => 'Card Clearing',
                ]);

                // 1115 تحصيل محافظ
                $upsert([
                    'account_type_id' => $typeIds['الأصول'],
                    'parent_account_id' => $cashGroup->id,
                    'name' => 'تحصيل محافظ (Wallet Clearing)',
                    'account_number' => '1115',
                    'notes' => 'Wallet Clearing',
                ]);

                // 1110 البنوك (Group)
                $banks = $upsert([
                    'account_type_id' => $typeIds['الأصول'],
                    'parent_account_id' => $currentAssets->id,
                    'name' => 'البنوك',
                    'account_number' => '1110',
                ]);

                $upsert([
                    'account_type_id' => $typeIds['الأصول'],
                    'parent_account_id' => $banks->id,
                    'name' => 'بنك CIB',
                    'account_number' => '1111',
                ]);

                $upsert([
                    'account_type_id' => $typeIds['الأصول'],
                    'parent_account_id' => $banks->id,
                    'name' => 'بنك الأهلي',
                    'account_number' => '1112',
                ]);

                // 1120 العملاء A/R Control
                $arControl = $upsert([
                    'account_type_id' => $typeIds['الأصول'],
                    'parent_account_id' => $currentAssets->id,
                    'name' => 'العملاء (A/R Control)',
                    'account_number' => '1120',
                    'notes' => 'A/R Control',
                ]);

                // (اختياري) Parent لحسابات العملاء الفرعية تحت 1120
                $upsert([
                    'account_type_id' => $typeIds['الأصول'],
                    'parent_account_id' => $arControl->id,
                    'name' => 'حسابات العملاء الفرعية (Sub-ledger)',
                    'account_number' => '1121',
                    'notes' => 'Customers Sub-ledger Parent',
                ]);

                // 1140 المخزون Control
                $inventoryControl = $upsert([
                    'account_type_id' => $typeIds['الأصول'],
                    'parent_account_id' => $currentAssets->id,
                    'name' => 'المخزون (Control)',
                    'account_number' => '1140',
                    'notes' => 'Inventory Control',
                ]);

                // 1141 مخزون بضاعة (Transactional)
                $upsert([
                    'account_type_id' => $typeIds['الأصول'],
                    'parent_account_id' => $inventoryControl->id,
                    'name' => 'مخزون بضاعة (جاهزة للبيع)',
                    'account_number' => '1141',
                    'notes' => 'Inventory (Merchandise)',
                ]);

                // =========================================================
                // 2) الخصوم (Liabilities)
                // =========================================================
                $liabilities = $upsert([
                    'account_type_id' => $typeIds['الخصوم'],
                    'parent_account_id' => null,
                    'name' => 'الخصوم',
                    'account_number' => '2000',
                ]);

                $currentLiabilities = $upsert([
                    'account_type_id' => $typeIds['الخصوم'],
                    'parent_account_id' => $liabilities->id,
                    'name' => 'الخصوم المتداولة',
                    'account_number' => '2010',
                ]);

                // 2100 الموردين A/P Control
                $upsert([
                    'account_type_id' => $typeIds['الخصوم'],
                    'parent_account_id' => $currentLiabilities->id,
                    'name' => 'الموردين (A/P Control)',
                    'account_number' => '2100',
                    'notes' => 'A/P Control',
                ]);

                // 2120 VAT Control
                $vatControl = $upsert([
                    'account_type_id' => $typeIds['الخصوم'],
                    'parent_account_id' => $currentLiabilities->id,
                    'name' => 'ضريبة القيمة المضافة VAT – Control',
                    'account_number' => '2120',
                ]);

                $upsert([
                    'account_type_id' => $typeIds['الخصوم'],
                    'parent_account_id' => $vatControl->id,
                    'name' => 'VAT مخرجات (على المبيعات)',
                    'account_number' => '2121',
                    'notes' => 'VAT Output',
                ]);

                $upsert([
                    'account_type_id' => $typeIds['الخصوم'],
                    'parent_account_id' => $vatControl->id,
                    'name' => 'VAT مدخلات (على المشتريات)',
                    'account_number' => '2122',
                    'notes' => 'VAT Input',
                ]);

                // =========================================================
                // 3) حقوق الملكية (Equity)
                // =========================================================
                $equityRoot = $upsert([
                    'account_type_id' => $typeIds['حقوق الملكية'],
                    'parent_account_id' => null,
                    'name' => 'حقوق الملكية',
                    'account_number' => '3000',
                    'notes' => 'Equity Root',
                ]);

                // 3900 Opening Balance Offset
                $upsert([
                    'account_type_id' => $typeIds['حقوق الملكية'],
                    'parent_account_id' => $equityRoot->id,
                    'name' => 'رصيد افتتاحي (Opening Balance Offset)',
                    'account_number' => '3900',
                    'notes' => 'Opening Balance Offset',
                ]);

                $upsert([
                    'account_type_id' => $typeIds['حقوق الملكية'],
                    'parent_account_id' => $equityRoot->id,
                    'name' => 'رأس المال',
                    'account_number' => '3100',
                ]);

                $upsert([
                    'account_type_id' => $typeIds['حقوق الملكية'],
                    'parent_account_id' => $equityRoot->id,
                    'name' => 'جاري الشريك/المالك',
                    'account_number' => '3200',
                ]);

                $upsert([
                    'account_type_id' => $typeIds['حقوق الملكية'],
                    'parent_account_id' => $equityRoot->id,
                    'name' => 'الأرباح المحتجزة',
                    'account_number' => '3300',
                ]);

                $upsert([
                    'account_type_id' => $typeIds['حقوق الملكية'],
                    'parent_account_id' => $equityRoot->id,
                    'name' => 'صافي ربح/خسارة الفترة',
                    'account_number' => '3400',
                ]);

                // =========================================================
                // 4) الإيرادات (Revenues)
                // =========================================================
                $revenueRoot = $upsert([
                    'account_type_id' => $typeIds['الإيرادات'],
                    'parent_account_id' => null,
                    'name' => 'الإيرادات',
                    'account_number' => '4000',
                    'notes' => 'Revenue Root',
                ]);

                // 4100 Sales Revenue (Transactional/Control)
                $sales = $upsert([
                    'account_type_id' => $typeIds['الإيرادات'],
                    'parent_account_id' => $revenueRoot->id,
                    'name' => 'المبيعات (Sales Revenue)',
                    'account_number' => '4100',
                    'notes' => 'Sales Revenue',
                ]);

                // ✅ 4110 Sales Returns (Contra) - تحت 4100 (تصحيح)
                $upsert([
                    'account_type_id' => $typeIds['الإيرادات'],
                    'parent_account_id' => $sales->id,
                    'name' => 'مردودات المبيعات (Sales Returns)',
                    'account_number' => '4110',
                    'notes' => 'Contra Revenue - Sales Returns',
                ]);

                // ✅ 4120 Sales Discount Allowed (Contra) - جديد
                $upsert([
                    'account_type_id' => $typeIds['الإيرادات'],
                    'parent_account_id' => $sales->id,
                    'name' => 'خصم مسموح به (Sales Discount)',
                    'account_number' => '4120',
                    'notes' => 'Contra Revenue - Sales Discount Allowed',
                ]);

                // =========================================================
                // 5) تكلفة المبيعات (COGS)
                // =========================================================
                $cogsRoot = $upsert([
                    'account_type_id' => $typeIds['تكلفة المبيعات'],
                    'parent_account_id' => null,
                    'name' => 'تكلفة المبيعات',
                    'account_number' => '5000',
                    'notes' => 'COGS Root',
                ]);

                // 5100 COGS (Transactional)
                $cogs = $upsert([
                    'account_type_id' => $typeIds['تكلفة المبيعات'],
                    'parent_account_id' => $cogsRoot->id,
                    'name' => 'تكلفة البضاعة المباعة (COGS)',
                    'account_number' => '5100',
                    'notes' => 'COGS',
                ]);

                // 5110 فروق جرد (اختياري)
                $upsert([
                    'account_type_id' => $typeIds['تكلفة المبيعات'],
                    'parent_account_id' => $cogs->id,
                    'name' => 'فروق جرد (زيادة/نقص)',
                    'account_number' => '5110',
                ]);

                // 5120 مصاريف شراء (Purchase Charges)
                $upsert([
                    'account_type_id' => $typeIds['تكلفة المبيعات'],
                    'parent_account_id' => $cogs->id,
                    'name' => 'مصاريف شراء (Purchase Charges)',
                    'account_number' => '5120',
                    'notes' => 'Purchase Charges',
                ]);

                // 5121 نقل مشتريات (Purchase Freight)
                $upsert([
                    'account_type_id' => $typeIds['تكلفة المبيعات'],
                    'parent_account_id' => $cogs->id,
                    'name' => 'نقل مشتريات (Purchase Freight)',
                    'account_number' => '5121',
                    'notes' => 'Purchase Freight',
                ]);

                // =========================================================
                // 6) المصروفات (Expenses) - أساسيات (اختياري لكن مفيد)
                // =========================================================
                $expensesRoot = $upsert([
                    'account_type_id' => $typeIds['المصروفات'],
                    'parent_account_id' => null,
                    'name' => 'المصروفات',
                    'account_number' => '6000',
                    'notes' => 'Expenses Root',
                ]);

                $upsert([
                    'account_type_id' => $typeIds['المصروفات'],
                    'parent_account_id' => $expensesRoot->id,
                    'name' => 'مصروفات تشغيلية',
                    'account_number' => '6100',
                ]);

                $upsert([
                    'account_type_id' => $typeIds['المصروفات'],
                    'parent_account_id' => $expensesRoot->id,
                    'name' => 'مصروفات إدارية',
                    'account_number' => '6200',
                ]);
            }
        });
    }
}
