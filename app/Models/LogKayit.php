<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogKayit extends Model
{
    protected $table = 'log_kayitlari';

    protected $fillable = [
        'kurs_id',
        'user_id',
        'islem',
        'aciklama',
        'konu_tipi',
        'konu_id',
        'konu_adi',
        'eski_veriler',
        'yeni_veriler',
        'ekstra',
        'ip_adresi',
        'user_agent',
        'tarayici',
        'platform',
        'cihaz_tipi',
        'http_metodu',
        'url',
    ];

    protected function casts(): array
    {
        return [
            'eski_veriler' => 'array',
            'yeni_veriler' => 'array',
            'ekstra' => 'array',
        ];
    }

    /**
     * İşlem kodlarının okunabilir etiketleri ve renk sınıfları.
     *
     * @var array<string, array{ad: string, renk: string}>
     */
    public const ISLEM_ETIKETLERI = [
        'kurs.olusturuldu' => ['ad' => 'Kurs Oluşturuldu', 'renk' => 'success'],
        'kurs.guncellendi' => ['ad' => 'Kurs Güncellendi', 'renk' => 'info'],
        'kurs.yayina_alindi' => ['ad' => 'Yayına Alındı', 'renk' => 'success'],
        'kurs.yayindan_kaldirildi' => ['ad' => 'Yayından Kaldırıldı', 'renk' => 'warning'],
        'kurs.ogretmen_atandi' => ['ad' => 'Öğretmen Atandı', 'renk' => 'info'],
        'kurs.ogretmen_kaldirildi' => ['ad' => 'Öğretmen Kaldırıldı', 'renk' => 'warning'],
        'merkez.olusturuldu' => ['ad' => 'Merkez Oluşturuldu', 'renk' => 'success'],
        'merkez.guncellendi' => ['ad' => 'Merkez Güncellendi', 'renk' => 'info'],
        'alan.olusturuldu' => ['ad' => 'Alan Oluşturuldu', 'renk' => 'success'],
        'alan.guncellendi' => ['ad' => 'Alan Güncellendi', 'renk' => 'info'],
        'brans.olusturuldu' => ['ad' => 'Branş Oluşturuldu', 'renk' => 'success'],
        'brans.guncellendi' => ['ad' => 'Branş Güncellendi', 'renk' => 'info'],
        'kisi.olusturuldu' => ['ad' => 'Kişi Oluşturuldu', 'renk' => 'success'],
        'kisi.guncellendi' => ['ad' => 'Kişi Güncellendi', 'renk' => 'info'],
        'kullanici.olusturuldu' => ['ad' => 'Kullanıcı Oluşturuldu', 'renk' => 'success'],
        'kullanici.guncellendi' => ['ad' => 'Kullanıcı Güncellendi', 'renk' => 'info'],
        'kullanici.merkez_yetkilendirildi' => ['ad' => 'Merkez Yetkilendirildi', 'renk' => 'info'],
        'rol.olusturuldu' => ['ad' => 'Rol Oluşturuldu', 'renk' => 'success'],
        'rol.guncellendi' => ['ad' => 'Rol Güncellendi', 'renk' => 'info'],
        'rol.silindi' => ['ad' => 'Rol Silindi', 'renk' => 'danger'],
        'profil.guncellendi' => ['ad' => 'Profil Güncellendi', 'renk' => 'info'],
        'profil.sifre_degistirildi' => ['ad' => 'Şifre Değiştirildi', 'renk' => 'warning'],
        'basvuru.durum_degisti' => ['ad' => 'Başvuru Durumu Değişti', 'renk' => 'info'],
        'basvuru.basari_guncellendi' => ['ad' => 'Başarı Durumu Güncellendi', 'renk' => 'info'],
        'basvuru.kursa_baslama_guncellendi' => ['ad' => 'Kursa Başlama Tarihi Güncellendi', 'renk' => 'info'],
        'ders.yoklama_kaydedildi' => ['ad' => 'Yoklama Kaydedildi', 'renk' => 'success'],
        'ders.yoklama_silindi' => ['ad' => 'Yoklama Silindi', 'renk' => 'danger'],
        'ders.iptal_edildi' => ['ad' => 'Ders İptal Edildi', 'renk' => 'danger'],
        'ders.iptal_geri_alindi' => ['ad' => 'Ders İptali Geri Alındı', 'renk' => 'warning'],
        'ders.tarih_degistirildi' => ['ad' => 'Ders Tarihi Değiştirildi', 'renk' => 'warning'],
        'sms.gonderildi' => ['ad' => 'SMS Gönderildi', 'renk' => 'info'],
        'eposta.gonderildi' => ['ad' => 'E-posta Gönderildi', 'renk' => 'info'],
        'oturum.giris' => ['ad' => 'Giriş Yapıldı', 'renk' => 'success'],
        'oturum.cikis' => ['ad' => 'Çıkış Yapıldı', 'renk' => 'muted'],
        'oturum.basarisiz_giris' => ['ad' => 'Başarısız Giriş', 'renk' => 'danger'],
    ];

    public function getIslemAdiAttribute(): string
    {
        return self::ISLEM_ETIKETLERI[$this->islem]['ad'] ?? $this->islem;
    }

    public function getIslemRengiAttribute(): string
    {
        return self::ISLEM_ETIKETLERI[$this->islem]['renk'] ?? 'muted';
    }

    /**
     * İşlemin ilişkili olduğu modelin okunabilir tür adı (Merkez, Alan, Branş vb.).
     */
    public function getKonuTuruAttribute(): ?string
    {
        return match ($this->konu_tipi) {
            'Kurs' => 'Kurs',
            'Yer', 'Merkez' => 'Merkez',
            'Alan' => 'Alan',
            'Brans' => 'Branş',
            'User' => 'Kullanıcı',
            'Kisi' => 'Kişi',
            'Rol' => 'Rol',
            'KursDers' => 'Ders',
            'KursBasvuru' => 'Başvuru',
            'KursSmsGonderim' => 'SMS Gönderimi',
            'KursEpostaGonderim' => 'E-posta Gönderimi',
            default => $this->konu_tipi,
        };
    }

    /**
     * @return BelongsTo<Kurs, $this>
     */
    public function kurs(): BelongsTo
    {
        return $this->belongsTo(Kurs::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
