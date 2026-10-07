<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $payload = [
            'label_ar' => 'بطاريات فقط بياض مصري',
            'label_en' => null,
            'value' => 'batteries_only_layers_eg',
            'sort_order' => 2,
            'is_active' => true,
            'meta' => json_encode([], JSON_UNESCAPED_UNICODE),
            'updated_at' => $now,
        ];

        $existing = DB::table('lookups')
            ->where('type', 'quote_type')
            ->where('code', 'batteries_layers_eg');

        if ($existing->exists()) {
            $existing->update($payload);

            return;
        }

        DB::table('lookups')->insert($payload + [
            'type' => 'quote_type',
            'code' => 'batteries_layers_eg',
            'created_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('lookups')
            ->where('type', 'quote_type')
            ->where('code', 'batteries_layers_eg')
            ->delete();
    }
};
