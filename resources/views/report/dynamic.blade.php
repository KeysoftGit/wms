@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - {{ $report->ReportName }}</title>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.4.1/css/rowGroup.bootstrap5.min.css">
    <link rel="stylesheet" href="{{ asset('js/plugins/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 mb-3">
            <div class="flex-grow-1">
                <a href="{{ route('report.dynamic.list') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                <h1 class="h3 fw-bold mb-1 text-dark d-inline-block ms-3">{{ $report->ReportName }}</h1>
                <p class="fs-sm fw-medium text-muted mb-0">{{ $report->Description ?: 'Dynamic report.' }}</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2 flex-wrap">
                <a href="{{ route('report.dynamic.matrix', $report->Slug) }}" class="btn btn-sm btn-alt-secondary">
                    <i class="fa fa-table-cells-large me-1"></i> Matrix
                </a>
                <button type="button" class="btn btn-sm btn-success" id="export-excel">
                    <i class="fa fa-file-excel me-1"></i> Excel
                </button>
                <button type="button" class="btn btn-sm btn-danger" id="export-pdf">
                    <i class="fa fa-file-pdf me-1"></i> PDF
                </button>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form id="filter-form">
                    <div class="row g-3">
                        @if($report->variables->count() > 0)
                            <div class="col-12">
                                <span class="badge bg-warning text-dark"><i class="fa fa-cog me-1"></i> REPORT PARAMETERS</span>
                            </div>
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
                        @endif

                        @foreach($report->filters as $filter)
                            <div class="col-md-3">
                                <label class="form-label">{{ $filter->Label }}</label>
                                @if($filter->FilterType === 'date_range')
                                    <div class="d-flex gap-2">
                                        <input type="date" class="form-control" name="{{ $filter->FieldName }}_start">
                                        <input type="date" class="form-control" name="{{ $filter->FieldName }}_end">
                                    </div>
                                @elseif($filter->FilterType === 'select')
                                    <select class="form-select" name="{{ $filter->FieldName }}[]" multiple data-placeholder="Show All">
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
                                    <input type="text" class="form-control" name="{{ $filter->FieldName }}" placeholder="Search..">
                                @endif
                            </div>
                        @endforeach

                        <div class="col-md-3">
                            <label class="form-label">Grouping Levels</label>
                            <select class="form-select" id="report-grouping" name="grouping[]" multiple data-placeholder="Level 1, Level 2..">
                                @foreach($report->columns->where('IsGroupable', 1) as $gCol)
                                    <option value="{{ $gCol->FieldName }}">{{ $gCol->Label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Sort By</label>
                            <div class="input-group">
                                <select class="form-select" id="report-sort-by" name="sort_by">
                                    <option value="">Default Order</option>
                                    @foreach($report->columns->where('IsSortable', 1) as $sCol)
                                        <option value="{{ $sCol->FieldName }}">{{ $sCol->Label }}</option>
                                    @endforeach
                                </select>
                                <select class="form-select" style="max-width: 100px;" name="sort_dir">
                                    <option value="asc">ASC</option>
                                    <option value="desc">DESC</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-alt-secondary" id="reset-filter">
                                <i class="fa fa-rotate-left me-1"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-bolt me-1"></i> Generate
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card d-none" id="table-block">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter w-100 mb-0" id="dynamic-table">
                        <thead>
                            <tr>
                                @foreach($report->columns as $col)
                                    <th class="{{ ($col->Format === 'currency' || $col->DataType === 'numeric') ? 'text-end' : '' }} {{ !$col->IsVisible ? 'd-none' : '' }}">
                                        {{ $col->Label }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot class="d-none" id="table-footer">
                            <tr>
                                @foreach($report->columns as $index => $col)
                                    <td class="{{ ($col->Format === 'currency' || $col->DataType === 'numeric') ? 'text-end' : '' }} {{ !$col->IsVisible ? 'd-none' : '' }}" id="total-{{ $col->FieldName }}">
                                        @if($index === 0) GRAND TOTAL @endif
                                    </td>
                                @endforeach
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="https://cdn.datatables.net/rowgroup/1.4.1/js/dataTables.rowGroup.min.js"></script>
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('js/plugins/flatpickr/flatpickr.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function () {
            let table = null;
            let lastResponse = null;
            let groupingOrder = [];

            $('.form-select[multiple]').select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true
            });
            $('input[type="date"]').flatpickr({ dateFormat: 'Y-m-d' });

            $('#report-grouping').on('select2:select', function (e) {
                groupingOrder.push(e.params.data.id);
            });

            $('#report-grouping').on('select2:unselect', function (e) {
                groupingOrder = groupingOrder.filter(id => id !== e.params.data.id);
            });

            $('#filter-form').on('submit', function (e) {
                e.preventDefault();
                $('#table-block').removeClass('d-none');
                if (table) {
                    table.destroy();
                }
                initTable();
            });

            $('#reset-filter').on('click', function () {
                $('#filter-form')[0].reset();
                $('.form-select[multiple]').val(null).trigger('change');
                groupingOrder = [];
                if (table) {
                    table.destroy();
                    $('#dynamic-table tbody').empty();
                    table = null;
                }
                $('#table-block').addClass('d-none');
            });

            $('#export-excel').on('click', function () {
                const url = "{{ route('report.dynamic.export', $report->Slug) }}?" + $('#filter-form').serialize();
                window.location.href = url;
            });

            $('#export-pdf').on('click', function () {
                const url = "{{ route('report.dynamic.export-pdf', $report->Slug) }}?" + $('#filter-form').serialize();
                window.location.href = url;
            });

            function initTable() {
                const selectedGrouping = ($('#report-grouping').val() || []).slice();
                const sortBy = $('#report-sort-by').val();
                const sortDir = $('select[name="sort_dir"]').val();
                const allFieldNames = [@foreach($report->columns as $col) '{{ $col->FieldName }}', @endforeach];
                const aggregateCols = [@foreach($report->columns->whereNotNull('AggregateFunction')->where('AggregateFunction', '!=', '') as $col) '{{ $col->FieldName }}', @endforeach];
                const dynamicOrder = [];

                selectedGrouping.forEach(function (field) {
                    const idx = allFieldNames.indexOf(field);
                    if (idx !== -1) {
                        dynamicOrder.push([idx, 'asc']);
                    }
                });

                if (sortBy) {
                    const idx = allFieldNames.indexOf(sortBy);
                    if (idx !== -1 && !selectedGrouping.includes(sortBy)) {
                        dynamicOrder.push([idx, sortDir]);
                    }
                }

                table = $('#dynamic-table').DataTable({
                    processing: true,
                    serverSide: true,
                    pageLength: 25,
                    order: dynamicOrder.length > 0 ? dynamicOrder : [[0, 'asc']],
                    orderFixed: dynamicOrder.length > 0 ? dynamicOrder : null,
                    ajax: {
                        url: '{{ route('report.dynamic.data', $report->Slug) }}',
                        type: 'POST',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        data: function (d) {
                            $('#filter-form').serializeArray().forEach(function (item) {
                                if (item.name === 'grouping[]') {
                                    return;
                                }
                                if (item.name.endsWith('[]')) {
                                    const key = item.name.replace('[]', '');
                                    if (!d[key]) {
                                        d[key] = [];
                                    }
                                    d[key].push(item.value);
                                } else {
                                    d[item.name] = item.value;
                                }
                            });
                            d.grouping = selectedGrouping.join(',');
                        },
                        dataSrc: function (json) {
                            lastResponse = json;
                            return json.data || [];
                        }
                    },
                    columns: [
                        @foreach($report->columns as $col)
                        {
                            data: '{{ $col->FieldName }}',
                            name: '{{ $col->FieldName }}',
                            visible: {{ $col->IsVisible ? 'true' : 'false' }},
                            className: '{{ ($col->Format === 'currency' || $col->DataType === 'numeric') ? 'text-end' : '' }}',
                            defaultContent: '-'
                        },
                        @endforeach
                    ],
                    rowGroup: selectedGrouping.length > 0 ? {
                        dataSrc: selectedGrouping
                    } : null,
                    drawCallback: function (settings) {
                        const json = settings.json;
                        lastResponse = json;
                        if (json && json.totals) {
                            $('#table-footer').removeClass('d-none');
                            $.each(json.totals, function (field, value) {
                                $('#total-' + field).text(value);
                            });
                        } else {
                            $('#table-footer').addClass('d-none');
                        }
                    }
                });
            }
        });
    </script>
@endsection
