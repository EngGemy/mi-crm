<?php

use App\Services\Poultry\SavedClientCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('phone_key', 20)->nullable()->index();
            $table->string('alt_phone_key', 20)->nullable()->index();
            $table->string('search_name')->nullable();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone_key', 20)->nullable()->index();
            $table->string('alt_phone_key', 20)->nullable()->index();
            $table->string('search_name')->nullable();
        });

        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->string('client_phone_key', 20)->nullable()->index();
            $table->softDeletes();
        });

        DB::table('leads')->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('leads')->where('id', $row->id)->update([
                    'phone_key' => SavedClientCatalog::phoneKey($row->phone),
                    'alt_phone_key' => SavedClientCatalog::phoneKey($row->whatsapp),
                    'search_name' => SavedClientCatalog::fold(trim(($row->name ?? '').' '.($row->company ?? '').' '.($row->city ?? ''))),
                ]);
            }
        });

        DB::table('customers')->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('customers')->where('id', $row->id)->update([
                    'phone_key' => SavedClientCatalog::phoneKey($row->phone),
                    'alt_phone_key' => SavedClientCatalog::phoneKey($row->whatsapp ?: $row->phone_alt),
                    'search_name' => SavedClientCatalog::fold(trim(($row->name ?? '').' '.($row->name_en ?? '').' '.($row->city ?? ''))),
                ]);
            }
        });

        DB::table('poultry_quotations')->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('poultry_quotations')->where('id', $row->id)->update([
                    'client_phone_key' => SavedClientCatalog::phoneKey($row->client_phone),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['phone_key']);
            $table->dropIndex(['alt_phone_key']);
            $table->dropColumn(['phone_key', 'alt_phone_key', 'search_name']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['phone_key']);
            $table->dropIndex(['alt_phone_key']);
            $table->dropColumn(['phone_key', 'alt_phone_key', 'search_name']);
        });

        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->dropIndex(['client_phone_key']);
            $table->dropColumn(['client_phone_key', 'deleted_at']);
        });
    }
};
