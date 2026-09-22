@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Advanced Stock Monitoring</title>
@endsection

@section('content')
    <div class="px-lg-5 py-lg-3 p-3" id="advanced-stock-monitoring">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 py-2 mb-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Advanced Stock Monitoring</h1>
                <div class="text-muted fs-sm">Track stock movement by warehouse, part, and batch.</div>
            </div>
            <button type="button" class="btn btn-alt-secondary" id="clear-filter">
                <i class="fa fa-fw fa-rotate-left"></i> Reset
            </button>
        </div>

        <form autocomplete="off" id="filter-form" class="block block-rounded shadow-sm mb-3">
            <div class="block-header block-header-default">
                <h3 class="block-title">Filters</h3>
                <div class="block-options">
                    <button type="button" class="btn btn-sm btn-alt-secondary me-1" id="clear-detail-filters">
                        <i class="fa fa-fw fa-eraser"></i> Clear Details
                    </button>
                    <button type="submit" class="btn btn-sm btn-primary" id="filter">
                        <i class="fa fa-fw fa-filter"></i> Apply
                    </button>
                </div>
            </div>
            <div class="block-content pb-3">
                <div class="row g-3">
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <label class="form-label" for="warehouse">Warehouse</label>
                        <select name="warehouse_id" id="warehouse" class="form-select">
                            <option></option>
                        </select>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <label class="form-label" for="part_id">Part</label>
                        <select name="part_id" id="part_id" class="form-select" required>
                            <option></option>
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <label class="form-label" for="unit_id">Unit</label>
                        <select name="unit_id" id="unit_id" class="form-select" disabled>
                            <option></option>
                        </select>
                    </div>

                    <div class="col-xl-4 col-lg-4 col-md-6">
                        <label class="form-label" for="batch_no">Batch No</label>
                        <div class="input-group">
                            <select name="batch_no" id="batch_no" class="form-select stock-detail-select" data-target="batch_no">
                                <option></option>
                            </select>
                            <button type="button" class="btn btn-alt-secondary clear-detail-column" data-target="#batch_no" title="Clear Batch No"><i class="fa fa-times"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="stock-summary stock-summary-in">
                    <span class="stock-summary-label">Stock In</span>
                    <strong id="stock_in">0</strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stock-summary stock-summary-out">
                    <span class="stock-summary-label">Stock Out</span>
                    <strong id="stock_out">0</strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stock-summary stock-summary-final">
                    <span class="stock-summary-label">Final Stock</span>
                    <strong id="final_stock">0</strong>
                </div>
            </div>
        </div>

        <div class="block block-rounded shadow-sm">
            <div class="block-header block-header-default">
                <h3 class="block-title">Stock Movements</h3>
                <div class="block-options text-muted fs-sm">
                    Use details to inspect full information.
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive w-100">
                    <table class="table table-bordered table-striped table-vcenter w-100" id="datatable">
                        <thead>
                            <tr>
                                <th>Transaction</th>
                                <th class="text-center">Batch No</th>
                                <th class="text-center">Coil No</th>
                                <th class="text-center">Notes</th>
                                <th class="text-end">Qty</th>
                                <th class="text-center">Details</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="stockDetailModal" tabindex="-1" aria-labelledby="stockDetailModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="block block-rounded block-transparent mb-0">
                        <div class="block-header block-header-default">
                            <h3 class="block-title" id="stockDetailModalLabel">Stock Movement Details</h3>
                            <div class="block-options">
                                <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                                    <i class="fa fa-fw fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="block-content pb-4">
                            <div class="detail-summary mb-3">
                                <div>
                                    <div class="text-muted fs-sm">Part</div>
                                    <div class="fw-semibold" id="detail-part">-</div>
                                </div>
                                <div class="text-lg-end">
                                    <div class="text-muted fs-sm">Qty</div>
                                    <div class="fw-bold fs-4" id="detail-qty">0</div>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="detail-field">
                                        <span>Date</span>
                                        <strong id="detail-date">-</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-field">
                                        <span>Warehouse</span>
                                        <strong id="detail-warehouse">-</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-field">
                                        <span>Transaction Type</span>
                                        <strong id="detail-transaction-type">-</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-field">
                                        <span>Transaction No</span>
                                        <strong id="detail-transaction-no">-</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-field">
                                        <span>Batch No</span>
                                        <strong id="detail-batch-no">-</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-field">
                                        <span>Coil No</span>
                                        <strong id="detail-coil-no">-</strong>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="detail-field">
                                        <span>Notes</span>
                                        <strong id="detail-notes">-</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-section mb-4">
                                <div class="detail-section-title">Part Information</div>
                                <div class="row g-3">
                                    <div class="col-md-6 col-xl-3">
                                        <div class="detail-field">
                                            <span>Part ID</span>
                                            <strong id="detail-part-id">-</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-3">
                                        <div class="detail-field">
                                            <span>Part Name</span>
                                            <strong id="detail-part-name">-</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-3">
                                        <div class="detail-field">
                                            <span>Category</span>
                                            <strong id="detail-part-category">-</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-3">
                                        <div class="detail-field">
                                            <span>Specification</span>
                                            <strong id="detail-part-specification">-</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-3">
                                        <div class="detail-field">
                                            <span>Variant</span>
                                            <strong id="detail-part-variant">-</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-3">
                                        <div class="detail-field">
                                            <span>Serial No</span>
                                            <strong id="detail-serial-no">-</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-3">
                                        <div class="detail-field">
                                            <span>BIN</span>
                                            <strong id="detail-bin">-</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-3">
                                        <div class="detail-field">
                                            <span>LOC</span>
                                            <strong id="detail-loc">-</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-section">
                                <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-2">
                                    <div>
                                        <div class="detail-section-title mb-1">Stock Journey</div>
                                        <div class="text-muted fs-sm">Every accessible movement for the selected part and batch.</div>
                                    </div>
                                    <div class="detail-journey-summary">
                                        <span>In <strong id="detail-stock-in">0</strong></span>
                                        <span>Out <strong id="detail-stock-out">0</strong></span>
                                        <span>Final <strong id="detail-final-stock">0</strong></span>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered table-striped table-vcenter mb-0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Transaction</th>
                                                <th>Warehouse</th>
                                                <th>Batch No</th>
                                                <th>Coil No</th>
                                                <th class="text-end">Initial</th>
                                                <th class="text-end">Qty</th>
                                                <th class="text-end">Final</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody id="detail-journey-body">
                                            <tr>
                                                <td colspan="9" class="text-center text-muted">No movement.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-buttons-bs5/buttons.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/sweetalert2/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">

    <style>
        .stock-summary {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            padding: 1rem 1.25rem;
            min-height: 86px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: .25rem;
        }

        .stock-summary-label {
            color: #6c757d;
            font-size: .8125rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .stock-summary strong {
            color: #111827;
            font-size: 1.45rem;
            line-height: 1.2;
        }

        .stock-summary-in {
            border-left: 4px solid #198754;
        }

        .stock-summary-out {
            border-left: 4px solid #dc3545;
        }

        .stock-summary-final {
            border-left: 4px solid #0d6efd;
        }

        .select2-container {
            width: 100% !important;
        }

        #filter-form .input-group .select2-container {
            flex: 1 1 auto;
            min-width: 0;
            width: 1% !important;
        }

        #filter-form .input-group .clear-detail-column {
            width: 44px;
            flex: 0 0 44px;
        }

        #datatable th,
        #datatable td {
            vertical-align: middle;
        }

        #datatable td:not(.text-wrap) {
            white-space: nowrap;
        }

        .movement-part {
            min-width: 220px;
        }

        .movement-part strong,
        .movement-transaction strong {
            display: block;
            color: #111827;
        }

        .movement-part span,
        .movement-transaction span {
            display: block;
            color: #6c757d;
            font-size: .8125rem;
            margin-top: .125rem;
        }

        .detail-summary {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f8f9fa;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            gap: 1rem;
        }

        .detail-field {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            padding: .85rem 1rem;
            min-height: 72px;
        }

        .detail-field span {
            color: #6c757d;
            display: block;
            font-size: .78rem;
            font-weight: 600;
            margin-bottom: .25rem;
            text-transform: uppercase;
        }

        .detail-field strong {
            color: #111827;
            display: block;
            overflow-wrap: anywhere;
        }

        .detail-section-title {
            color: #111827;
            font-size: .9rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .detail-journey-summary {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .detail-journey-summary span {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: #fff;
            color: #6c757d;
            font-size: .8125rem;
            padding: .35rem .55rem;
        }

        .detail-journey-summary strong {
            color: #111827;
            margin-left: .2rem;
        }

        @media (max-width: 991.98px) {
            .detail-summary {
                flex-direction: column;
            }
        }
    </style>
@endsection

@section('scripts')
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('js/moment.min.js') }}"></script>
    <script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.js') }}"></script>
    <script src="{{ asset('js/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('js/plugins/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>

    <script>
        const availableStockDetailsUrl = @json(route('helper.available_stock_details'));
        const datatableUrl = @json(route('monitor.datatable'));
        const detailUrl = @json(route('monitor.detail'));
        const warehouseSelectUrl = @json(route('misc.warehouse2', ['stock_monitor' => true]));
        const partSelectUrl = @json(route('misc.part'));
        const partUnitSelectUrl = @json(route('misc.partunit2'));
        const nullFilterValue = '__NULL__';
        let isResettingDetailFilters = false;

        const detailColumnMap = {
            batch_no: 'BatchNo',
        };

        const detailLabelMap = {
            batch_no: 'Batch No',
        };

        function currentFilters() {
            return {
                warehouse_id: $('#warehouse').val(),
                part_id: $('#part_id').val(),
                unit_id: $('#unit_id').val(),
                batch_no: $('#batch_no').val(),
            };
        }

        function selectedDetailFilters() {
            const filters = currentFilters();
            const data = {};

            Object.keys(detailColumnMap).forEach(function(key) {

                if (filters[key]) {
                    data[key] = filters[key];
                }
            });

            return data;
        }

        function formatStockNumber(value) {
            if (value == null || value === '') {
                return '0';
            }

            const parsed = parseFloat(value);
            if (Number.isNaN(parsed)) {
                return value;
            }

            return parsed.toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 6,
            });
        }

        function formatDateValue(value) {
            if (!value) {
                return '-';
            }

            const date = moment(value);
            return date.isValid() ? date.format('YYYY-MM-DD') : value;
        }

        function displayValue(value) {
            return value === null || value === '' || value === undefined ? '-' : value;
        }

        function escapeHtml(value) {
            return String(displayValue(value))
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function renderNotes(data, row) {
            if (row.TransactionType === 'ITEMTRANSFER_RECEIVE_ANOMALI') {
                if (data === 'Surplus') {
                    return '<span class="badge bg-success">Surplus</span>';
                }

                if (data === 'Shortage') {
                    return '<span class="badge bg-danger">Shortage</span>';
                }

                return '<span class="badge bg-warning text-dark">' + escapeHtml(data) + '</span>';
            }

            return escapeHtml(data);
        }

        function setDetailText(selector, value) {
            $(selector).text(displayValue(value));
        }

        function detailRequestData(row) {
            const filters = currentFilters();
            const batchNo = row.BatchNo === null || row.BatchNo === '' || row.BatchNo === undefined ? nullFilterValue : row.BatchNo;

            return {
                part_id: row.PartID,
                unit_id: filters.unit_id,
                warehouse_id: filters.warehouse_id,
                batch_no: batchNo,
                transaction_no: row.TransactionNo || '',
                sequence: row.Sequence || '',
            };
        }

        function resetJourneyTable(message) {
            $('#detail-journey-body').html(
                '<tr><td colspan="9" class="text-center text-muted">' + escapeHtml(message || 'No movement.') + '</td></tr>'
            );
        }

        function renderJourney(rows) {
            if (!rows || !rows.length) {
                resetJourneyTable('No movement.');
                return;
            }

            const html = rows.map(function(item) {
                const qty = parseFloat(item.qty);
                const colorClass = qty > 0 ? 'text-success' : (qty < 0 ? 'text-danger' : 'text-muted');

                return '<tr>' +
                    '<td>' + escapeHtml(formatDateValue(item.date)) + '</td>' +
                    '<td><strong>' + escapeHtml(item.transaction_no) + '</strong><span class="d-block text-muted fs-sm">' + escapeHtml(item.type) + '</span></td>' +
                    '<td>' + escapeHtml(item.warehouse) + '</td>' +
                    '<td>' + escapeHtml(item.batch_no) + '</td>' +
                    '<td>' + escapeHtml(item.coil_no) + '</td>' +
                    '<td class="text-end">' + escapeHtml(formatStockNumber(item.initial)) + '</td>' +
                    '<td class="text-end fw-semibold ' + colorClass + '">' + escapeHtml(formatStockNumber(item.qty)) + '</td>' +
                    '<td class="text-end">' + escapeHtml(formatStockNumber(item.final)) + '</td>' +
                    '<td>' + renderNotes(item.notes, { TransactionType: item.type }) + '</td>' +
                '</tr>';
            }).join('');

            $('#detail-journey-body').html(html);
        }

        function applyDetailResponse(response, fallbackRow) {
            const part = response.part || {};
            const movement = response.movement || fallbackRow || {};
            const summary = response.summary || {};

            setDetailText('#detail-part', displayValue(part.PartID || fallbackRow.PartID) + ' - ' + displayValue(part.PartName || fallbackRow.PartName));
            setDetailText('#detail-part-id', part.PartID || fallbackRow.PartID);
            setDetailText('#detail-part-name', part.PartName || fallbackRow.PartName);
            setDetailText('#detail-part-category', part.Category);
            setDetailText('#detail-part-specification', part.Specification);
            setDetailText('#detail-part-variant', part.Variant);

            $('#detail-qty')
                .text(formatStockNumber(movement.qty ?? fallbackRow.Qty))
                .toggleClass('text-danger', parseFloat(movement.qty ?? fallbackRow.Qty) < 0)
                .toggleClass('text-success', parseFloat(movement.qty ?? fallbackRow.Qty) >= 0);
            setDetailText('#detail-date', formatDateValue(movement.date || fallbackRow.TransactionDate));
            setDetailText('#detail-warehouse', movement.warehouse || fallbackRow.WarehouseID);
            setDetailText('#detail-transaction-type', movement.type || fallbackRow.TransactionType);
            setDetailText('#detail-transaction-no', movement.transaction_no || fallbackRow.TransactionNo);
            setDetailText('#detail-batch-no', movement.batch_no || fallbackRow.BatchNo);
            setDetailText('#detail-coil-no', movement.coil_no || fallbackRow.CoilNo);
            setDetailText('#detail-serial-no', movement.serial_no || fallbackRow.SerialNo);
            setDetailText('#detail-bin', movement.bin || fallbackRow.BIN);
            setDetailText('#detail-loc', movement.loc || fallbackRow.LOC);
            $('#detail-notes').html(renderNotes(movement.notes ?? fallbackRow.Notes, {
                TransactionType: movement.type || fallbackRow.TransactionType,
            }));

            $('#detail-stock-in').text(formatStockNumber(summary.stock_in));
            $('#detail-stock-out').text(formatStockNumber(summary.stock_out));
            $('#detail-final-stock').text(formatStockNumber(summary.final_stock));
            renderJourney(response.journey || []);
        }

        function fillDetailModal(row) {
            $('#detail-part').text(displayValue(row.PartID) + ' - ' + displayValue(row.PartName));
            $('#detail-qty')
                .text(formatStockNumber(row.Qty))
                .toggleClass('text-danger', parseFloat(row.Qty) < 0)
                .toggleClass('text-success', parseFloat(row.Qty) >= 0);
            $('#detail-date').text(formatDateValue(row.TransactionDate));
            $('#detail-warehouse').text(displayValue(row.WarehouseID));
            $('#detail-transaction-type').text(displayValue(row.TransactionType));
            $('#detail-transaction-no').text(displayValue(row.TransactionNo));
            $('#detail-batch-no').text(displayValue(row.BatchNo));
            $('#detail-coil-no').text(displayValue(row.CoilNo));
            $('#detail-notes').html(renderNotes(row.Notes, row));
            setDetailText('#detail-part-id', row.PartID);
            setDetailText('#detail-part-name', row.PartName);
            setDetailText('#detail-part-category', null);
            setDetailText('#detail-part-specification', null);
            setDetailText('#detail-part-variant', null);
            setDetailText('#detail-serial-no', row.SerialNo);
            setDetailText('#detail-bin', row.BIN);
            setDetailText('#detail-loc', row.LOC);
            $('#detail-stock-in, #detail-stock-out, #detail-final-stock').text('0');
            resetJourneyTable('Loading movement journey...');

            $.get(detailUrl, detailRequestData(row))
                .done(function(response) {
                    applyDetailResponse(response, row);
                })
                .fail(function() {
                    resetJourneyTable('Failed to load movement journey.');
                });
        }

        function resetDetailFilters() {
            isResettingDetailFilters = true;
            $('.stock-detail-select').val(null).trigger('change');
            isResettingDetailFilters = false;
        }

        function resetTableSummary() {
            $('#stock_in').text('0');
            $('#stock_out').text('0');
            $('#final_stock').text('0');
        }

        function renderSummary(summary) {
            summary = summary || {};

            $('#stock_in').text(formatStockNumber(summary.stock_in));
            $('#stock_out').text(formatStockNumber(summary.stock_out));
            $('#final_stock').text(formatStockNumber(summary.final_stock));
        }

        function resetUnitFilter() {
            $('#unit_id')
                .val(null)
                .prop('disabled', true)
                .trigger('change.select2');
        }

        function hasRequiredFilters() {
            const filters = currentFilters();

            return Boolean(filters.part_id);
        }

        function syncDetailFilterState() {
            $('.stock-detail-select')
                .prop('disabled', !hasRequiredFilters())
                .trigger('change.select2');
        }

        function reloadTableIfReady() {
            if (!hasRequiredFilters()) {
                resetTableSummary();
                return;
            }

            table.ajax.reload();
        }

        function initDetailSelect(element) {
            const $element = $(element);
            const target = $element.data('target');

            $element.select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: 'All ' + detailLabelMap[target],
                ajax: {
                    url: availableStockDetailsUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        const filters = currentFilters();

                        const data = {
                            part_id: filters.part_id,
                            warehouse_id: filters.warehouse_id,
                            target: target,
                            term: params.term || '',
                            filter_qty: 0,
                        };

                        Object.entries(selectedDetailFilters()).forEach(function(entry) {
                            const key = entry[0];
                            const value = entry[1];

                            if (key !== target) {
                                data[key] = value;
                            }
                        });

                        return data;
                    },
                    transport: function(params, success, failure) {
                        if (!hasRequiredFilters()) {
                            success({
                                data: {
                                    results: [],
                                },
                            });
                            return null;
                        }

                        const request = $.ajax(params);
                        request.then(success);
                        request.fail(failure);

                        return request;
                    },
                    processResults: function(response) {
                        const column = detailColumnMap[target];
                        const rows = response.data && response.data.results ? response.data.results : [];

                        return {
                            results: rows
                                .map(function(row) {
                                    const value = row[column];
                                    const isEmptyValue = value === null || value === '';
                                    const id = isEmptyValue ? nullFilterValue : value;

                                    const label = isEmptyValue ? '(Empty)' : value;

                                    return {
                                        id: id,
                                        text: label,
                                    };
                                })
                        };
                    },
                    cache: true,
                },
            });
        }

        function initRemoteSelect(selector, url, placeholder) {
            $(selector).select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                placeholder: placeholder,
                ajax: {
                    url: url,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term || '',
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data,
                        };
                    },
                    cache: true,
                },
            });
        }

        initRemoteSelect('#warehouse', warehouseSelectUrl, 'Select warehouse');
        initRemoteSelect('#part_id', partSelectUrl, 'Select part');
        $('#unit_id').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true,
            placeholder: 'Select unit',
            ajax: {
                url: partUnitSelectUrl,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        id: $('#part_id').val(),
                        search: params.term || '',
                    };
                },
                transport: function(params, success, failure) {
                    if (!$('#part_id').val()) {
                        success([]);
                        return null;
                    }

                    const request = $.ajax(params);
                    request.then(success);
                    request.fail(failure);

                    return request;
                },
                processResults: function(data) {
                    return {
                        results: data,
                    };
                },
                cache: true,
            },
        });

        $('.stock-detail-select').each(function() {
            initDetailSelect(this);
        });
        syncDetailFilterState();

        const table = $('#datatable').DataTable({
            processing: true,
            serverSide: true,
            deferLoading: 0,
            pageLength: 10,
            scrollX: true,
            ajax: {
                url: datatableUrl,
                data: function(data) {
                    const filters = currentFilters();

                    data.warehouse_id = filters.warehouse_id;
                    data.part_id = filters.part_id;
                    data.unit_id = filters.unit_id;
                    Object.assign(data, selectedDetailFilters());
                },
                dataSrc: function(json) {
                    return json.data || [];
                },
                error: function() {
                    resetTableSummary();
                },
            },
            language: {
                lengthMenu: '_MENU_',
                search: '_INPUT_',
                searchPlaceholder: 'Search..',
                info: 'Page <strong>_PAGE_</strong> of <strong>_PAGES_</strong>',
                paginate: {
                    first: '<i class="fa fa-angle-double-left"></i>',
                    previous: '<i class="fa fa-angle-left"></i>',
                    next: '<i class="fa fa-angle-right"></i>',
                    last: '<i class="fa fa-angle-double-right"></i>',
                },
            },
            order: [],
            columns: [
                {
                    data: 'TransactionNo',
                    name: 'TransactionNo',
                    class: 'movement-transaction text-wrap',
                    render: function(data, type, row) {
                        return '<strong>' + escapeHtml(data) + '</strong><span>' + escapeHtml(row.TransactionType) + '</span><span>' + escapeHtml(formatDateValue(row.TransactionDate)) + '</span>';
                    },
                },
                {
                    data: 'BatchNo',
                    name: 'BatchNo',
                    class: 'text-center',
                    render: function(data) {
                        return escapeHtml(data);
                    },
                },
                {
                    data: 'CoilNo',
                    name: 'CoilNo',
                    class: 'text-center',
                    render: function(data) {
                        return escapeHtml(data);
                    },
                },
                {
                    data: 'Notes',
                    name: 'Notes',
                    class: 'text-center',
                    render: function(data, type, row) {
                        return renderNotes(data, row);
                    },
                },
                {
                    data: 'Qty',
                    name: 'Qty',
                    class: 'text-end',
                    searchable: false,
                    render: function(data) {
                        const value = parseFloat(data);
                        const colorClass = value > 0 ? 'text-success' : (value < 0 ? 'text-danger' : 'text-muted');

                        return '<span class="fw-semibold ' + colorClass + '">' + escapeHtml(formatStockNumber(data)) + '</span>';
                    },
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    class: 'text-center',
                    render: function() {
                        return '<button type="button" class="btn btn-sm btn-alt-secondary stock-detail-btn" data-bs-toggle="tooltip" title="View details"><i class="fa fa-fw fa-info-circle"></i></button>';
                    },
                },
            ],
        });

        $('#part_id').on('select2:select', function() {
            syncDetailFilterState();
        });

        $('#part_id').on('select2:clear', function() {
            resetUnitFilter();
            syncDetailFilterState();
        });

        $('#warehouse, #part_id').on('change', function() {
            if ($(this).is('#part_id')) {
                resetUnitFilter();
                $('#unit_id').prop('disabled', !$('#part_id').val()).trigger('change.select2');
            }

            resetDetailFilters();
            syncDetailFilterState();
            reloadTableIfReady();
        });

        $('#unit_id').on('change', function() {
            reloadTableIfReady();
        });

        $('.stock-detail-select').on('change', function() {
            if (isResettingDetailFilters) {
                return;
            }

            reloadTableIfReady();
        });

        $('#filter-form').on('submit', function(event) {
            event.preventDefault();

            if (!hasRequiredFilters()) {
                resetTableSummary();
                return;
            }

            table.ajax.reload();
        });

        $('#clear-detail-filters').on('click', function() {
            resetDetailFilters();
            syncDetailFilterState();
            reloadTableIfReady();
        });

        $('.clear-detail-column').on('click', function() {
            const target = $(this).data('target');
            $(target).val(null).trigger('change');
        });

        $('#clear-filter').on('click', function() {
            $('#warehouse, #part_id').val(null).trigger('change');
            resetUnitFilter();
            resetDetailFilters();
            syncDetailFilterState();
            resetTableSummary();
        });

        table.on('xhr.dt', function(event, settings, json) {
            const summary = json && json.summary ? json.summary : {};

            renderSummary(summary);
        });

        $('#datatable').on('click', '.stock-detail-btn', function() {
            const row = table.row($(this).closest('tr')).data();

            if (!row) {
                return;
            }

            fillDetailModal(row);
            $('#stockDetailModal').modal('show');
        });
    </script>
@endsection
