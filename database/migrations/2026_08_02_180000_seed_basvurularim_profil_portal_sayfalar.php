<?php

use App\Models\PortalSayfa;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $maxSira = (int) PortalSayfa::query()->max('sira');

        if (! PortalSayfa::query()->where('kod', 'basvurularim')->exists()) {
            PortalSayfa::query()->create([
                'kod' => 'basvurularim',
                'baslik' => 'Başvurularım',
                'slug' => 'basvurularim',
                'sistem' => true,
                'menude_goster' => true,
                'sira' => $maxSira + 1,
                'aciklama' => 'Kurs ve etkinlik başvurularınızı görüntüleyin veya iptal edin.',
                'menu_aciklama' => 'Başvurularınızı görüntüleyin veya iptal edin.',
                'anasayfa_menu_arkaplan_mod' => 'kapla',
            ]);
            $maxSira++;
        }

        if (! PortalSayfa::query()->where('kod', 'profil')->exists()) {
            PortalSayfa::query()->create([
                'kod' => 'profil',
                'baslik' => 'Profil',
                'slug' => 'profil',
                'sistem' => true,
                'menude_goster' => true,
                'sira' => $maxSira + 1,
                'aciklama' => 'Kişisel bilgilerinizi görüntüleyin ve güncelleyin.',
                'menu_aciklama' => 'Kişisel bilgilerinizi görüntüleyin ve güncelleyin.',
                'anasayfa_menu_arkaplan_mod' => 'kapla',
            ]);
        }
    }

    public function down(): void
    {
        PortalSayfa::query()
            ->whereIn('kod', ['basvurularim', 'profil'])
            ->delete();
    }
};
