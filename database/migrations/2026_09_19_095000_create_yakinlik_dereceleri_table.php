<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yakinlik_dereceleri', function (Blueprint $table) {
            $table->id();
            $table->string('kod', 20)->unique();
            $table->string('ad', 50);
            $table->unsignedSmallInteger('sira')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('yakinlik_dereceleri')->insert([
            ['kod' => 'ESI', 'ad' => 'Eşi', 'sira' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['kod' => 'OGLU', 'ad' => 'Oğlu', 'sira' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['kod' => 'KIZI', 'ad' => 'Kızı', 'sira' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['kod' => 'ANNE', 'ad' => 'Annesi', 'sira' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['kod' => 'BABA', 'ad' => 'Babası', 'sira' => 5, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('yakinlik_dereceleri');
    }
};
