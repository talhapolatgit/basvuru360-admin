<?php

use App\Support\YetkiKatalogu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'rol')) {
            return;
        }

        $this->sistemRolleriniVeYetkileriKur();
        $this->kullaniciRolleriniTasi();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('rol');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'rol')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('rol')->default('personel')->index()->after('telefon');
            });
        }

        if (! Schema::hasTable('kullanici_rol') || ! Schema::hasTable('roller')) {
            return;
        }

        $roller = DB::table('roller')->pluck('kod', 'id');

        foreach (DB::table('kullanici_rol')->orderBy('id')->get() as $row) {
            $kod = $roller[$row->rol_id] ?? 'personel';
            if ($kod === 'admin') {
                $kod = 'personel';
            }
            if (! in_array($kod, ['personel', 'ogretmen'], true)) {
                $kod = 'personel';
            }

            DB::table('users')->where('id', $row->user_id)->update(['rol' => $kod]);
        }
    }

    private function sistemRolleriniVeYetkileriKur(): void
    {
        if (! Schema::hasTable('yetkiler') || ! Schema::hasTable('roller')) {
            return;
        }

        $sira = 0;
        foreach (YetkiKatalogu::tanimlar() as $tanim) {
            $exists = DB::table('yetkiler')->where('kod', $tanim['kod'])->exists();
            if ($exists) {
                continue;
            }
            DB::table('yetkiler')->insert([
                'kod' => $tanim['kod'],
                'ad' => $tanim['ad'],
                'modul' => $tanim['modul'],
                'aciklama' => $tanim['aciklama'] ?? null,
                'sira' => $sira++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $roller = [
            ['kod' => 'admin', 'ad' => 'Admin', 'aciklama' => 'Tüm yetkilere sınırsız erişim.', 'tum_yetkiler' => true, 'sistem' => true, 'sira' => 1],
            ['kod' => 'personel', 'ad' => 'Personel', 'aciklama' => 'Yönetim paneli personeli.', 'tum_yetkiler' => false, 'sistem' => true, 'sira' => 2],
            ['kod' => 'ogretmen', 'ad' => 'Öğretmen', 'aciklama' => 'Kurs eğitmeni.', 'tum_yetkiler' => false, 'sistem' => true, 'sira' => 3],
        ];

        foreach ($roller as $rol) {
            if (DB::table('roller')->where('kod', $rol['kod'])->exists()) {
                continue;
            }
            DB::table('roller')->insert($rol + [
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $yetkiIds = DB::table('yetkiler')->pluck('id', 'kod');
        $personelId = DB::table('roller')->where('kod', 'personel')->value('id');
        $ogretmenId = DB::table('roller')->where('kod', 'ogretmen')->value('id');

        if ($personelId) {
            foreach (YetkiKatalogu::personelYetkileri() as $kod) {
                $yetkiId = $yetkiIds[$kod] ?? null;
                if (! $yetkiId) {
                    continue;
                }
                $exists = DB::table('rol_yetki')
                    ->where('rol_id', $personelId)
                    ->where('yetki_id', $yetkiId)
                    ->exists();
                if (! $exists) {
                    DB::table('rol_yetki')->insert([
                        'rol_id' => $personelId,
                        'yetki_id' => $yetkiId,
                    ]);
                }
            }
        }

        if ($ogretmenId) {
            foreach (YetkiKatalogu::ogretmenYetkileri() as $kod) {
                $yetkiId = $yetkiIds[$kod] ?? null;
                if (! $yetkiId) {
                    continue;
                }
                $exists = DB::table('rol_yetki')
                    ->where('rol_id', $ogretmenId)
                    ->where('yetki_id', $yetkiId)
                    ->exists();
                if (! $exists) {
                    DB::table('rol_yetki')->insert([
                        'rol_id' => $ogretmenId,
                        'yetki_id' => $yetkiId,
                    ]);
                }
            }
        }
    }

    private function kullaniciRolleriniTasi(): void
    {
        if (! Schema::hasTable('roller') || ! Schema::hasTable('kullanici_rol')) {
            return;
        }

        $roller = DB::table('roller')->pluck('id', 'kod');

        foreach (DB::table('users')->orderBy('id')->get() as $user) {
            $mevcut = DB::table('kullanici_rol')->where('user_id', $user->id)->exists();
            if ($mevcut) {
                continue;
            }

            $kod = $user->email === 'admin@basvuru360.test'
                ? 'admin'
                : (string) ($user->rol ?? 'personel');

            if ($kod === 'admin' && ! isset($roller['admin'])) {
                $kod = 'personel';
            }
            if (! isset($roller[$kod])) {
                $kod = 'personel';
            }

            $rolId = $roller[$kod] ?? null;
            if (! $rolId) {
                continue;
            }

            DB::table('kullanici_rol')->insert([
                'user_id' => $user->id,
                'rol_id' => $rolId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
