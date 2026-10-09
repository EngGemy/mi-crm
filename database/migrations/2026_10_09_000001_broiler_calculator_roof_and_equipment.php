<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * خيارات معدات التسمين: 1 حصان، 4 مواتير سبلة، سيور 16/20 و12، سايلو 25 طن.
 * ونوع السقف على عرض السعر.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('poultry_quotations', 'roof_type')) {
                $table->string('roof_type', 16)->nullable()->after('internal_columns');
            }
        });

        $this->upsert('motor_power', ['code' => '1_hp', 'label_ar' => '1 حصان', 'value' => '1'], 1);
        DB::table('lookups')->where('type', 'motor_power')->where('code', '1_5_hp')->update(['sort_order' => 2]);

        $this->upsert('manure_motor_count', ['code' => '4', 'label_ar' => '4 ماتور', 'value' => '4'], 4);
        $this->upsert('inner_belt_length', ['code' => '16', 'label_ar' => '16 متر', 'value' => '16'], 2);
        $this->upsert('inner_belt_length', ['code' => '20', 'label_ar' => '20 متر', 'value' => '20'], 3);
        $this->upsert('outer_belt_length', ['code' => '12', 'label_ar' => '12 متر', 'value' => '12'], 2);
        $this->upsert('silo_capacity', ['code' => '25', 'label_ar' => '25 طن', 'value' => '25'], 4);

        $this->markDefault('motor_power', '1_hp');
        $this->markDefault('manure_motor_count', '4');
        $this->markDefault('inner_belt_length', '20');
        $this->markDefault('outer_belt_length', '12');
        $this->markDefault('silo_capacity', '25');
    }

    public function down(): void
    {
        DB::table('lookups')->where('type', 'motor_power')->where('code', '1_hp')->delete();
        DB::table('lookups')->where('type', 'manure_motor_count')->where('code', '4')->delete();
        DB::table('lookups')->where('type', 'inner_belt_length')->whereIn('code', ['16', '20'])->delete();
        DB::table('lookups')->where('type', 'outer_belt_length')->where('code', '12')->delete();
        DB::table('lookups')->where('type', 'silo_capacity')->where('code', '25')->delete();

        foreach (['motor_power', 'manure_motor_count', 'inner_belt_length', 'outer_belt_length', 'silo_capacity'] as $type) {
            $this->clearDefaults($type);
        }

        Schema::table('poultry_quotations', function (Blueprint $table) {
            if (Schema::hasColumn('poultry_quotations', 'roof_type')) {
                $table->dropColumn('roof_type');
            }
        });
    }

    /** @param  array{code: string, label_ar: string, value: string}  $row */
    private function upsert(string $type, array $row, int $sort): void
    {
        $now = now();
        $existing = DB::table('lookups')->where('type', $type)->where('code', $row['code']);
        $payload = [
            'label_ar' => $row['label_ar'],
            'value' => $row['value'],
            'sort_order' => $sort,
            'is_active' => true,
            'updated_at' => $now,
        ];

        if ($existing->exists()) {
            $existing->update($payload);

            return;
        }

        DB::table('lookups')->insert($payload + [
            'type' => $type,
            'code' => $row['code'],
            'meta' => json_encode([], JSON_UNESCAPED_UNICODE),
            'created_at' => $now,
        ]);
    }

    private function markDefault(string $type, string $code): void
    {
        $rows = DB::table('lookups')->where('type', $type)->get();
        foreach ($rows as $row) {
            $meta = json_decode($row->meta ?? '[]', true);
            if (! is_array($meta)) {
                $meta = [];
            }
            $meta['is_default'] = $row->code === $code;
            DB::table('lookups')->where('id', $row->id)->update([
                'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
        }
    }

    private function clearDefaults(string $type): void
    {
        $rows = DB::table('lookups')->where('type', $type)->get();
        foreach ($rows as $row) {
            $meta = json_decode($row->meta ?? '[]', true);
            if (! is_array($meta) || ! array_key_exists('is_default', $meta)) {
                continue;
            }
            $meta['is_default'] = false;
            DB::table('lookups')->where('id', $row->id)->update([
                'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }
};
