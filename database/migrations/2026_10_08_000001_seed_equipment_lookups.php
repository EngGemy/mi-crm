<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * قوائم المعدات كانت في الـ seeder فقط، والنشر يشغّل migrate ولا يشغّل db:seed.
 */
return new class extends Migration
{
    public function up(): void
    {
        $groups = [
            'manure_motor_count' => [
                ['code' => '1', 'label_ar' => '1 ماتور', 'value' => '1'],
                ['code' => '2', 'label_ar' => '2 ماتور', 'value' => '2'],
                ['code' => '3', 'label_ar' => '3 ماتور', 'value' => '3'],
            ],
            'motor_power' => [
                ['code' => '1_5_hp', 'label_ar' => '1.5 حصان', 'value' => '1.5'],
            ],
            'belts_per_line' => [
                ['code' => '3', 'label_ar' => '3 سيور', 'value' => '3'],
                ['code' => '4', 'label_ar' => '4 سيور', 'value' => '4'],
                ['code' => '5', 'label_ar' => '5 سيور', 'value' => '5'],
            ],
            'inner_belt_length' => [
                ['code' => '12', 'label_ar' => '12 متر', 'value' => '12'],
            ],
            'outer_belt_length' => [
                ['code' => '8', 'label_ar' => '8 متر', 'value' => '8'],
            ],
            'silo_capacity' => [
                ['code' => '11', 'label_ar' => '11 طن', 'value' => '11'],
                ['code' => '14', 'label_ar' => '14 طن', 'value' => '14'],
                ['code' => '17', 'label_ar' => '17 طن', 'value' => '17'],
            ],
        ];

        foreach ($groups as $type => $rows) {
            foreach ($rows as $i => $row) {
                $this->upsert($type, $row, $i + 1);
            }
        }
    }

    public function down(): void
    {
        DB::table('lookups')->whereIn('type', [
            'manure_motor_count',
            'motor_power',
            'belts_per_line',
            'inner_belt_length',
            'outer_belt_length',
            'silo_capacity',
        ])->delete();
    }

    /** @param  array{code: string, label_ar: string, value: string}  $row */
    private function upsert(string $type, array $row, int $sort): void
    {
        $now = now();
        $payload = [
            'label_ar' => $row['label_ar'],
            'value' => $row['value'],
            'sort_order' => $sort,
            'is_active' => true,
            'updated_at' => $now,
        ];

        $existing = DB::table('lookups')->where('type', $type)->where('code', $row['code']);
        if ($existing->exists()) {
            $existing->update($payload);

            return;
        }

        DB::table('lookups')->insert($payload + [
            'type' => $type,
            'code' => $row['code'],
            'created_at' => $now,
        ]);
    }
};
