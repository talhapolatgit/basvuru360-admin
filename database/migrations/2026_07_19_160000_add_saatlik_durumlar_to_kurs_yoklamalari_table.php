<?php

use App\Enums\YoklamaDurum;
use App\Models\KursYoklama;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kurs_yoklamalari', function (Blueprint $table) {
            $table->json('saatlik_durumlar')->nullable()->after('durum');
        });

        DB::table('kurs_yoklamalari')
            ->where('durum', 'gec')
            ->update(['durum' => YoklamaDurum::Var->value]);

        KursYoklama::query()
            ->with('ders:id,ders_saati')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $yoklama) {
                    $adet = max(1, (int) ceil((float) ($yoklama->ders?->ders_saati ?? 1)));
                    $base = $yoklama->durum?->value ?? YoklamaDurum::Yok->value;
                    if ($base === 'gec') {
                        $base = YoklamaDurum::Var->value;
                    }

                    $yoklama->forceFill([
                        'saatlik_durumlar' => array_fill(0, $adet, $base),
                    ])->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        Schema::table('kurs_yoklamalari', function (Blueprint $table) {
            $table->dropColumn('saatlik_durumlar');
        });
    }
};
