<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kreşe özel soru formlarını kurs, etkinlik ve kreş dönemlerinin ortak kullandığı
 * soru formu kütüphanesine taşır.
 */
return new class extends Migration
{
    private const KRES_BASVURU_TYPE = 'App\\Models\\KresBasvuru';

    public function up(): void
    {
        Schema::create('soru_formlari', function (Blueprint $table) {
            $table->id();
            $table->string('ad');
            $table->text('aciklama')->nullable();
            $table->boolean('aktif')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('sorular', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('soru_formlari')->cascadeOnDelete();
            $table->string('tip');
            $table->string('baslik', 500);
            $table->text('aciklama')->nullable();
            $table->boolean('zorunlu')->default(false);
            $table->decimal('min_deger', 12, 4)->nullable();
            $table->decimal('max_deger', 12, 4)->nullable();
            $table->boolean('tam_sayi')->default(false);
            $table->foreignId('kosul_soru_id')->nullable()->constrained('sorular')->nullOnDelete();
            $table->json('kosul_secenek_ids')->nullable();
            $table->unsignedInteger('sira')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('soru_secenekleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('soru_id')->constrained('sorular')->cascadeOnDelete();
            $table->string('etiket');
            $table->unsignedInteger('sira')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('basvuru_cevaplari', function (Blueprint $table) {
            $table->id();
            $table->string('basvuru_type');
            $table->unsignedBigInteger('basvuru_id');
            $table->foreignId('soru_id')->nullable()->constrained('sorular')->nullOnDelete();
            $table->string('soru_baslik', 500);
            $table->string('soru_tip', 30);
            $table->text('deger')->nullable();
            $table->text('deger_metin')->nullable();
            $table->string('dosya_yolu')->nullable();
            $table->string('orijinal_ad')->nullable();
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('boyut')->nullable();
            $table->timestamps();

            $table->index(['basvuru_type', 'basvuru_id']);
            $table->unique(['basvuru_type', 'basvuru_id', 'soru_id']);
        });

        foreach (['kurslar', 'etkinlikler', 'kres_donemler'] as $tablo) {
            Schema::table($tablo, function (Blueprint $table) {
                $table->foreignId('soru_formu_id')->nullable()->constrained('soru_formlari')->nullOnDelete();
            });
        }

        $this->kresVerileriniTasi();

        Schema::dropIfExists('kres_basvuru_cevaplari');
        Schema::dropIfExists('kres_soru_secenekler');
        Schema::dropIfExists('kres_sorular');
        Schema::dropIfExists('kres_soru_formlari');

        $this->yetkiyiTasi();
    }

    private function yetkiyiTasi(): void
    {
        $alanlar = [
            'ad' => 'Başvuru Soru Formlarını Yönet',
            'modul' => 'sabit',
            'aciklama' => 'Kurs, etkinlik ve kreş başvurularında kullanılan soru formlarını oluşturma ve düzenleme.',
        ];
        $eski = DB::table('yetkiler')->where('kod', 'kres.soru_formu_yonet')->first();
        $yeni = DB::table('yetkiler')->where('kod', 'soru_formu.yonet')->first();

        if ($eski && ! $yeni) {
            DB::table('yetkiler')->where('id', $eski->id)->update(['kod' => 'soru_formu.yonet'] + $alanlar);

            return;
        }

        if (! $yeni) {
            $yeniId = DB::table('yetkiler')->insertGetId(['kod' => 'soru_formu.yonet'] + $alanlar + [
                'sira' => ((int) DB::table('yetkiler')->max('sira')) + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $personel = DB::table('roller')->where('kod', 'personel')->first();
            if ($personel && ! $personel->tum_yetkiler) {
                DB::table('rol_yetki')->insertOrIgnore(['rol_id' => $personel->id, 'yetki_id' => $yeniId]);
            }

            return;
        }

        if ($eski) {
            foreach (DB::table('rol_yetki')->where('yetki_id', $eski->id)->pluck('rol_id') as $rolId) {
                DB::table('rol_yetki')->insertOrIgnore(['rol_id' => $rolId, 'yetki_id' => $yeni->id]);
            }
            DB::table('rol_yetki')->where('yetki_id', $eski->id)->delete();
            DB::table('yetkiler')->where('id', $eski->id)->delete();
        }
    }

    public function down(): void
    {
        Schema::create('kres_soru_formlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donem_id')->unique()->constrained('kres_donemler')->cascadeOnDelete();
            $table->string('ad');
            $table->text('aciklama')->nullable();
            $table->boolean('aktif')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('kres_sorular', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('kres_soru_formlari')->cascadeOnDelete();
            $table->string('tip');
            $table->string('baslik');
            $table->text('aciklama')->nullable();
            $table->boolean('zorunlu')->default(false);
            $table->decimal('min_deger', 12, 4)->nullable();
            $table->decimal('max_deger', 12, 4)->nullable();
            $table->boolean('tam_sayi')->default(false);
            $table->unsignedInteger('sira')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('kres_soru_secenekler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('soru_id')->constrained('kres_sorular')->cascadeOnDelete();
            $table->string('etiket');
            $table->unsignedInteger('sira')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('kres_basvuru_cevaplari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('basvuru_id')->constrained('kres_basvurulari')->cascadeOnDelete();
            $table->foreignId('soru_id')->constrained('kres_sorular')->cascadeOnDelete();
            $table->text('deger')->nullable();
            $table->string('dosya_yolu')->nullable();
            $table->string('orijinal_ad')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('boyut')->nullable();
            $table->timestamps();

            $table->unique(['basvuru_id', 'soru_id']);
        });

        $this->kresVerileriniGeriTasi();

        DB::table('yetkiler')->where('kod', 'soru_formu.yonet')->update([
            'kod' => 'kres.soru_formu_yonet',
            'ad' => 'Kreş Soru Formu Yönet',
            'modul' => 'kres',
            'aciklama' => null,
        ]);

        foreach (['kurslar', 'etkinlikler', 'kres_donemler'] as $tablo) {
            Schema::table($tablo, function (Blueprint $table) {
                $table->dropConstrainedForeignId('soru_formu_id');
            });
        }

        Schema::dropIfExists('basvuru_cevaplari');
        Schema::dropIfExists('soru_secenekleri');
        Schema::dropIfExists('sorular');
        Schema::dropIfExists('soru_formlari');
    }

    private function kresVerileriniTasi(): void
    {
        if (! Schema::hasTable('kres_soru_formlari')) {
            return;
        }

        $soruMap = [];
        $secenekMap = [];
        $secenekEtiket = [];
        $soruBilgi = [];

        foreach (DB::table('kres_soru_formlari')->orderBy('id')->get() as $eskiForm) {
            $formId = DB::table('soru_formlari')->insertGetId([
                'ad' => $eskiForm->ad,
                'aciklama' => $eskiForm->aciklama,
                'aktif' => $eskiForm->aktif,
                'created_at' => $eskiForm->created_at,
                'updated_at' => $eskiForm->updated_at,
            ]);

            DB::table('kres_donemler')->where('id', $eskiForm->donem_id)->update(['soru_formu_id' => $formId]);

            foreach (DB::table('kres_sorular')->where('form_id', $eskiForm->id)->orderBy('sira')->orderBy('id')->get() as $eskiSoru) {
                $soruId = DB::table('sorular')->insertGetId([
                    'form_id' => $formId,
                    'tip' => $eskiSoru->tip,
                    'baslik' => mb_substr((string) $eskiSoru->baslik, 0, 500),
                    'aciklama' => $eskiSoru->aciklama,
                    'zorunlu' => $eskiSoru->zorunlu,
                    'min_deger' => $eskiSoru->min_deger ?? null,
                    'max_deger' => $eskiSoru->max_deger ?? null,
                    'tam_sayi' => $eskiSoru->tam_sayi ?? false,
                    'sira' => $eskiSoru->sira,
                    'created_at' => $eskiSoru->created_at,
                    'updated_at' => $eskiSoru->updated_at,
                ]);
                $soruMap[$eskiSoru->id] = $soruId;
                $soruBilgi[$eskiSoru->id] = $eskiSoru;

                foreach (DB::table('kres_soru_secenekler')->where('soru_id', $eskiSoru->id)->orderBy('sira')->orderBy('id')->get() as $eskiSecenek) {
                    $secenekId = DB::table('soru_secenekleri')->insertGetId([
                        'soru_id' => $soruId,
                        'etiket' => $eskiSecenek->etiket,
                        'sira' => $eskiSecenek->sira,
                        'created_at' => $eskiSecenek->created_at,
                        'updated_at' => $eskiSecenek->updated_at,
                    ]);
                    $secenekMap[$eskiSecenek->id] = $secenekId;
                    $secenekEtiket[$secenekId] = $eskiSecenek->etiket;
                }
            }
        }

        foreach (DB::table('kres_basvuru_cevaplari')->orderBy('id')->get() as $eskiCevap) {
            $eskiSoru = $soruBilgi[$eskiCevap->soru_id] ?? null;
            if (! $eskiSoru) {
                continue;
            }

            $deger = $eskiCevap->deger;
            $degerMetin = null;

            if (in_array($eskiSoru->tip, ['liste', 'radio'], true) && $deger !== null && $deger !== '') {
                $yeniId = $secenekMap[(int) $deger] ?? null;
                $deger = $yeniId !== null ? (string) $yeniId : $deger;
                $degerMetin = $yeniId !== null ? ($secenekEtiket[$yeniId] ?? null) : null;
            } elseif ($eskiSoru->tip === 'checkbox' && $deger !== null && $deger !== '') {
                $eskiIds = json_decode((string) $deger, true);
                $yeniIds = collect(is_array($eskiIds) ? $eskiIds : [])
                    ->map(fn ($id) => $secenekMap[(int) $id] ?? null)
                    ->filter()
                    ->values()
                    ->all();
                $deger = json_encode($yeniIds);
                $degerMetin = collect($yeniIds)->map(fn ($id) => $secenekEtiket[$id] ?? null)->filter()->implode(', ');
            }

            DB::table('basvuru_cevaplari')->insert([
                'basvuru_type' => self::KRES_BASVURU_TYPE,
                'basvuru_id' => $eskiCevap->basvuru_id,
                'soru_id' => $soruMap[$eskiCevap->soru_id],
                'soru_baslik' => mb_substr((string) $eskiSoru->baslik, 0, 500),
                'soru_tip' => $eskiSoru->tip,
                'deger' => $deger,
                'deger_metin' => $degerMetin,
                'dosya_yolu' => $eskiCevap->dosya_yolu,
                'orijinal_ad' => $eskiCevap->orijinal_ad,
                'mime' => $eskiCevap->mime,
                'boyut' => $eskiCevap->boyut,
                'created_at' => $eskiCevap->created_at,
                'updated_at' => $eskiCevap->updated_at,
            ]);
        }
    }

    private function kresVerileriniGeriTasi(): void
    {
        $soruMap = [];

        foreach (DB::table('kres_donemler')->whereNotNull('soru_formu_id')->get() as $donem) {
            $form = DB::table('soru_formlari')->where('id', $donem->soru_formu_id)->first();
            if (! $form) {
                continue;
            }

            $eskiFormId = DB::table('kres_soru_formlari')->insertGetId([
                'donem_id' => $donem->id,
                'ad' => $form->ad,
                'aciklama' => $form->aciklama,
                'aktif' => $form->aktif,
                'created_at' => $form->created_at,
                'updated_at' => $form->updated_at,
            ]);

            foreach (DB::table('sorular')->where('form_id', $form->id)->orderBy('sira')->get() as $soru) {
                $eskiSoruId = DB::table('kres_sorular')->insertGetId([
                    'form_id' => $eskiFormId,
                    'tip' => $soru->tip,
                    'baslik' => mb_substr((string) $soru->baslik, 0, 255),
                    'aciklama' => $soru->aciklama,
                    'zorunlu' => $soru->zorunlu,
                    'min_deger' => $soru->min_deger,
                    'max_deger' => $soru->max_deger,
                    'tam_sayi' => $soru->tam_sayi,
                    'sira' => $soru->sira,
                    'created_at' => $soru->created_at,
                    'updated_at' => $soru->updated_at,
                ]);
                $soruMap[$soru->id] ??= $eskiSoruId;

                foreach (DB::table('soru_secenekleri')->where('soru_id', $soru->id)->orderBy('sira')->get() as $secenek) {
                    DB::table('kres_soru_secenekler')->insert([
                        'soru_id' => $eskiSoruId,
                        'etiket' => $secenek->etiket,
                        'sira' => $secenek->sira,
                        'created_at' => $secenek->created_at,
                        'updated_at' => $secenek->updated_at,
                    ]);
                }
            }
        }

        foreach (DB::table('basvuru_cevaplari')->where('basvuru_type', self::KRES_BASVURU_TYPE)->get() as $cevap) {
            if (! isset($soruMap[$cevap->soru_id])) {
                continue;
            }

            DB::table('kres_basvuru_cevaplari')->insert([
                'basvuru_id' => $cevap->basvuru_id,
                'soru_id' => $soruMap[$cevap->soru_id],
                'deger' => $cevap->deger,
                'dosya_yolu' => $cevap->dosya_yolu,
                'orijinal_ad' => $cevap->orijinal_ad,
                'mime' => $cevap->mime,
                'boyut' => $cevap->boyut,
                'created_at' => $cevap->created_at,
                'updated_at' => $cevap->updated_at,
            ]);
        }
    }
};
