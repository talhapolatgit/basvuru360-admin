<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;

class HealthController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        return $this->success([
            'status' => 'ok',
            'service' => 'basvuru360-api',
            'version' => 'v1',
            'time' => now()->toIso8601String(),
        ]);
    }
}
