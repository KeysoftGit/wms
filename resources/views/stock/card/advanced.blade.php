@extends('layouts.admin')

@section('titles')
    <title>Keyonline - Advanced Stock Monitoring</title>
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
                        <select name="part_id" id="part_id" class="form-select">
                            <option></option>
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <label class="form-label" for="unit_id">Unit</label>
                        <select name="unit_id" id="unit_id" class="form-select">
                            <option></option>
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <label class="form-label" for="batch_no">Batch No</label>
                        <select name="batch_no" id="batch_no" class="form-select stock-detail-select" data-target="batch_no">
                            <option></option>
                        </select>
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
                <div class="block-options text-muted fs-sm">Use details to inspect full information.</div>
            </div>
            <div class="block-content">
                <div class="table-responsive w-100">
                    <table class="table table-bordered table-striped table-vcenter w-100" id="datatable">
                        <thead>
                            <tr>
                                <th>Transaction</th>
                                <th class="text-center">Batch No</th>
                                <th class="text-center">Coil No</th>
                                <th class="text-end">Qty</th>
                                <th class="text-center">Unit</th>
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
                        <div class="stock-detail-hero">
                            <div>
                                <div class="stock-detail-kicker">Stock Movement Detail</div>
                                <h3 class="stock-detail-title" id="stockDetailModalLabel">-</h3>
                                <div class="stock-detail-subtitle" id="detail-transaction-line">-</div>
                            </div>
                            <div class="stock-detail-hero-actions">
                                <span class="movement-badge" id="detail-direction-badge">-</span>
                                <div class="block-options">
                                    <button type="button" class="btn btn-sm btn-alt-secondary" data-bs-dismiss="modal" aria-label="Close">
                                        <i class="fa fa-fw fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="block-content pb-4">
                            <div class="movement-overview mb-4">
                                <div class="movement-overview-main">
                                    <div class="overview-label">Movement Qty</div>
                                    <div class="overview-value" id="detail-qty">0</div>
                                    <div class="overview-unit" id="detail-unit">-</div>
                                </div>
                                <div class="movement-overview-grid">
                                    <div>
                                        <span>Date</span>
                                        <strong id="detail-date">-</strong>
                                    </div>
                                    <div>
                                        <span>Warehouse</span>
                                        <strong id="detail-warehouse">-</strong>
                                    </div>
                                    <div>
                                        <span>Batch No</span>
                                        <strong id="detail-batch-no">-</strong>
                                    </div>
                                    <div>
                                        <span>Coil No</span>
                                        <strong id="detail-coil-no">-</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-lg-7">
                                    <div class="detail-panel h-100">
                                        <div class="detail-panel-title">Part Information</div>
                                        <div class="part-title" id="detail-part">-</div>
                                        <div class="detail-metadata-grid mt-3">
                                            <div>
                                                <span>Part ID</span>
                                                <strong id="detail-part-id">-</strong>
                                            </div>
                                            <div>
                                                <span>Part Name</span>
                                                <strong id="detail-part-name">-</strong>
                                            </div>
                                            <div>
                                                <span>Category</span>
                                                <strong id="detail-part-category">-</strong>
                                            </div>
                                            <div>
                                                <span>Specification</span>
                                                <strong id="detail-part-specification">-</strong>
                                            </div>
                                            <div>
                                                <span>Variant</span>
                                                <strong id="detail-part-variant">-</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-5">
                                    <div class="detail-panel h-100">
                                        <div class="detail-panel-title">Current Transaction</div>
                                        <div class="detail-metadata-grid detail-metadata-grid-single mt-3">
                                            <div>
                                                <span>Transaction No</span>
                                                <strong id="detail-transaction-no">-</strong>
                                            </div>
                                            <div>
                                                <span>Transaction Type</span>
                                                <strong id="detail-transaction-type">-</strong>
                                            </div>
                                            <div>
                                                <span>Notes</span>
                                                <strong id="detail-notes">-</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-panel">
                                <div class="journey-header">
                                    <div>
                                        <div class="detail-panel-title p-0">Stock Journey</div>
                                        <div class="text-muted fs-sm">Semua pergerakan stock untuk part dan batch yang sama.</div>
                                    </div>
                                    <div class="journey-summary">
                                        <span>In <strong id="detail-stock-in">0</strong></span>
                                        <span>Out <strong id="detail-stock-out">0</strong></span>
                                        <span>Final <strong id="detail-final-stock">0</strong></span>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-vcenter journey-table mb-0">
                                        <thead>
                                            <tr>
                                                <th class="journey-date-col">Date</th>
                                                <th>Transaction</th>
                                                <th>Warehouse</th>
                                                <th class="journey-batch-col">Batch</th>
                                                <th class="journey-coil-col">Coil</th>
                                                <th class="text-end">Initial</th>
                                                <th class="text-end">Qty</th>
                                                <th class="text-end">Final</th>
                                                <th class="text-center">Unit</th>
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

        .stock-summary-in { border-left: 4px solid #198754; }
        .stock-summary-out { border-left: 4px solid #dc3545; }
        .stock-summary-final { border-left: 4px solid #0d6efd; }

        .select2-container { width: 100% !important; }

        #stockDetailModal .modal-content {
            max-height: calc(100vh - 2rem);
            overflow: hidden;
        }

        #stockDetailModal .block,
        #stockDetailModal .modal-content {
            display: flex;
            flex-direction: column;
        }

        #stockDetailModal .block {
            min-height: 0;
        }

        #stockDetailModal .block-content {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
        }

        #datatable th,
        #datatable td {
            vertical-align: middle;
        }

        #datatable td:not(.text-wrap) {
            white-space: nowrap;
        }

        .movement-transaction strong {
            display: block;
            color: #111827;
        }

        .movement-transaction span {
            display: block;
            color: #6c757d;
            font-size: .8125rem;
            margin-top: .125rem;
        }

        .stock-detail-hero {
            align-items: flex-start;
            background: linear-gradient(135deg, #172033 0%, #28435f 100%);
            color: #fff;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
        }

        .stock-detail-kicker {
            color: rgba(255, 255, 255, .72);
            font-size: .76rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .stock-detail-title {
            color: #fff;
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.25;
            margin: .25rem 0;
            overflow-wrap: anywhere;
        }

        .stock-detail-subtitle {
            color: rgba(255, 255, 255, .78);
            font-size: .875rem;
            overflow-wrap: anywhere;
        }

        .stock-detail-hero-actions {
            align-items: center;
            display: flex;
            gap: .75rem;
        }

        .movement-badge {
            border-radius: 999px;
            color: #fff;
            display: inline-flex;
            font-size: .8rem;
            font-weight: 700;
            line-height: 1;
            padding: .55rem .75rem;
            white-space: nowrap;
        }

        .movement-badge-in { background: #198754; }
        .movement-badge-out { background: #dc3545; }
        .movement-badge-neutral { background: #6c757d; }

        .movement-overview {
            border: 1px solid #dfe5ec;
            border-radius: 8px;
            display: grid;
            grid-template-columns: minmax(220px, .85fr) 2fr;
            overflow: hidden;
        }

        .movement-overview-main {
            background: #f6f8fb;
            border-right: 1px solid #dfe5ec;
            padding: 1.15rem;
        }

        .overview-label,
        .overview-unit {
            color: #6c757d;
            font-size: .8125rem;
            font-weight: 600;
        }

        .overview-value {
            color: #111827;
            font-size: 2rem;
            font-weight: 800;
            line-height: 1.1;
            margin: .2rem 0;
        }

        .movement-overview-grid,
        .detail-metadata-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .movement-overview-grid > div,
        .detail-metadata-grid > div {
            border-bottom: 1px solid #eef1f4;
            border-right: 1px solid #eef1f4;
            min-height: 72px;
            padding: .85rem 1rem;
        }

        .movement-overview-grid > div:nth-child(2n),
        .detail-metadata-grid > div:nth-child(2n) {
            border-right: 0;
        }

        .movement-overview-grid span,
        .detail-metadata-grid span {
            color: #6c757d;
            display: block;
            font-size: .75rem;
            font-weight: 700;
            margin-bottom: .25rem;
            text-transform: uppercase;
        }

        .movement-overview-grid strong,
        .detail-metadata-grid strong {
            color: #111827;
            display: block;
            overflow-wrap: anywhere;
        }

        .detail-panel {
            border: 1px solid #dfe5ec;
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }

        .detail-panel-title {
            color: #111827;
            font-size: .86rem;
            font-weight: 800;
            padding: 1rem 1rem 0;
            text-transform: uppercase;
        }

        .part-title {
            color: #1f2937;
            font-size: 1.05rem;
            font-weight: 700;
            padding: .5rem 1rem 0;
            overflow-wrap: anywhere;
        }

        .detail-metadata-grid-single {
            grid-template-columns: 1fr;
        }

        .detail-metadata-grid-single > div {
            border-right: 0;
        }

        .journey-summary {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .journey-header {
            align-items: flex-start;
            display: flex;
            flex-direction: column;
            gap: .9rem;
            justify-content: space-between;
            padding: 1rem 1rem 1.1rem;
        }

        @media (min-width: 1200px) {
            .journey-header {
                align-items: center;
                flex-direction: row;
            }
        }

        .journey-summary span {
            background: #f6f8fb;
            border: 1px solid #dfe5ec;
            border-radius: 6px;
            color: #6c757d;
            font-size: .8125rem;
            padding: .55rem .7rem;
        }

        .journey-summary strong {
            color: #111827;
            margin-left: .2rem;
        }

        .movement-dot {
            align-items: center;
            border: 1px solid currentColor;
            border-radius: 999px;
            display: inline-flex;
            flex: 0 0 28px;
            height: 28px;
            justify-content: center;
            width: 28px;
        }

        .journey-table th,
        .journey-table td {
            padding: .75rem .85rem;
            vertical-align: middle;
        }

        .journey-table td {
            line-height: 1.35;
        }

        .journey-table th {
            background: #f6f8fb;
            color: #4b5563;
            font-size: .76rem;
            font-weight: 800;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .journey-date-col {
            width: 96px;
        }

        .journey-batch-col,
        .journey-coil-col {
            min-width: 150px;
            width: 150px;
        }

        .journey-date {
            color: #111827;
            font-weight: 700;
            line-height: 1.2;
            white-space: nowrap;
        }

        .journey-time {
            color: #6b7280;
            display: block;
            font-size: .72rem;
            font-weight: 700;
            line-height: 1.2;
            margin-top: 2px;
            white-space: nowrap;
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

        @media (max-width: 991.98px) {
            .detail-summary { flex-direction: column; }

            .stock-detail-hero,
            .stock-detail-hero-actions {
                align-items: flex-start;
                flex-direction: column;
            }

            .movement-overview {
                grid-template-columns: 1fr;
            }

            .movement-overview-main {
                border-right: 0;
                border-bottom: 1px solid #dfe5ec;
            }

            .movement-overview-grid,
            .detail-metadata-grid {
                grid-template-columns: 1fr;
            }

            .movement-overview-grid > div,
            .detail-metadata-grid > div {
                border-right: 0;
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
        const advancedDatatableUrl = @json(route('monitor.datatable'));
        const stockDetailUrl = @json(route('monitor.detail'));
        const warehouseSelectUrl = @json(route('misc.warehouse2', ['stock_monitoring' => true]));
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

            return parsed.toLocaleString('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 6,
            });
        }

        function formatDateValue(value) {
            if (!value) {
                return '-';
            }

            const date = moment(value);
            return date.isValid() ? date.format('YYYY-MM-DD HH:mm:ss') : value;
        }

        function formatJourneyDateHtml(value) {
            if (!value || value === '-') {
                return '-';
            }

            const date = moment(value);
            if (!date.isValid()) {
                return escapeHtml(value);
            }

            return '<span class="journey-date">' + escapeHtml(date.format('YYYY/MM/DD')) + '</span>' +
                '<span class="journey-time">' + escapeHtml(date.format('HH:mm:ss')) + '</span>';
        }

        function displayValue(value) {
            return value === null || value === '' || value === undefined ? '-' : value;
        }

        function displayWarehouse(row) {
            return displayValue(row.WarehouseDisplay || row.WarehouseName || row.WarehouseID);
        }

        function escapeHtml(value) {
            return String(displayValue(value))
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function setDetailText(selector, value) {
            $(selector).text(displayValue(value));
        }

        function movementDirection(qty) {
            const value = parseFloat(qty);

            if (value > 0) {
                return { label: 'STOCK IN', className: 'movement-badge-in' };
            }

            if (value < 0) {
                return { label: 'STOCK OUT', className: 'movement-badge-out' };
            }

            return { label: 'NO CHANGE', className: 'movement-badge-neutral' };
        }

        function syncMovementBadge(qty) {
            const direction = movementDirection(qty);

            $('#detail-direction-badge')
                .removeClass('movement-badge-in movement-badge-out movement-badge-neutral')
                .addClass(direction.className)
                .text(direction.label);
        }

        function detailRequestData(row) {
            const filters = currentFilters();
            const batchNo = row.BatchNo === null || row.BatchNo === '' || row.BatchNo === undefined ? nullFilterValue : row.BatchNo;

            return {
                part_id: row.PartID,
                warehouse_id: filters.warehouse_id,
                unit_id: filters.unit_id,
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
                const icon = qty > 0 ? 'fa-arrow-down' : (qty < 0 ? 'fa-arrow-up' : 'fa-minus');

                return '<tr>' +
                    '<td class="journey-date-col">' + formatJourneyDateHtml(item.date) + '</td>' +
                    '<td><div class="d-flex align-items-start gap-2"><span class="movement-dot ' + colorClass + '"><i class="fa ' + icon + '"></i></span><div><strong>' + escapeHtml(item.transaction_no) + '</strong><span class="d-block text-muted fs-sm">' + escapeHtml(item.type) + '</span></div></div></td>' +
                    '<td>' + escapeHtml(item.warehouse) + '</td>' +
                    '<td class="journey-batch-col">' + escapeHtml(item.batch_no) + '</td>' +
                    '<td class="journey-coil-col">' + escapeHtml(item.coil_no) + '</td>' +
                    '<td class="text-end">' + escapeHtml(formatStockNumber(item.initial)) + '</td>' +
                    '<td class="text-end fw-semibold ' + colorClass + '">' + escapeHtml(formatStockNumber(item.qty)) + '</td>' +
                    '<td class="text-end">' + escapeHtml(formatStockNumber(item.final)) + '</td>' +
                    '<td class="text-center">' + escapeHtml(item.unit) + '</td>' +
                '</tr>';
            }).join('');

            $('#detail-journey-body').html(html);
        }

        function applyDetailResponse(response, fallbackRow) {
            const part = response.part || {};
            const movement = response.movement || fallbackRow || {};
            const summary = response.summary || {};
            const qty = movement.qty ?? fallbackRow.Qty;
            const unit = movement.unit || fallbackRow.DisplayUnitID;

            setDetailText('#stockDetailModalLabel', displayValue(part.PartID || fallbackRow.PartID) + ' - ' + displayValue(part.PartName || fallbackRow.PartName));
            setDetailText('#detail-part', displayValue(part.PartID || fallbackRow.PartID) + ' - ' + displayValue(part.PartName || fallbackRow.PartName));
            setDetailText('#detail-part-id', part.PartID || fallbackRow.PartID);
            setDetailText('#detail-part-name', part.PartName || fallbackRow.PartName);
            setDetailText('#detail-part-category', part.Category);
            setDetailText('#detail-part-specification', part.Specification);
            setDetailText('#detail-part-variant', part.Variant);
            $('#detail-qty')
                .text(formatStockNumber(qty))
                .toggleClass('text-danger', parseFloat(qty) < 0)
                .toggleClass('text-success', parseFloat(qty) >= 0);
            syncMovementBadge(qty);
            setDetailText('#detail-unit', unit);
            setDetailText('#detail-date', formatDateValue(movement.date));
            setDetailText('#detail-warehouse', movement.warehouse || fallbackRow.WarehouseDisplay || fallbackRow.WarehouseName || fallbackRow.WarehouseID);
            setDetailText('#detail-transaction-type', movement.type || fallbackRow.TransactionType);
            setDetailText('#detail-transaction-no', movement.transaction_no || fallbackRow.TransactionNo);
            setDetailText('#detail-transaction-line', displayValue(movement.transaction_no || fallbackRow.TransactionNo) + ' | ' + displayValue(movement.type || fallbackRow.TransactionType));
            setDetailText('#detail-batch-no', movement.batch_no || fallbackRow.BatchNo);
            setDetailText('#detail-coil-no', movement.coil_no || fallbackRow.CoilNo);
            setDetailText('#detail-notes', movement.notes || fallbackRow.Notes);
            $('#detail-stock-in').text(formatStockNumber(summary.stock_in));
            $('#detail-stock-out').text(formatStockNumber(summary.stock_out));
            $('#detail-final-stock').text(formatStockNumber(summary.final_stock));
            renderJourney(response.journey || []);
        }

        function fillDetailModal(row) {
            setDetailText('#stockDetailModalLabel', displayValue(row.PartID) + ' - ' + displayValue(row.PartName));
            setDetailText('#detail-part', displayValue(row.PartID) + ' - ' + displayValue(row.PartName));
            $('#detail-qty')
                .text(formatStockNumber(row.Qty))
                .toggleClass('text-danger', parseFloat(row.Qty) < 0)
                .toggleClass('text-success', parseFloat(row.Qty) >= 0);
            syncMovementBadge(row.Qty);
            $('#detail-unit').text(displayValue(row.DisplayUnitID));
            $('#detail-date').text(formatDateValue(row.TransactionDate));
            $('#detail-warehouse').text(displayWarehouse(row));
            $('#detail-transaction-type').text(displayValue(row.TransactionType));
            $('#detail-transaction-no').text(displayValue(row.TransactionNo));
            setDetailText('#detail-transaction-line', displayValue(row.TransactionNo) + ' | ' + displayValue(row.TransactionType));
            $('#detail-batch-no').text(displayValue(row.BatchNo));
            $('#detail-coil-no').text(displayValue(row.CoilNo));
            $('#detail-notes').text(displayValue(row.Notes));
            setDetailText('#detail-part-id', row.PartID);
            setDetailText('#detail-part-name', row.PartName);
            setDetailText('#detail-part-category', null);
            setDetailText('#detail-part-specification', null);
            setDetailText('#detail-part-variant', null);
            $('#detail-stock-in, #detail-stock-out, #detail-final-stock').text('0');
            resetJourneyTable('Loading stock journey...');

            $.get(stockDetailUrl, detailRequestData(row))
                .done(function(response) {
                    applyDetailResponse(response, row);
                })
                .fail(function() {
                    resetJourneyTable('Failed to load stock journey.');
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

        function updateTableSummary(summary) {
            summary = summary || {};

            $('#stock_in').text(formatStockNumber(summary.stock_in));
            $('#stock_out').text(formatStockNumber(summary.stock_out));
            $('#final_stock').text(formatStockNumber(summary.final_stock));
        }

        function hasRequiredFilters() {
            const filters = currentFilters();

            return Boolean(filters.warehouse_id || filters.part_id);
        }

        function hasPartFilter() {
            const filters = currentFilters();

            return Boolean(filters.part_id);
        }

        function syncDetailFilterState() {
            $('.stock-detail-select')
                .prop('disabled', !hasPartFilter())
                .trigger('change.select2');

            $('#unit_id')
                .prop('disabled', !hasPartFilter())
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
                        if (!hasPartFilter()) {
                            success({ data: { results: [] } });
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
                            results: rows.map(function(row) {
                                const value = row[column];
                                const isEmptyValue = value === null || value === '';
                                const id = isEmptyValue ? nullFilterValue : value;
                                const label = isEmptyValue ? '(Empty)' : value;

                                return { id: id, text: label };
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
                        return { search: params.term || '' };
                    },
                    processResults: function(data) {
                        return { results: data };
                    },
                    cache: true,
                },
            });
        }

        function initUnitSelect() {
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
                        if (!hasPartFilter()) {
                            success([]);
                            return null;
                        }

                        const request = $.ajax(params);
                        request.then(success);
                        request.fail(failure);

                        return request;
                    },
                    processResults: function(data) {
                        return { results: data };
                    },
                    cache: true,
                },
            });
        }

        function resetUnitSelection() {
            $('#unit_id').val(null).trigger('change.select2');
        }

        initRemoteSelect('#warehouse', warehouseSelectUrl, 'Select warehouse');
        initRemoteSelect('#part_id', partSelectUrl, 'Select part');
        initUnitSelect();

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
                url: advancedDatatableUrl,
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
                        return '<strong>' + escapeHtml(data) + '</strong><span>' + escapeHtml(displayWarehouse(row)) + '</span><span>' + escapeHtml(row.TransactionType) + '</span><span>' + escapeHtml(formatDateValue(row.TransactionDate)) + '</span>';
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
                    data: 'DisplayUnitID',
                    name: 'DisplayUnitID',
                    class: 'text-center',
                    orderable: false,
                    searchable: false,
                    render: function(data) {
                        return escapeHtml(data);
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
            syncDetailFilterState();
        });

        $('#unit_id').on('change', function() {
            reloadTableIfReady();
        });

        $('#warehouse, #part_id').on('change', function() {
            if ($(this).is('#part_id')) {
                resetUnitSelection();
            }

            resetDetailFilters();
            syncDetailFilterState();
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

        $('#clear-filter').on('click', function() {
            $('#warehouse, #part_id, #unit_id').val(null).trigger('change');
            resetDetailFilters();
            syncDetailFilterState();
            resetTableSummary();
        });

        table.on('xhr.dt', function(event, settings, json) {
            const summary = json && json.summary ? json.summary : {};

            updateTableSummary(summary);
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
