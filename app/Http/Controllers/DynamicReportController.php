<?php

namespace App\Http\Controllers;

use App\Exports\DynamicReportExport;
use App\Models\MsDynamicReportHD;
use App\Models\MsCompanyProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class DynamicReportController extends Controller
{
    public function reportList()
    {
        try {
            $reports = MsDynamicReportHD::orderBy('ParentModule')
                ->orderBy('ReportName')
                ->get()
                ->filter(fn ($report) => $this->canViewReport($report));

            return view('report.dynamic-list', [
                'reports' => $reports,
                'modules' => $this->getAvailableModules(),
            ]);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Invalid object name \'Ms_DynamicReportHD\'')) {
                session()->flash('error', 'Database table Ms_DynamicReportHD is missing. Please run Sync Dynamic Report in Admin > Sync Table.');

                return view('report.dynamic-list', [
                    'reports' => collect(),
                    'modules' => $this->getAvailableModules(),
                ]);
            }

            throw $e;
        }
    }

    public function index($slug)
    {
        $report = MsDynamicReportHD::with(['columns', 'filters', 'variables'])
            ->where('Slug', $slug)
            ->firstOrFail();

        $this->authorizeReportView($report);

        $filterOptions = [];
        foreach ($report->filters as $filter) {
            if ($filter->FilterType === 'select' && $filter->DataSource) {
                try {
                    $filterOptions[$filter->FieldName] = DB::select($filter->DataSource);
                } catch (\Throwable $e) {
                    $filterOptions[$filter->FieldName] = [];
                }
            }
        }

        return view('report.dynamic', compact('report', 'filterOptions'));
    }

    public function data(Request $request, $slug)
    {
        try {
            $report = MsDynamicReportHD::with(['columns', 'filters', 'variables'])
                ->where('Slug', $slug)
                ->firstOrFail();

            $this->authorizeReportView($report);

            $baseQuery = $this->buildBaseQueryWithVariables($report, $request);
            $query = DB::table(DB::raw("($baseQuery) as base_report"));
            $this->applyReportFilters($query, $report, $request);

            $totals = $this->calculateTotals($query, $report);
            $grouping = $this->normalizeGrouping($request->get('grouping', []));

            $datatables = DataTables::of($query);
            $datatables->order(function ($query) use ($grouping, $request, $report) {
                $orderedColumns = [];

                foreach ($grouping as $field) {
                    $query->orderBy($field, 'asc');
                    $orderedColumns[] = $field;
                }

                $orders = $request->get('order', []);
                if (is_array($orders)) {
                    foreach ($orders as $order) {
                        $columnIndex = $order['column'] ?? null;
                        $direction = $order['dir'] ?? 'asc';
                        $columnName = data_get($request->get('columns', []), $columnIndex . '.name');

                        if ($columnName && !in_array($columnName, $orderedColumns, true)) {
                            $query->orderBy($columnName, $direction);
                            $orderedColumns[] = $columnName;
                        }
                    }
                }

                if (empty($orderedColumns) && $report->columns->first()) {
                    $query->orderBy($report->columns->first()->FieldName, 'asc');
                }
            });

            foreach ($report->columns as $col) {
                $field = $col->FieldName;
                $datatables->editColumn($field, function ($row) use ($field, $col) {
                    $val = $row->{$field} ?? null;

                    if ($col->Format === 'currency' && is_numeric($val)) {
                        return number_format($val, 0, ',', '.');
                    }

                    $dateFormats = ['d/m/Y', 'd M Y', 'Y-m-d', 'd/m/Y H:i', 'H:i'];
                    if ($col->DataType === 'date' || in_array($col->Format, $dateFormats, true)) {
                        try {
                            return $val ? Carbon::parse($val)->format($col->Format ?: 'd/m/Y') : '-';
                        } catch (\Exception $e) {
                            return $val;
                        }
                    }

                    if ($col->DataType === 'numeric' && is_numeric($val)) {
                        $formatted = number_format($val, 2, ',', '.');
                        return rtrim(rtrim($formatted, '0'), ',');
                    }

                    if ($col->DataType === 'badge') {
                        $class = 'info';
                        $lVal = strtolower((string) $val);
                        if (in_array($lVal, ['open', 'active', 'success'], true)) {
                            $class = 'success';
                        } elseif (in_array($lVal, ['closed', 'inactive', 'danger'], true)) {
                            $class = 'danger';
                        } elseif (in_array($lVal, ['pending', 'warning'], true)) {
                            $class = 'warning';
                        }

                        return '<span class="badge bg-' . $class . '">' . e($val) . '</span>';
                    }

                    return $val;
                });
            }

            $response = $datatables->rawColumns($report->columns->where('DataType', 'badge')->pluck('FieldName')->toArray())
                ->with('totals', $totals)
                ->make(true)
                ->getData(true);

            $response['groupMappings'] = $this->getGroupMappings($report, $grouping, collect($response['data'] ?? []));

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error("DynamicReport Data Error ($slug): " . $e->getMessage());
            return response()->json(['error' => true, 'message' => $e->getMessage()], 500);
        }
    }

    public function matrix($slug)
    {
        $report = MsDynamicReportHD::with(['columns', 'filters', 'variables'])
            ->where('Slug', $slug)
            ->firstOrFail();

        $this->authorizeReportView($report);

        $filterOptions = [];
        foreach ($report->filters as $filter) {
            if ($filter->FilterType === 'select' && $filter->DataSource) {
                try {
                    $filterOptions[$filter->FieldName] = DB::select($filter->DataSource);
                } catch (\Throwable $e) {
                    $filterOptions[$filter->FieldName] = [];
                }
            }
        }

        return view('report.dynamic-matrix', compact('report', 'filterOptions'));
    }

    public function matrixData(Request $request, $slug)
    {
        try {
            $report = MsDynamicReportHD::with(['columns', 'filters', 'variables'])
                ->where('Slug', $slug)
                ->firstOrFail();

            $this->authorizeReportView($report);

            $availableFields = $report->columns->pluck('FieldName')->toArray();
            $rowFields = array_values(array_intersect((array) $request->input('row_fields', []), $availableFields));
            $columnFields = array_values(array_intersect((array) $request->input('column_fields', []), $availableFields));
            $valueField = $request->input('value_field');
            $aggregate = strtoupper($request->input('aggregate', 'SUM'));
            $allowedAggregates = ['SUM', 'AVG', 'COUNT', 'MIN', 'MAX'];

            if (empty($rowFields) || empty($columnFields)) {
                return response()->json(['message' => 'Row fields and column fields are required.'], 422);
            }

            if (!in_array($aggregate, $allowedAggregates, true)) {
                $aggregate = 'SUM';
            }

            if ($aggregate !== 'COUNT' && !in_array($valueField, $availableFields, true)) {
                return response()->json(['message' => 'Value field is required for this aggregate.'], 422);
            }

            $baseQuery = $this->buildBaseQueryWithVariables($report, $request);
            $query = DB::table(DB::raw("($baseQuery) as base_report"));
            $this->applyReportFilters($query, $report, $request);

            $selects = [];
            $groupFields = array_values(array_unique(array_merge($rowFields, $columnFields)));

            foreach ($rowFields as $index => $field) {
                $selects[] = DB::raw($this->safeSqlIdentifier($field) . " as [row_$index]");
            }

            foreach ($columnFields as $index => $field) {
                $selects[] = DB::raw($this->safeSqlIdentifier($field) . " as [col_$index]");
            }

            if ($aggregate === 'COUNT') {
                $selects[] = DB::raw('COUNT_BIG(*) as [matrix_value]');
            } else {
                $valueExpression = $this->safeSqlIdentifier($valueField);
                $selects[] = DB::raw($aggregate . '(CAST(' . $valueExpression . ' as decimal(38, 6))) as [matrix_value]');
            }

            $query->select($selects);
            foreach ($groupFields as $field) {
                $query->groupBy(DB::raw($this->safeSqlIdentifier($field)));
                $query->orderBy(DB::raw($this->safeSqlIdentifier($field)));
            }

            $items = collect($query->get());
            $matrix = $this->buildMatrixPayload($items, $report, $rowFields, $columnFields, $valueField, $aggregate);

            return response()->json($matrix);
        } catch (\Exception $e) {
            Log::error("DynamicReport Matrix Error ($slug): " . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function export(Request $request, $slug)
    {
        $report = MsDynamicReportHD::with(['columns', 'filters', 'variables'])
            ->where('Slug', $slug)
            ->firstOrFail();

        $this->authorizeReportView($report);

        $fileName = $report->ReportName . '_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new DynamicReportExport($report, $request->all()), $fileName);
    }

    public function exportPdf(Request $request, $slug)
    {
        $report = MsDynamicReportHD::with(['columns', 'filters', 'variables'])
            ->where('Slug', $slug)
            ->firstOrFail();

        $this->authorizeReportView($report);

        $fileName = $report->ReportName . '_' . now()->format('Ymd_His') . '.pdf';
        return Excel::download(new DynamicReportExport($report, $request->all()), $fileName, \Maatwebsite\Excel\Excel::DOMPDF);
    }

    public function preview(Request $request, $slug)
    {
        $report = MsDynamicReportHD::with(['columns', 'filters', 'variables'])
            ->where('Slug', $slug)
            ->firstOrFail();

        $this->authorizeReportView($report);

        $company = MsCompanyProfile::find(1);
        $baseQuery = $this->buildBaseQueryWithVariables($report, $request);
        $query = DB::table(DB::raw("($baseQuery) as t"));
        $this->applyReportFilters($query, $report, $request);
        $sortBy = $request->get('sort_by');
        if ($sortBy) {
            $query->orderBy($sortBy, $request->get('sort_dir', 'asc'));
        }

        $data = $query->get();
        $columns = $report->columns->where('IsVisible', 1);
        $groupingFields = $this->normalizeGrouping($request->get('grouping', []));
        $groupMappings = $this->getGroupMappings($report, $groupingFields, $data);
        $appliedParams = $this->appliedParameters($report, $request);
        $groupedRows = empty($groupingFields)
            ? []
            : $this->groupDataRecursive($data, $groupingFields, $columns);

        return view('report.preview', compact(
            'report',
            'company',
            'data',
            'columns',
            'groupingFields',
            'groupMappings',
            'appliedParams',
            'groupedRows'
        ));
    }

    private function getAvailableModules(): array
    {
        return [
            'Accounting' => 'Accounting',
            'Common' => 'Common',
            'Users' => 'Users',
            'Inventory' => 'Inventory',
            'BAST' => 'BAST',
            'Leads' => 'Leads',
            'RAB' => 'RAB',
            'Purchase' => 'Purchase',
            'Sales' => 'Sales',
            'Stock' => 'Stock',
            'Production' => 'Production',
            'Technician' => 'Technician',
            'Finance' => 'Finance',
            'Journal' => 'Journal',
            'Fixed Asset' => 'Fixed Asset',
        ];
    }

    private function authorizeReportView(MsDynamicReportHD $report): void
    {
        abort_unless($this->canViewReport($report), 403);
    }

    private function canViewReport(MsDynamicReportHD $report): bool
    {
        return auth()->user()->hasAnyPermission([
            'admin',
            'report.view',
            $this->reportPermissionName($report),
        ]);
    }

    private function reportPermissionName(MsDynamicReportHD $report): string
    {
        return 'dynamic_report_' . str_replace('-', '_', strtolower($report->Slug)) . '.view';
    }

    private function buildBaseQueryWithVariables(MsDynamicReportHD $report, Request $request): string
    {
        $baseQuery = rtrim(trim((string) $report->BaseQuery), ';');
        $sortedVars = $report->variables->sortByDesc(fn ($v) => strlen($v->VariableName));

        foreach ($sortedVars as $v) {
            $varName = $v->VariableName;
            $varNameClean = ltrim($varName, '@');
            $val = $request->get($varName)
                ?? $request->get($varNameClean)
                ?? $request->get(str_replace('.', '_', $varNameClean));

            if ($val === null || $val === '') {
                $val = $v->VariableType === 'date' ? now()->format('Y-m-d') : ($v->VariableType === 'numeric' ? 0 : '');
            }

            $sqlVal = in_array($v->VariableType, ['date', 'string'], true)
                ? "'" . str_replace("'", "''", $val) . "'"
                : (is_numeric($val) ? (string) floatval($val) : '0');

            $baseQuery = str_ireplace($v->VariableName, $sqlVal, $baseQuery);
        }

        if (preg_match('/ORDER\s+BY/i', $baseQuery) && !preg_match('/TOP\s+/i', $baseQuery)) {
            if (preg_match('/^SELECT\s+DISTINCT/i', $baseQuery)) {
                $baseQuery = preg_replace('/^SELECT\s+DISTINCT/i', 'SELECT DISTINCT TOP 100 PERCENT', $baseQuery);
            } else {
                $baseQuery = preg_replace('/^SELECT\s+/i', 'SELECT TOP 100 PERCENT ', $baseQuery);
            }
        }

        return $baseQuery;
    }

    private function applyReportFilters($query, MsDynamicReportHD $report, Request $request): void
    {
        foreach ($report->filters as $filter) {
            $fieldName = $filter->FieldName;
            $paramName = str_replace(['.', ' '], '_', $fieldName);
            $safeField = $this->safeSqlIdentifier($fieldName);

            if ($filter->FilterType === 'date_range') {
                $start = $request->get($fieldName . '_start') ?? $request->get($paramName . '_start');
                $end = $request->get($fieldName . '_end') ?? $request->get($paramName . '_end');

                if ($start) {
                    $query->where(DB::raw($safeField), '>=', $start);
                }
                if ($end) {
                    $query->where(DB::raw($safeField), '<=', $end);
                }
            } else {
                $value = $request->get($fieldName) ?? $request->get($paramName);

                if ($value !== null && $value !== '') {
                    if ($filter->FilterType === 'select') {
                        $values = is_array($value) ? $value : [$value];
                        $query->whereIn(DB::raw($safeField), $values);
                    } else {
                        $query->where(DB::raw($safeField), 'like', '%' . $value . '%');
                    }
                }
            }
        }
    }

    private function normalizeGrouping($grouping): array
    {
        if (!is_array($grouping)) {
            $grouping = array_filter(explode(',', (string) $grouping));
        }

        return array_values(array_filter($grouping));
    }

    private function safeSqlIdentifier(string $field): string
    {
        $field = trim($field);
        if (str_starts_with($field, '[') && str_ends_with($field, ']')) {
            return $field;
        }

        return '[' . str_replace(']', ']]', $field) . ']';
    }

    private function calculateTotals($query, MsDynamicReportHD $report): array
    {
        $totals = [];
        $aggregateCols = $report->columns->whereNotNull('AggregateFunction')->where('AggregateFunction', '!=', '');

        if ($aggregateCols->count() === 0) {
            return $totals;
        }

        $totalQuery = clone $query;
        $selects = [];
        foreach ($aggregateCols as $col) {
            $field = $col->FieldName;
            $func = strtoupper($col->AggregateFunction);
            $selects[] = $func . '(' . $this->safeSqlIdentifier($field) . ') as [total_' . $field . ']';
        }

        $summary = $totalQuery->select(DB::raw(implode(', ', $selects)))->first();
        if ($summary) {
            foreach ($aggregateCols as $col) {
                $key = 'total_' . $col->FieldName;
                $val = $summary->$key ?? 0;

                if ($col->Format === 'currency') {
                    $totals[$col->FieldName] = number_format($val, 0, ',', '.');
                } elseif ($col->DataType === 'numeric') {
                    $totals[$col->FieldName] = rtrim(rtrim(number_format($val, 2, ',', '.'), '0'), ',');
                } else {
                    $totals[$col->FieldName] = $val;
                }
            }
        }

        return $totals;
    }

    private function getGroupMappings($report, array $grouping, $items): array
    {
        if (empty($grouping)) {
            return [];
        }

        $mappings = [];
        foreach ($grouping as $gField) {
            $col = $report->columns->firstWhere('FieldName', $gField);
            if (!$col || empty(trim($col->GroupMapping))) {
                continue;
            }

            $uniqueValues = collect($items)->pluck($gField)->map(function ($v) {
                return is_string($v) ? trim(strip_tags($v)) : $v;
            })->unique()->filter(fn ($v) => $v !== null && $v !== '' && $v !== '-')->values()->toArray();

            if (empty($uniqueValues)) {
                continue;
            }

            $mappingQuery = $col->GroupMapping;

            try {
                $results = [];
                if (stripos($mappingQuery, ':values') !== false) {
                    $placeholders = implode(',', array_map(fn ($v) => "'" . str_replace("'", "''", $v) . "'", $uniqueValues));
                    $results = DB::select(str_ireplace(':values', $placeholders, $mappingQuery));
                } elseif (stripos($mappingQuery, ':value') !== false) {
                    foreach ($uniqueValues as $val) {
                        $res = DB::select(str_ireplace(':value', "'" . str_replace("'", "''", $val) . "'", $mappingQuery));
                        if (!empty($res)) {
                            $results[] = $res[0];
                        }
                    }
                } else {
                    $results = DB::select($mappingQuery);
                }

                foreach ($results as $res) {
                    $resArr = (array) $res;
                    $valKey = null;
                    $details = [];

                    foreach ($resArr as $k => $v) {
                        if (strtolower($k) === 'value') {
                            $valKey = $v;
                        } else {
                            $details[$k] = $v;
                        }
                    }

                    if ($valKey !== null) {
                        $mappings[$gField][$valKey] = count($details) === 1 && isset($details['description'])
                            ? $details['description']
                            : $details;
                    }
                }
            } catch (\Exception $e) {
                Log::error("GroupMapping Error ($gField): " . $e->getMessage());
            }
        }

        return $mappings;
    }

    private function appliedParameters(MsDynamicReportHD $report, Request $request): array
    {
        $params = [];
        foreach ($report->variables as $variable) {
            $value = $request->get($variable->VariableName) ?? $request->get(ltrim($variable->VariableName, '@'));
            $params[$variable->Label] = $variable->VariableType === 'date' && $value
                ? Carbon::parse($value)->format('d/m/Y')
                : ($value ?? '-');
        }

        foreach ($report->filters as $filter) {
            if ($filter->FilterType === 'date_range') {
                $start = $request->get($filter->FieldName . '_start');
                $end = $request->get($filter->FieldName . '_end');
                if ($start || $end) {
                    $params[$filter->Label] = trim(($start ? Carbon::parse($start)->format('d/m/Y') : '') . ' - ' . ($end ? Carbon::parse($end)->format('d/m/Y') : ''), ' -');
                }
            } else {
                $value = $request->get($filter->FieldName);
                if ($value !== null && $value !== '') {
                    $params[$filter->Label] = is_array($value) ? implode(', ', $value) : $value;
                }
            }
        }

        return $params;
    }

    private function buildMatrixPayload($items, MsDynamicReportHD $report, array $rowFields, array $columnFields, ?string $valueField, string $aggregate): array
    {
        $columnMap = [];
        $rowMap = [];
        $grandTotals = [];
        $overallTotal = 0;

        foreach ($items as $item) {
            $rowLabels = [];
            foreach ($rowFields as $index => $field) {
                $rowLabels[] = $item->{'row_' . $index} ?? '';
            }

            $columnLabels = [];
            foreach ($columnFields as $index => $field) {
                $columnLabels[] = $item->{'col_' . $index} ?? '';
            }

            $rowKey = implode('||', array_map(fn ($value) => (string) $value, $rowLabels));
            $columnKey = implode('||', array_map(fn ($value) => (string) $value, $columnLabels));
            $value = (float) ($item->matrix_value ?? 0);

            if (!isset($columnMap[$columnKey])) {
                $columnMap[$columnKey] = [
                    'key' => $columnKey,
                    'labels' => $columnLabels,
                    'label' => implode(' / ', $columnLabels),
                ];
            }

            if (!isset($rowMap[$rowKey])) {
                $rowMap[$rowKey] = [
                    'key' => $rowKey,
                    'labels' => $rowLabels,
                    'values' => [],
                    'raw_values' => [],
                    'raw_total' => 0,
                ];
            }

            $rowMap[$rowKey]['raw_values'][$columnKey] = ($rowMap[$rowKey]['raw_values'][$columnKey] ?? 0) + $value;
            $rowMap[$rowKey]['raw_total'] += $value;
            $grandTotals[$columnKey] = ($grandTotals[$columnKey] ?? 0) + $value;
            $overallTotal += $value;
        }

        $columns = array_values($columnMap);
        $valueColumn = $valueField ? $report->columns->firstWhere('FieldName', $valueField) : null;

        foreach ($rowMap as &$row) {
            foreach ($columns as $column) {
                $rawValue = $row['raw_values'][$column['key']] ?? 0;
                $row['values'][$column['key']] = $this->formatMatrixValue($rawValue, $valueColumn, $aggregate);
            }

            $row['total'] = $this->formatMatrixValue($row['raw_total'], $valueColumn, $aggregate);
        }

        $formattedGrandTotals = [];
        foreach ($columns as $column) {
            $formattedGrandTotals[$column['key']] = $this->formatMatrixValue($grandTotals[$column['key']] ?? 0, $valueColumn, $aggregate);
        }

        return [
            'row_fields' => $this->matrixFieldLabels($report, $rowFields),
            'column_fields' => $this->matrixFieldLabels($report, $columnFields),
            'value_field' => $valueField ? ($valueColumn->Label ?? $valueField) : 'Count',
            'aggregate' => $aggregate,
            'columns' => $columns,
            'rows' => array_values($rowMap),
            'grand_totals' => $formattedGrandTotals,
            'overall_total' => $this->formatMatrixValue($overallTotal, $valueColumn, $aggregate),
        ];
    }

    private function matrixFieldLabels(MsDynamicReportHD $report, array $fields): array
    {
        return collect($fields)->map(function ($field) use ($report) {
            $column = $report->columns->firstWhere('FieldName', $field);

            return [
                'field' => $field,
                'label' => $column->Label ?? $field,
            ];
        })->values()->toArray();
    }

    private function formatMatrixValue(float $value, $column, string $aggregate): string
    {
        if ($aggregate === 'COUNT') {
            return number_format($value, 0, ',', '.');
        }

        if ($column && $column->Format === 'currency') {
            return number_format($value, 0, ',', '.');
        }

        return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
    }

    private function groupDataRecursive($data, array $fields, $columns)
    {
        if (empty($fields)) {
            return $data;
        }

        $tempFields = array_values($fields);
        $field = array_shift($tempFields);
        $grouped = $data->groupBy($field);

        return $grouped->map(function ($items) use ($tempFields, $columns) {
            return [
                'items' => $this->groupDataRecursive($items, $tempFields, $columns),
                'count' => $items->count(),
                'totals' => $this->calculateGroupTotals($items, $columns),
            ];
        });
    }

    private function calculateGroupTotals($items, $columns): array
    {
        $totals = [];
        $aggregateCols = $columns->whereNotNull('AggregateFunction')->where('AggregateFunction', '!=', '');

        foreach ($aggregateCols as $col) {
            $field = $col->FieldName;
            $func = strtoupper($col->AggregateFunction);

            if ($func === 'SUM') {
                $val = $items->sum($field);
            } elseif ($func === 'AVG') {
                $val = $items->avg($field);
            } elseif ($func === 'COUNT') {
                $val = $items->count();
            } elseif ($func === 'MIN') {
                $val = $items->min($field);
            } elseif ($func === 'MAX') {
                $val = $items->max($field);
            } else {
                $val = 0;
            }

            $totals[$field] = $val;
        }

        return $totals;
    }
}
