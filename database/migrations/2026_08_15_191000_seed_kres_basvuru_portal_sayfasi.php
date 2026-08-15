<?php

use App\Models\PortalSayfa;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (PortalSayfa::query()->where('kod', 'kres-basvuru')->exists()) {
            return;
        }

        $maxSira = (int) PortalSayfa::query()->max('sira');

        PortalSayfa::query()->create([
            'kod' => 'kres-basvuru',
            'baslik' => 'Kreş Başvuru',
            'slug' => 'kres',
            'sistem' => true,
            'menude_goster' => true,
            'sadece_giris' => false,
            'sira' => $maxSira + 1,
            'aciklama' => 'Aktif dönem için kreş başvurusu yapın.',
            'menu_aciklama' => 'Veli ve öğrenci bilgileriyle kreş başvurusu oluşturun.',
            'anasayfa_menu_arkaplan_mod' => 'kapla',
        ]);
    }

    public function down(): void
    {
        PortalSayfa::query()->where('kod', 'kres-basvuru')->delete();
    }
};
