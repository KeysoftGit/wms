<?php

namespace App\Services;

use App\Models\ControlPanel;

class GeneralService
{
    public function formatDate(string $date, string $fromFormat = 'd/m/Y', string $toFormat = 'Y-m-d'): string
    {
        return \DateTime::createFromFormat($fromFormat, $date)->format($toFormat);
    }

    public function nullableDetailValue(?string $value): ?string
    {
        return $value === null || trim($value) === '' ? null : trim($value);
    }

    public function nullableDateValue(?string $value, string $fromFormat = 'd/m/Y', string $toFormat = 'Y-m-d'): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return $this->formatDate($value, $fromFormat, $toFormat);
    }

    public function redirectIfControlPanelDisabled(string $key, string $route): void
    {
        if (!ControlPanel::isEnabled($key)) {
            redirect()->route($route)->send();
        }
    }
}
