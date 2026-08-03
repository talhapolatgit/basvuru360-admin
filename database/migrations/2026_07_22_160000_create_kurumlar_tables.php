<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurumlar', function (Blueprint $table) {
            $table->id();
            $table->string('ad')->unique();
            $table->boolean('aktif')->default(true);
            $table->unsignedInteger('sira')->default(0);
            $table->timestamps();
        });

        Schema::create('kullanici_kurum', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kurum_id')->constrained('kurumlar')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'kurum_id']);
        });

        $now = now();
        DB::table('kurumlar')->insert([
            ['ad' => 'Küçükçekmece Belediyesi', 'aktif' => true, 'sira' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['ad' => 'İSMEK', 'aktif' => true, 'sira' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['ad' => 'Halk Eğitim Merkezi', 'aktif' => true, 'sira' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('kullanici_kurum');
        Schema::dropIfExists('kurumlar');
    }
};
