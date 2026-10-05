<?php

namespace Database\Seeders;

use App\Models\Lookup;
use Illuminate\Database\Seeder;

class LookupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            Lookup::TYPE_QUOTE_TYPE => [
                ['code' => 'batteries_broiler_eg', 'label_ar' => 'بطاريات فقط تسمين مصري', 'value' => 'batteries_only_broiler_eg'],
            ],
            Lookup::TYPE_MANURE_MOTOR_COUNT => [
                ['code' => '1', 'label_ar' => '1 ماتور', 'value' => '1'],
                ['code' => '2', 'label_ar' => '2 ماتور', 'value' => '2'],
                ['code' => '3', 'label_ar' => '3 ماتور', 'value' => '3'],
            ],
            Lookup::TYPE_MOTOR_POWER => [
                ['code' => '1_5_hp', 'label_ar' => '1.5 حصان', 'value' => '1.5'],
            ],
            Lookup::TYPE_BELTS_PER_LINE => [
                ['code' => '3', 'label_ar' => '3 سيور', 'value' => '3'],
                ['code' => '4', 'label_ar' => '4 سيور', 'value' => '4'],
                ['code' => '5', 'label_ar' => '5 سيور', 'value' => '5'],
            ],
            Lookup::TYPE_INNER_BELT_LENGTH => [
                ['code' => '12', 'label_ar' => '12 متر', 'value' => '12'],
            ],
            Lookup::TYPE_OUTER_BELT_LENGTH => [
                ['code' => '8', 'label_ar' => '8 متر', 'value' => '8'],
            ],
            Lookup::TYPE_SILO_CAPACITY => [
                ['code' => '11', 'label_ar' => '11 طن', 'value' => '11'],
                ['code' => '14', 'label_ar' => '14 طن', 'value' => '14'],
                ['code' => '17', 'label_ar' => '17 طن', 'value' => '17'],
            ],
            Lookup::TYPE_COUNTRY => [
                ['code' => 'eg', 'label_ar' => 'مصر', 'value' => 'EG'],
                ['code' => 'sa', 'label_ar' => 'السعودية', 'value' => 'SA'],
                ['code' => 'ae', 'label_ar' => 'الإمارات', 'value' => 'AE'],
            ],
            Lookup::TYPE_LOCATION => [
                ['code' => 'cairo', 'label_ar' => 'القاهرة', 'value' => 'cairo'],
                ['code' => 'giza', 'label_ar' => 'الجيزة', 'value' => 'giza'],
                ['code' => 'alex', 'label_ar' => 'الإسكندرية', 'value' => 'alex'],
                ['code' => 'sharkia', 'label_ar' => 'الشرقية', 'value' => 'sharkia'],
                ['code' => 'beheira', 'label_ar' => 'البحيرة', 'value' => 'beheira'],
                ['code' => 'daqahlia', 'label_ar' => 'الدقهلية', 'value' => 'daqahlia'],
                ['code' => 'other', 'label_ar' => 'أخرى', 'value' => 'other'],
            ],
        ];

        foreach ($groups as $type => $rows) {
            foreach ($rows as $i => $row) {
                Lookup::updateOrCreate(
                    ['type' => $type, 'code' => $row['code']],
                    [
                        'label_ar' => $row['label_ar'],
                        'label_en' => $row['label_en'] ?? null,
                        'value' => $row['value'],
                        'sort_order' => $i + 1,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
