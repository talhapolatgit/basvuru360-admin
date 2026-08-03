<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hizmet bölgesi (ikamet koşulu)
    |--------------------------------------------------------------------------
    |
    | Merkez kaydında il/ilçe boşsa ikamet şartı bu değerlere göre kontrol edilir.
    | Merkez üzerindeki il/ilçe alanları önceliklidir.
    |
    */

    'hizmet_ili' => env('HIZMET_ILI'),
    'hizmet_ilcesi' => env('HIZMET_ILCESI'),

];
