<?php

use App\Support\LayerBaseLookups;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('poultry_quotations', 'layer_specs')) {
                $table->json('layer_specs')->nullable();
            }
        });

        foreach (LayerBaseLookups::groups() as $type => $rows) {
            foreach ($rows as $i => $row) {
                $this->upsertLookup($type, $row, $i + 1);
            }
        }
    }

    public function down(): void
    {
        DB::table('lookups')->whereIn('type', array_keys(LayerBaseLookups::groups()))->delete();

        Schema::table('poultry_quotations', function (Blueprint $table) {
            if (Schema::hasColumn('poultry_quotations', 'layer_specs')) {
                $table->dropColumn('layer_specs');
            }
        });
    }

    /** @param  array{code: string, label_ar: string, value: string, meta?: array<string, mixed>}  $row */
    private function upsertLookup(string $type, array $row, int $sort): void
    {
        $now = now();
        $payload = [
            'label_ar' => $row['label_ar'],
            'label_en' => $row['label_en'] ?? null,
            'value' => $row['value'],
            'sort_order' => $sort,
            'is_active' => true,
            'meta' => json_encode($row['meta'] ?? [], JSON_UNESCAPED_UNICODE),
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
