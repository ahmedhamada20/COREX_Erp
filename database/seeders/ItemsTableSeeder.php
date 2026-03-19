<?php

namespace Database\Seeders;

use App\Models\ItemCategory;
use App\Models\Items;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ItemsTableSeeder extends Seeder
{
    public function run(): void
    {
        $countPerOwner = 200;
        $now = now();
        $faker = fake();

        // ✅ Owners فقط
        $owners = User::query()
            ->whereNull('owner_user_id')
            ->select('id', 'name')
            ->get();

        if ($owners->isEmpty()) {
            $this->command?->warn('No owners found. Seed users first.');

            return;
        }

        // ✅ Reset images مرة واحدة
        if (Storage::disk('public')->exists('items')) {
            Storage::disk('public')->deleteDirectory('items');
        }
        Storage::disk('public')->makeDirectory('items');

        $types = ['store', 'consumption', 'custody'];

        $parentUnits = ['CTN', 'BOX', 'PK'];
        $retailUnits = ['PCS', 'UNT'];       // Piece/Unit

        $retailRatio = 0.35; // 35% من الأصناف لها تجزئة
        $qtyChoices = [6, 12, 24];

        $round2 = fn ($v) => round((float) $v, 2);

        $makeBarcode = function (): string {
            return (string) random_int(1000000000000, 9999999999999);
        };

        $makeImageForItem = function (string $itemsCode, string $name): string {
            $hash = substr(md5($itemsCode), 0, 6);
            $bg = "#{$hash}";

            $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $safeCode = htmlspecialchars($itemsCode, ENT_QUOTES, 'UTF-8');

            $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="600" height="600">
  <defs>
    <style>
      .title { font: 700 26px Arial, sans-serif; fill: #fff; }
      .sub { font: 400 18px Arial, sans-serif; fill: #f2f2f2; }
    </style>
  </defs>
  <rect width="100%" height="100%" fill="{$bg}"/>
  <circle cx="480" cy="120" r="70" fill="rgba(255,255,255,0.18)"/>
  <circle cx="120" cy="460" r="90" fill="rgba(255,255,255,0.12)"/>
  <text x="50%" y="45%" text-anchor="middle" class="title">{$safeName}</text>
  <text x="50%" y="53%" text-anchor="middle" class="sub">{$safeCode}</text>
  <text x="50%" y="90%" text-anchor="middle" class="sub">COREX ERP</text>
</svg>
SVG;

            $fileName = 'items/'.Str::uuid().'.svg';
            Storage::disk('public')->put($fileName, $svg);

            return $fileName;
        };

        foreach ($owners as $owner) {

            // ✅ Categories الخاصة بالـ owner فقط
            $categoryIds = ItemCategory::query()
                ->where('user_id', $owner->id)
                ->pluck('id')
                ->values();

            if ($categoryIds->isEmpty()) {
                $this->command?->warn("No item categories for owner #{$owner->id}. Seed item_categories first.");

                continue;
            }

            DB::transaction(function () use (
                $owner,
                $categoryIds,
                $countPerOwner,
                $types,
                $parentUnits,
                $retailUnits,
                $retailRatio,
                $qtyChoices,
                $now,
                $faker,
                $round2,
                $makeBarcode,
                $makeImageForItem
            ) {
                for ($i = 1; $i <= $countPerOwner; $i++) {

                    $hasRetail = (random_int(1, 100) <= (int) ($retailRatio * 100));
                    $qtyToParent = $hasRetail ? $qtyChoices[array_rand($qtyChoices)] : null;

                    $parentUnitCode = $parentUnits[array_rand($parentUnits)];
                    $retailUnitCode = $retailUnits[array_rand($retailUnits)];

                    // ✅ كود مميز + مربوط بالـ owner
                    $parentCode = 'ITM-'.$owner->id.'-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT);
                    $childCode = $parentCode.'-R';

                    $type = $types[array_rand($types)];
                    $categoryId = $categoryIds[random_int(0, $categoryIds->count() - 1)];

                    // ✅ سعر الأب
                    $baseRetailPrice = random_int(500, 25000) / 100; // 5 -> 250
                    $baseNosGomla = $baseRetailPrice * (random_int(92, 97) / 100);
                    $baseGomla = $baseRetailPrice * (random_int(85, 92) / 100);

                    $baseRetailPrice = $round2($baseRetailPrice);
                    $baseNosGomla = $round2($baseNosGomla);
                    $baseGomla = $round2($baseGomla);

                    // ✅ سعر التجزئة (مشتق من الأب لو فيه retail)
                    if ($hasRetail && $qtyToParent) {
                        $priceRetail = $round2($baseRetailPrice / $qtyToParent);
                        $nosRetail = $round2($baseNosGomla / $qtyToParent);
                        $gomlaRetail = $round2($baseGomla / $qtyToParent);
                    } else {
                        $priceRetail = $baseRetailPrice;
                        $nosRetail = $baseNosGomla;
                        $gomlaRetail = $baseGomla;
                    }

                    $parentName = $faker->unique()->words(3, true);
                    $parentImage = $makeImageForItem($parentCode, $parentName);

                    // ✅ Upsert (منع تكرار عند إعادة تشغيل السيدر)
                    $parent = Items::updateOrCreate(
                        ['user_id' => $owner->id, 'items_code' => $parentCode],
                        [
                            'barcode' => $makeBarcode(),
                            'name' => $parentName,

                            'price' => $baseRetailPrice,
                            'nos_egomania_price' => $baseNosGomla,
                            'egomania_price' => $baseGomla,

                            'price_retail' => $priceRetail,
                            'nos_gomla_price_retail' => $nosRetail,
                            'gomla_price_retail' => $gomlaRetail,

                            'type' => $type,
                            'item_category_id' => $categoryId,
                            'item_id' => null,

                            'does_has_retail_unit' => $hasRetail,
                            'retail_unit' => $hasRetail ? $retailUnitCode : null,
                            'unit_id' => $parentUnitCode,
                            'retail_uom_quintToParent' => $hasRetail ? $qtyToParent : null,

                            'status' => true,
                            'date' => $now->toDateString(),
                            'image' => $parentImage,
                            'updated_by' => $owner->name,
                        ]
                    );

                    // ✅ child retail item
                    if ($hasRetail) {
                        $childName = $parentName.' (Retail)';
                        $childImage = $makeImageForItem($childCode, $childName);

                        Items::updateOrCreate(
                            ['user_id' => $owner->id, 'items_code' => $childCode],
                            [
                                'barcode' => $makeBarcode(),
                                'name' => $childName,

                                // هذا الصنف يعتبر "تجزئة" كوحدة أساسية له
                                'price' => $priceRetail,
                                'nos_egomania_price' => $nosRetail,
                                'egomania_price' => $gomlaRetail,

                                'price_retail' => $priceRetail,
                                'nos_gomla_price_retail' => $nosRetail,
                                'gomla_price_retail' => $gomlaRetail,

                                'type' => $type,
                                'item_category_id' => $categoryId,
                                'item_id' => $parent->id,

                                'does_has_retail_unit' => false,
                                'retail_unit' => null,
                                'unit_id' => $retailUnitCode,
                                'retail_uom_quintToParent' => 1,

                                'status' => true,
                                'date' => $now->toDateString(),
                                'image' => $childImage,
                                'updated_by' => $owner->name,
                            ]
                        );
                    }
                }
            });

            $this->command?->info("✅ Seeded {$countPerOwner} items for owner #{$owner->id} ({$owner->name}) + retail children.");
        }
    }
}
