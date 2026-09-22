@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Matrix {{ $report->ReportName }}</title>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 mb-3">
            <div>
                <a href="{{ route('report.dynamic.index', $report->Slug) }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                <h1 class="h3 fw-bold mb-1 text-dark d-inline-block ms-3">Matrix: {{ $report->ReportName }}</h1>
                <p class="fs-sm fw-medium text-muted mb-0">{{ $report->Description ?: 'Cross-tab report view.' }}</p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form id="matrix-form">
                    <div class="row g-3">
                        @foreach($report->variables as $v)
                            <div class="col-md-3">
                                <label class="form-label">{{ $v->Label }}</label>
                                @if($v->VariableType === 'date')
                                    <input type="date" class="form-control" name="{{ $v->VariableName }}" value="{{ now()->format('Y-m-d') }}">
                                @elseif($v->VariableType === 'numeric')
                                    <input type="number" step="any" class="form-control" name="{{ $v->VariableName }}" value="0">
                                @else
                                    <input type="text" class="form-control" name="{{ $v->VariableName }}">
                                @endif
                            </div>
                        @endforeach

                        @foreach($report->filters as $filter)
                            <div class="col-md-3">
                                <label class="form-label">{{ $filter->Label }}</label>
                                @if($filter->FilterType === 'date_range')
                                    <div class="d-flex gap-2">
                                        <input type="date" class="form-control" name="{{ $filter->FieldName }}_start">
                                        <input type="date" class="form-control" name="{{ $filter->FieldName }}_end">
                                    </div>
                                @elseif($filter->FilterType === 'select')
                                    <select class="form-select" name="{{ $filter->FieldName }}[]" multiple>
                                        @foreach(($filterOptions[$filter->FieldName] ?? []) as $opt)
                                            @php
                                                $optArray = (array) $opt;
                                                $val = reset($optArray);
                                                $label = next($optArray);
                                                if ($label === false) $label = $val;
                                            @endphp
                                            <option value="{{ $val }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" class="form-control" name="{{ $filter->FieldName }}">
                                @endif
                            </div>
                        @endforeach

                        <div class="col-12 border-top pt-3 mt-3">
                            <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Rows</label>
                            <select class="js-select2 form-select" id="row-fields" name="row_fields[]" multiple required>
                                @foreach($report->columns as $col)
                                    <option value="{{ $col->FieldName }}">{{ $col->Label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Columns</label>
                            <select class="js-select2 form-select" id="column-fields" name="column_fields[]" multiple required>
                                @foreach($report->columns as $col)
                                    <option value="{{ $col->FieldName }}">{{ $col->Label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Value</label>
                            <select class="js-select2 form-select" id="value-field" name="value_field">
                                <option value=""></option>
                                @foreach($report->columns as $col)
                                    <option value="{{ $col->FieldName }}">{{ $col->Label }}</option>
                                @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Aggregate</label>
                                    <select class="form-select" name="aggregate" id="matrix-aggregate">
                                        <option value="SUM">SUM</option>
                                        <option value="AVG">AVG</option>
                                        <option value="COUNT">COUNT</option>
                                        <option value="MIN">MIN</option>
                                        <option value="MAX">MAX</option>
                                    </select>
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100" id="generate-matrix">
                                        <i class="fa fa-bolt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card d-none" id="matrix-block">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-vcenter mb-0" id="matrix-table"></table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @php
        $matrixFields = $report->columns->map(function ($col) {
            return [
                'field' => $col->FieldName,
                'label' => $col->Label,
                'type' => $col->DataType,
                'format' => $col->Format,
            ];
        })->values();
    @endphp
    <script>
        $(function () {
            const fields = @json($matrixFields);

            $('.js-select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true
            });

            applyMatrixDefaults();

            $('#matrix-aggregate').on('change', function () {
                const isCount = $(this).val() === 'COUNT';
                $('#value-field').prop('disabled', isCount).trigger('change.select2');
            });

            $('#matrix-form').on('submit', function (e) {
                e.preventDefault();
                const btn = $('#generate-matrix');
                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

                $.ajax({
                    url: '{{ route('report.dynamic.matrix-data', $report->Slug) }}',
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: $(this).serialize()
                }).done(function (response) {
                    renderMatrix(response);
                    $('#matrix-block').removeClass('d-none');
                }).fail(function (xhr) {
                    Swal.fire('Matrix Error', xhr.responseJSON ? xhr.responseJSON.message : 'Failed to generate matrix', 'error');
                }).always(function () {
                    btn.prop('disabled', false).html('<i class="fa fa-bolt"></i>');
                });
            });

            function applyMatrixDefaults() {
                const rowDefaults = fields
                    .filter(field => /salesman|customer|supplier|part|item|warehouse|division/i.test(field.field + ' ' + field.label))
                    .map(field => field.field)
                    .slice(0, 2);

                const columnDefaults = fields
                    .filter(field => /^(year|month|date|period)$/i.test(field.field) || /year|month|date|period/i.test(field.label))
                    .map(field => field.field)
                    .slice(0, 2);

                const valueDefault = (fields.find(field => /amount|total|value|qty|quantity|balance/i.test(field.field + ' ' + field.label)) || {}).field;

                const rowValue = rowDefaults.length ? rowDefaults : [fields[0]?.field].filter(Boolean);
                const columnValue = columnDefaults.length ? columnDefaults : [fields[1]?.field].filter(Boolean);

                $('#row-fields').val(rowValue).trigger('change');
                $('#column-fields').val(columnValue).trigger('change');
                $('#value-field').val(valueDefault || '').trigger('change');
            }

            function renderMatrix(data) {
                let html = '<thead><tr><th>Row</th>';
                data.columns.forEach(function (col) {
                    html += '<th class="text-end">' + col.label + '</th>';
                });
                html += '<th class="text-end">Total</th></tr></thead><tbody>';

                data.rows.forEach(function (row) {
                    html += '<tr><td><strong>' + row.labels.join(' / ') + '</strong></td>';
                    data.columns.forEach(function (col) {
                        html += '<td class="text-end">' + (row.values[col.key] ?? '0') + '</td>';
                    });
                    html += '<td class="text-end"><strong>' + row.total + '</strong></td></tr>';
                });

                html += '</tbody><tfoot><tr><td><strong>Grand Total</strong></td>';
                data.columns.forEach(function (col) {
                    html += '<td class="text-end"><strong>' + (data.grand_totals[col.key] ?? '0') + '</strong></td>';
                });
                html += '<td class="text-end"><strong>' + data.overall_total + '</strong></td></tr></tfoot>';

                $('#matrix-table').html(html);
            }
        });
    </script>
@endsection
