<?php

namespace App\Services\Tenants;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SeedChartOfAccounts
{
    /**
     * Seed chart for ONE owner (tenant).
     */
    public function seedForOwner(User $owner): void
    {
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
                    'allow_posting' => false,
                    'status' => true,
                ]
            );
            $typeIds[$typeMeta['name']] = $type->id;
        }

        /**
         * ✅ Upsert ثابت:
         * - account_number ثابت => Update
         * - ما يغيرش current_balance لو موجود (علشان القيود)
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

        // 1.1 الأصول المتداولة (Group)
        $currentAssets = $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $assets->id,
            'name' => 'الأصول المتداولة',
            'account_number' => '1010',
        ]);

        // 1100 الصندوق
        $cash = $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $currentAssets->id,
            'name' => 'الصندوق',
            'account_number' => '1100',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $cash->id,
            'name' => 'خزينة رئيسية',
            'account_number' => '1101',
        ]);

        // لو عايز تفعّلها
        // $upsert([
        //     'account_type_id'   => $typeIds['الأصول'],
        //     'parent_account_id' => $cash->id,
        //     'name'              => 'خزينة فرع 1',
        //     'account_number'    => '1102',
        // ]);

        // 1110 البنوك
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

        // 1120 العملاء – Control
        $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $currentAssets->id,
            'name' => 'العملاء',
            'account_number' => '1120',
            'notes' => 'A/R Control',
        ]);

        // 1130 أوراق القبض
        $arNotes = $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $currentAssets->id,
            'name' => 'أوراق القبض',
            'account_number' => '1130',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $arNotes->id,
            'name' => 'شيكات تحت التحصيل',
            'account_number' => '1131',
        ]);

        // 1140 المخزون – Control
        $inventory = $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $currentAssets->id,
            'name' => 'المخزون',
            'account_number' => '1140',
            'notes' => 'Inventory Control',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $inventory->id,
            'name' => 'مخزون بضاعة (جاهزة للبيع)',
            'account_number' => '1141',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $inventory->id,
            'name' => 'مخزون مواد خام (لو مصنع)',
            'account_number' => '1142',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $inventory->id,
            'name' => 'مخزون تحت التشغيل (لو مصنع)',
            'account_number' => '1143',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $inventory->id,
            'name' => 'مخزون قطع غيار (لو ورشة/سيارات)',
            'account_number' => '1144',
        ]);

        // 1150 مصروفات مقدمة
        $prepaid = $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $currentAssets->id,
            'name' => 'مصروفات مقدمة / مدفوعات مقدمة',
            'account_number' => '1150',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $prepaid->id,
            'name' => 'إيجار مقدم',
            'account_number' => '1151',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $prepaid->id,
            'name' => 'تأمين مقدم',
            'account_number' => '1152',
        ]);

        // 1160 عهد/سُلف موظفين
        $advances = $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $currentAssets->id,
            'name' => 'عهد/سُلف موظفين',
            'account_number' => '1160',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $advances->id,
            'name' => 'عهدة: موظف 1',
            'account_number' => '1161',
        ]);

        // 1.2 الأصول غير المتداولة (Group)
        $nonCurrentAssets = $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $assets->id,
            'name' => 'الأصول غير المتداولة',
            'account_number' => '1020',
        ]);

        // 1200 الأصول الثابتة – Control
        $fixedAssets = $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $nonCurrentAssets->id,
            'name' => 'الأصول الثابتة',
            'account_number' => '1200',
            'notes' => 'Fixed Assets Control',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $fixedAssets->id,
            'name' => 'أجهزة ومعدات',
            'account_number' => '1201',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $fixedAssets->id,
            'name' => 'أثاث',
            'account_number' => '1202',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $fixedAssets->id,
            'name' => 'سيارات',
            'account_number' => '1203',
        ]);

        // 1210 مجمع الإهلاك – Contra
        $dep = $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $nonCurrentAssets->id,
            'name' => 'مجمع الإهلاك',
            'account_number' => '1210',
            'notes' => 'Accumulated Depreciation (Contra Asset)',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $dep->id,
            'name' => 'مجمع إهلاك الأجهزة',
            'account_number' => '1211',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الأصول'],
            'parent_account_id' => $dep->id,
            'name' => 'مجمع إهلاك الأثاث',
            'account_number' => '1212',
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

        // 2.1 الخصوم المتداولة (Group)
        $currentLiabilities = $upsert([
            'account_type_id' => $typeIds['الخصوم'],
            'parent_account_id' => $liabilities->id,
            'name' => 'الخصوم المتداولة',
            'account_number' => '2010',
        ]);

        // 2100 الموردين – Control
        $upsert([
            'account_type_id' => $typeIds['الخصوم'],
            'parent_account_id' => $currentLiabilities->id,
            'name' => 'الموردين',
            'account_number' => '2100',
            'notes' => 'A/P Control',
        ]);

        // 2110 أوراق الدفع
        $apNotes = $upsert([
            'account_type_id' => $typeIds['الخصوم'],
            'parent_account_id' => $currentLiabilities->id,
            'name' => 'أوراق الدفع',
            'account_number' => '2110',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الخصوم'],
            'parent_account_id' => $apNotes->id,
            'name' => 'شيكات صادرة',
            'account_number' => '2111',
        ]);

        // 2120 VAT – Control
        $vat = $upsert([
            'account_type_id' => $typeIds['الخصوم'],
            'parent_account_id' => $currentLiabilities->id,
            'name' => 'ضريبة القيمة المضافة VAT – Control',
            'account_number' => '2120',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الخصوم'],
            'parent_account_id' => $vat->id,
            'name' => 'VAT مخرجات (على المبيعات)',
            'account_number' => '2121',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الخصوم'],
            'parent_account_id' => $vat->id,
            'name' => 'VAT مدخلات (على المشتريات)',
            'account_number' => '2122',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الخصوم'],
            'parent_account_id' => $vat->id,
            'name' => 'صافي VAT مستحق/دائن',
            'account_number' => '2123',
        ]);

        // 2130 مصروفات مستحقة
        $accruedExp = $upsert([
            'account_type_id' => $typeIds['الخصوم'],
            'parent_account_id' => $currentLiabilities->id,
            'name' => 'مصروفات مستحقة',
            'account_number' => '2130',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الخصوم'],
            'parent_account_id' => $accruedExp->id,
            'name' => 'مرتبات مستحقة',
            'account_number' => '2131',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الخصوم'],
            'parent_account_id' => $accruedExp->id,
            'name' => 'كهرباء/مياه مستحقة',
            'account_number' => '2132',
        ]);

        // 2140 إيرادات مقدمة
        $deferredRev = $upsert([
            'account_type_id' => $typeIds['الخصوم'],
            'parent_account_id' => $currentLiabilities->id,
            'name' => 'إيرادات مقدمة',
            'account_number' => '2140',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الخصوم'],
            'parent_account_id' => $deferredRev->id,
            'name' => 'إيراد مقدم من عميل',
            'account_number' => '2141',
        ]);

        // 2.2 خصوم طويلة الأجل (Group)
        $ltLiabilities = $upsert([
            'account_type_id' => $typeIds['الخصوم'],
            'parent_account_id' => $liabilities->id,
            'name' => 'خصوم طويلة الأجل',
            'account_number' => '2020',
        ]);

        $loans = $upsert([
            'account_type_id' => $typeIds['الخصوم'],
            'parent_account_id' => $ltLiabilities->id,
            'name' => 'قروض',
            'account_number' => '2200',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الخصوم'],
            'parent_account_id' => $loans->id,
            'name' => 'قرض بنك',
            'account_number' => '2201',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الخصوم'],
            'parent_account_id' => $loans->id,
            'name' => 'تمويل طويل الأجل',
            'account_number' => '2202',
        ]);

        // =========================================================
        // 3) حقوق الملكية (Equity)
        // =========================================================
        $upsert([
            'account_type_id' => $typeIds['حقوق الملكية'],
            'parent_account_id' => null,
            'name' => 'رأس المال',
            'account_number' => '3100',
        ]);

        $upsert([
            'account_type_id' => $typeIds['حقوق الملكية'],
            'parent_account_id' => null,
            'name' => 'جاري الشريك/المالك',
            'account_number' => '3200',
        ]);

        $upsert([
            'account_type_id' => $typeIds['حقوق الملكية'],
            'parent_account_id' => null,
            'name' => 'الأرباح المحتجزة',
            'account_number' => '3300',
        ]);

        $upsert([
            'account_type_id' => $typeIds['حقوق الملكية'],
            'parent_account_id' => null,
            'name' => 'صافي ربح/خسارة الفترة',
            'account_number' => '3400',
        ]);

        // =========================================================
        // 4) الإيرادات (Revenues)
        // =========================================================
        $sales = $upsert([
            'account_type_id' => $typeIds['الإيرادات'],
            'parent_account_id' => null,
            'name' => 'المبيعات – Control',
            'account_number' => '4100',
            'notes' => 'Sales Control',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الإيرادات'],
            'parent_account_id' => $sales->id,
            'name' => 'مبيعات محلية',
            'account_number' => '4101',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الإيرادات'],
            'parent_account_id' => $sales->id,
            'name' => 'مبيعات تصدير',
            'account_number' => '4102',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الإيرادات'],
            'parent_account_id' => null,
            'name' => 'مردودات المبيعات (Contra)',
            'account_number' => '4110',
        ]);

        $services = $upsert([
            'account_type_id' => $typeIds['الإيرادات'],
            'parent_account_id' => null,
            'name' => 'إيرادات خدمات',
            'account_number' => '4200',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الإيرادات'],
            'parent_account_id' => $services->id,
            'name' => 'صيانة',
            'account_number' => '4201',
        ]);

        $upsert([
            'account_type_id' => $typeIds['الإيرادات'],
            'parent_account_id' => $services->id,
            'name' => 'تركيب',
            'account_number' => '4202',
        ]);

        // =========================================================
        // 5) تكلفة المبيعات (COGS)
        // =========================================================
        $cogs = $upsert([
            'account_type_id' => $typeIds['تكلفة المبيعات'],
            'parent_account_id' => null,
            'name' => 'تكلفة البضاعة المباعة',
            'account_number' => '5100',
        ]);

        $upsert([
            'account_type_id' => $typeIds['تكلفة المبيعات'],
            'parent_account_id' => $cogs->id,
            'name' => 'فروق جرد (زيادة/نقص)',
            'account_number' => '5110',
        ]);

        $purchaseExp = $upsert([
            'account_type_id' => $typeIds['تكلفة المبيعات'],
            'parent_account_id' => $cogs->id,
            'name' => 'مصاريف شراء',
            'account_number' => '5120',
        ]);

        $upsert([
            'account_type_id' => $typeIds['تكلفة المبيعات'],
            'parent_account_id' => $purchaseExp->id,
            'name' => 'نقل مشتريات',
            'account_number' => '5121',
        ]);

        $upsert([
            'account_type_id' => $typeIds['تكلفة المبيعات'],
            'parent_account_id' => $purchaseExp->id,
            'name' => 'جمارك',
            'account_number' => '5122',
        ]);

        // =========================================================
        // 6) المصروفات التشغيلية
        // =========================================================
        $selling = $upsert([
            'account_type_id' => $typeIds['المصروفات'],
            'parent_account_id' => null,
            'name' => 'مصروفات بيع وتسويق',
            'account_number' => '6100',
        ]);

        $upsert([
            'account_type_id' => $typeIds['المصروفات'],
            'parent_account_id' => $selling->id,
            'name' => 'رواتب المبيعات',
            'account_number' => '6101',
        ]);

        $upsert([
            'account_type_id' => $typeIds['المصروفات'],
            'parent_account_id' => $selling->id,
            'name' => 'عمولات',
            'account_number' => '6110',
        ]);

        $upsert([
            'account_type_id' => $typeIds['المصروفات'],
            'parent_account_id' => $selling->id,
            'name' => 'إعلانات وتسويق',
            'account_number' => '6120',
        ]);

        $upsert([
            'account_type_id' => $typeIds['المصروفات'],
            'parent_account_id' => $selling->id,
            'name' => 'مصروفات توصيل',
            'account_number' => '6130',
        ]);

        $admin = $upsert([
            'account_type_id' => $typeIds['المصروفات'],
            'parent_account_id' => null,
            'name' => 'مصروفات عمومية وإدارية',
            'account_number' => '6200',
        ]);

        $upsert([
            'account_type_id' => $typeIds['المصروفات'],
            'parent_account_id' => $admin->id,
            'name' => 'رواتب الإدارة',
            'account_number' => '6201',
        ]);

        $upsert([
            'account_type_id' => $typeIds['المصروفات'],
            'parent_account_id' => $admin->id,
            'name' => 'إيجارات',
            'account_number' => '6210',
        ]);

        $upsert([
            'account_type_id' => $typeIds['المصروفات'],
            'parent_account_id' => $admin->id,
            'name' => 'كهرباء ومياه',
            'account_number' => '6220',
        ]);

        $upsert([
            'account_type_id' => $typeIds['المصروفات'],
            'parent_account_id' => $admin->id,
            'name' => 'إنترنت واتصالات',
            'account_number' => '6230',
        ]);

        $upsert([
            'account_type_id' => $typeIds['المصروفات'],
            'parent_account_id' => $admin->id,
            'name' => 'صيانة',
            'account_number' => '6240',
        ]);

        $upsert([
            'account_type_id' => $typeIds['المصروفات'],
            'parent_account_id' => $admin->id,
            'name' => 'مستلزمات مكتبية',
            'account_number' => '6250',
        ]);

        $upsert([
            'account_type_id' => $typeIds['المصروفات'],
            'parent_account_id' => $admin->id,
            'name' => 'أتعاب محاسب/قانوني',
            'account_number' => '6260',
        ]);

        $upsert([
            'account_type_id' => $typeIds['المصروفات'],
            'parent_account_id' => $admin->id,
            'name' => 'إهلاكات',
            'account_number' => '6270',
        ]);

        // =========================================================
        // 7) أخرى
        // =========================================================
        $upsert([
            'account_type_id' => $typeIds['أخرى'],
            'parent_account_id' => null,
            'name' => 'إيرادات أخرى',
            'account_number' => '7100',
        ]);

        $upsert([
            'account_type_id' => $typeIds['أخرى'],
            'parent_account_id' => null,
            'name' => 'مصروفات أخرى',
            'account_number' => '7200',
        ]);

        $upsert([
            'account_type_id' => $typeIds['أخرى'],
            'parent_account_id' => null,
            'name' => 'أرباح/خسائر فروق عملة',
            'account_number' => '7300',
        ]);
    }

    /**
     * Seed for a specific ownerId.
     */
    public function seedForOwnerId(int $ownerId): void
    {
        DB::transaction(function () use ($ownerId) {
            $owner = User::whereNull('owner_user_id')->findOrFail($ownerId);
            $this->seedForOwner($owner);
        });
    }
}
