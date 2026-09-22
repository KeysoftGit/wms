<?php

namespace App\Services;

class FormatService
{

    function formatPrice($price)
    {
        // Format dengan 6 decimal fixed
        $formatted = sprintf('%.6f', $price);

        // Hapus trailing zeros dan titik jika perlu
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        // Pisahkan bagian integer dan decimal
        $parts = explode('.', $formatted);

        // Tambahkan thousand separator ke bagian integer saja, dengan '.' sebagai separator
        $parts[0] = number_format($parts[0], 0, '', '.');

        // Gabung dengan ',' sebagai pemisah desimal
        return isset($parts[1]) ? $parts[0] . ',' . $parts[1] : $parts[0];
    }
}
