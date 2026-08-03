<?php

namespace App\Support;

final class HtmlMetin
{
    private const IZINLI_ETIKETLER = '<p><br><strong><b><em><i><u><ul><ol><li><a><h2><h3><h4>';

    public static function temizle(?string $html): ?string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return null;
        }

        $temiz = trim(strip_tags($html, self::IZINLI_ETIKETLER));
        if ($temiz === '' || self::bosMu($temiz)) {
            return null;
        }

        return $temiz;
    }

    public static function bosMu(?string $html): bool
    {
        $html = trim((string) $html);
        if ($html === '') {
            return true;
        }

        $duz = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $duz = preg_replace('/\x{00A0}|\s+/u', '', $duz) ?? '';

        return $duz === '';
    }

    public static function baslik(?string $baslik, string $varsayilan): string
    {
        $baslik = trim((string) $baslik);

        return $baslik !== '' ? $baslik : $varsayilan;
    }
}
