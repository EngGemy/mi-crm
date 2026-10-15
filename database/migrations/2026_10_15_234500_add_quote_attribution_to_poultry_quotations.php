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
        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->foreignId('client_added_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->json('contributors')->nullable()->after('client_added_by');
        });

        $byPhone = [];
        $leads = DB::table('leads')
            ->whereNotNull('created_by')
            ->orderBy('id')
            ->get(['phone', 'whatsapp', 'created_by']);

        foreach ($leads as $lead) {
            foreach ([$lead->phone, $lead->whatsapp] as $phone) {
                $key = SavedClientCatalog::phoneKey($phone);
                if ($key !== null && ! isset($byPhone[$key])) {
                    $byPhone[$key] = (int) $lead->created_by;
                }
            }
        }

        if ($byPhone === []) {
            return;
        }

        DB::table('poultry_quotations')
            ->whereNull('client_added_by')
            ->orderBy('id')
            ->select(['id', 'client_phone'])
            ->chunkById(200, function ($rows) use ($byPhone) {
                foreach ($rows as $row) {
                    $key = SavedClientCatalog::phoneKey($row->client_phone);
                    if ($key !== null && isset($byPhone[$key])) {
                        DB::table('poultry_quotations')->where('id', $row->id)->update([
                            'client_added_by' => $byPhone[$key],
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_added_by');
            $table->dropColumn('contributors');
        });
    }
};
