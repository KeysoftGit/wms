<?php

if (!function_exists('generateRandomString')) {
    function generateRandomString($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    function getRoute($type) {}
}

if (!function_exists('auto_numeric_format')) {
    /**
     * Format angka agar mirip AutoNumeric di frontend.
     *
     * @param float|int|string $value
     * @param int $decimals Jumlah desimal (default 6)
     * @return string
     */
    function auto_numeric_format($value, $decimals = 6)
    {
        if ($value === null || $value === '') {
            return '0';
        }

        $value = (float) $value;

        $formatted = number_format($value, $decimals, ',', '.');

        $formatted = rtrim(rtrim($formatted, '0'), ',');

        return $formatted;
    }
}

if (!function_exists('format_qty')) {
    /**
     * Format qty: if decimals are .00, show integer. Else show 2 decimal places.
     * Use , as decimal separator and . as thousand separator.
     *
     * @param float|int $value
     * @return string
     */
    function format_qty($value)
    {
        $value = (float) $value;
        if (floor($value) == $value) {
            return number_format($value, 0, ',', '.');
        }
        return number_format($value, 2, ',', '.');
    }
}

if (!function_exists('clear_form_preservation')) {
    /**
     * Clear form preservation data on the next page load.
     *
     * @param string $key
     */
    function clear_form_preservation($key)
    {
        session()->flash('clear_form_preservation', $key);
    }
}
