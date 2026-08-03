<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class HttpSmsSender implements SmsSender
{
    public function send(string $telefon, string $mesaj, array $context = []): array
    {
        $url = (string) config('services.sms.url');
        $key = (string) config('services.sms.key');
        $from = (string) config('services.sms.from');

        if ($url === '') {
            return ['ok' => false, 'message' => 'SMS API adresi tanımlı değil.'];
        }

        try {
            $response = Http::timeout(15)
                ->withToken($key)
                ->acceptJson()
                ->post($url, [
                    'to' => $telefon,
                    'from' => $from,
                    'message' => $mesaj,
                ]);

            if ($response->successful()) {
                return ['ok' => true, 'message' => 'api'];
            }

            return [
                'ok' => false,
                'message' => $response->json('message') ?: ('HTTP '.$response->status()),
            ];
        } catch (Throwable $e) {
            Log::warning('SMS API hatası', ['error' => $e->getMessage(), 'telefon' => $telefon]);

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
