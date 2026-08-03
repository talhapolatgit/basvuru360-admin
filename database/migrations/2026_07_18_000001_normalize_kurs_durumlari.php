<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('kurslar')
            ->whereIn('durum', ['onay_bekleyen', 'onaylanan'])
            ->update(['durum' => 'hazirlik']);
    }

    public function down(): void
    {
        // Eski durumlara geri dönüş güvenli değil; no-op.
    }
};
