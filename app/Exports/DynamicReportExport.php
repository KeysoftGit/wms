<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DynamicReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $report;
    protected $filters;
    protected $rows;

    public function __construct($report, array $filters)
    {
        $this->report = $report;
        $this->filters = $filters;
        $this->rows = $this->buildRows();
    }

    public function collection()
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return $this->report->columns->where('IsVisible', 1)->pluck('Label')->toArray();
    }

    public function map($row): array
    {
        $mapped = [];
        foreach ($this->report->columns->where('IsVisible', 1) as $column) {
            $mapped[] = $row->{$column->FieldName} ?? '';
        }

        return $mapped;
    }

    private function buildRows(): array
    {
        $query = rtrim(trim((string) $this->report->BaseQuery), ';');
        $query = $this->replaceVariables($query);
        $builder = DB::table(DB::raw("($query) as base_report"));

        foreach ($this->report->filters as $filter) {
            $value = $this->filters[$filter->FieldName] ?? $this->filters[$filter->FieldName . '_start'] ?? null;
            if ($filter->FilterType === 'date_range') {
                $start = $this->filters[$filter->FieldName . '_start'] ?? null;
                $end = $this->filters[$filter->FieldName . '_end'] ?? null;
                if ($start) {
                    $builder->where($filter->FieldName, '>=', $start);
                }
                if ($end) {
                    $builder->where($filter->FieldName, '<=', $end);
                }
            } elseif ($value !== null && $value !== '') {
                if (is_array($value)) {
                    $builder->whereIn($filter->FieldName, $value);
                } else {
                    $builder->where($filter->FieldName, 'like', '%' . $value . '%');
                }
            }
        }

        return $builder->get()->toArray();
    }

    private function replaceVariables(string $query): string
    {
        $vars = $this->report->variables->sortByDesc(fn ($v) => strlen($v->VariableName));
        foreach ($vars as $v) {
            $value = $this->filters[$v->VariableName] ?? $this->filters[ltrim($v->VariableName, '@')] ?? null;
            if ($value === null || $value === '') {
                $value = $v->VariableType === 'date' ? now()->format('Y-m-d') : ($v->VariableType === 'numeric' ? 0 : '');
            }

            $sqlVal = in_array($v->VariableType, ['date', 'string'], true)
                ? "'" . str_replace("'", "''", $value) . "'"
                : (is_numeric($value) ? (string) floatval($value) : '0');

            $query = str_ireplace($v->VariableName, $sqlVal, $query);
        }

        if (preg_match('/ORDER\s+BY/i', $query) && !preg_match('/TOP\s+/i', $query)) {
            $query = preg_replace('/^SELECT\s+/i', 'SELECT TOP 100 PERCENT ', $query);
        }

        return $query;
    }
}
