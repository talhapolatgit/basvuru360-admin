<?php

use App\Models\Kurs;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('numarator', function (Blueprint $table) {
            $table->id();
            $table->string('kod', 50)->unique();
            $table->unsignedBigInteger('son_numara')->default(0);
            $table->timestamps();
        });

        $max = Kurs::query()
            ->pluck('kurs_no')
            ->filter(fn ($no) => is_string($no) && ctype_digit($no))
            ->map(fn ($no) => (int) $no)
            ->max() ?: 0;

        $now = now();
        DB::table('numarator')->insert([
            'kod' => 'kurs',
            'son_numara' => $max,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('numarator');
    }
};
