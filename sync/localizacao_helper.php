<?php
if (!function_exists('extrair_coordenadas_google_maps')) {
    function extrair_coordenadas_google_maps($link) {
        $link = trim((string)$link);
        if ($link === '') return null;

        // Segue o redirect de shortlinks (maps.app.goo.gl) para obter a URL completa
        if (stripos($link, 'maps.app.goo.gl') !== false || stripos($link, 'maps.google.com') !== false) {
            $ch = curl_init($link);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
            curl_exec($ch);
            if (curl_errno($ch) === CURLE_OK) {
                $link = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            }
            curl_close($ch);
        }

        // Padrão: https://www.google.com/maps/place/.../@-20.646,-40.484,17z
        if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $link, $m)) {
            return ['latitude' => (float)$m[1], 'longitude' => (float)$m[2]];
        }

        // Padrão: ...!3d-20.646!4d-40.481
        if (preg_match('/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', $link, $m)) {
            return ['latitude' => (float)$m[1], 'longitude' => (float)$m[2]];
        }

        return null;
    }
}
