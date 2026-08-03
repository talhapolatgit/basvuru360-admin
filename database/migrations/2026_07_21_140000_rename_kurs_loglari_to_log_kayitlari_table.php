<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kurs_loglari') && ! Schema::hasTable('log_kayitlari')) {
            Schema::rename('kurs_loglari', 'log_kayitlari');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('log_kayitlari') && ! Schema::hasTable('kurs_loglari')) {
            Schema::rename('log_kayitlari', 'kurs_loglari');
        }
    }
};
