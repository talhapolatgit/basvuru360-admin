<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Services\Entegrasyon\EntegrasyonAyarServisi;
use App\Services\GenelAyarServisi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GenelAyarController extends ApiController
{
    public function __invoke(GenelAyarServisi $servis, EntegrasyonAyarServisi $entegrasyon): JsonResponse
    {
        $form = $servis->formVerisi();
        $yontem = $servis->kisiGirisYontemi();

        return $this->success([
            'kurum_adi' => $form['kurum_adi'],
            'telefon' => $form['telefon'],
            'eposta' => $form['eposta'],
            'il' => $form['il'],
            'ilce' => $form['ilce'],
            'adres' => $form['adres'],
            'logo_url' => $servis->apiLogoUrl(),
            'favicon_url' => $servis->apiFaviconUrl(),
            'web_sitesi' => $form['web_sitesi'],
            'site_aciklama' => $form['site_aciklama'] !== ''
                ? $form['site_aciklama']
                : null,
            'sidebar_logo_url' => $servis->apiSidebarLogoUrl(),
            'sidebar_logo_arkaplan' => $form['sidebar_logo_arkaplan'],
            'header_logo_url' => $servis->apiHeaderLogoUrl(),
            'sidebar_arkaplan' => $form['sidebar_arkaplan'],
            'sidebar_arkaplan_tip' => $form['sidebar_arkaplan_tip'],
            'sidebar_baslik' => $form['sidebar_baslik'] !== ''
                ? $form['sidebar_baslik']
                : null,
            'sidebar_alt_baslik' => $form['sidebar_alt_baslik'] !== ''
                ? $form['sidebar_alt_baslik']
                : null,
            'kisi_giris_yontemi' => [
                'kod' => $yontem->value,
                'label' => $yontem->label(),
            ],
            'kisi_giris_yontemi_secenekler' => $form['kisi_giris_yontemi_secenekler'],
            'yakin_icin_basvuru_aktif' => $servis->yakinIcinBasvuruAktif(),
            'manuel_yakin_ekleme_aktif' => $servis->manuelYakinEklemeAktif(),
            'kimlik_sorgulama_aktif' => $entegrasyon->turAktifMi('kimlik_sorgulama'),
        ]);
    }

    public function logo(GenelAyarServisi $servis): BinaryFileResponse|Response
    {
        return $this->gorselYaniti($servis->logoDosyaYolu());
    }

    public function sidebarLogo(GenelAyarServisi $servis): BinaryFileResponse|Response
    {
        return $this->gorselYaniti($servis->sidebarLogoDosyaYolu());
    }

    public function headerLogo(GenelAyarServisi $servis): BinaryFileResponse|Response
    {
        return $this->gorselYaniti($servis->headerLogoDosyaYolu());
    }

    public function favicon(GenelAyarServisi $servis): BinaryFileResponse|Response
    {
        return $this->gorselYaniti($servis->faviconDosyaYolu(), 'Favicon bulunamadı.');
    }

    private function gorselYaniti(?string $path, string $notFound = 'Logo bulunamadı.'): BinaryFileResponse|Response
    {
        if ($path === null) {
            return response($notFound, 404);
        }

        $mime = mime_content_type($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
