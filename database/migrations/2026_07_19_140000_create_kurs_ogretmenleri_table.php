<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurs_ogretmenleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kurs_id')->constrained('kurslar')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['kurs_id', 'user_id']);
        });

        $now = now();

        DB::table('kurslar')
            ->whereNotNull('ogretmen_id')
            ->orderBy('id')
            ->select(['id', 'ogretmen_id'])
            ->chunkById(200, function ($kurslar) use ($now) {
                $rows = [];

                foreach ($kurslar as $kurs) {
                    $rows[] = [
                        'kurs_id' => $kurs->id,
                        'user_id' => $kurs->ogretmen_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('kurs_ogretmenleri')->insertOrIgnore($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurs_ogretmenleri');
    }
};
