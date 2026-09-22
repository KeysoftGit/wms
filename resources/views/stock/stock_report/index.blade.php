@extends('layouts.admin')

@section('titles')
    <title>Keyonline - Stock Report</title>
@endsection

@section('content')
    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">
                <h1 class="h3 fw-bold mb-5">
                    Stock Report
                </h1>

                <div class="row justify-content-between align-items-end mb-3">
                    <div class="col-md-8">
                        <form id="filter-form" class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label">Date Range</label>
                                <input type="text" id="date" name="date" class="form-control"
                                    placeholder="Select date range" value="{{ request()->get('date') ?? '' }}">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Warehouse</label>
                                <select2 url="{{ route('misc.warehouse2', ['select2' => true]) }}" v-model="warehouse"
                                    :prevalue="warehouse" class="form-select" name="WarehouseID" id="WarehouseID">
                                    <option value="">- Select Warehouse -</option>
                                </select2>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label d-none d-md-block">&nbsp;</label>
                                <button type="button" id="filter-btn" class="btn btn-primary w-100">
                                    <i class="fa fa-filter me-1"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="col-auto">
                        @if (auth()->user()->hasAnyPermission(['admin', 'stock_report.view']))
                            <button type="button" id="summary-btn" class="btn btn-info">
                                <i class="fa fa-fw fa-layer-group"></i> Summary
                            </button>
                        @endif
                        <button type="button" id="export-btn" class="btn btn-success">
                            <i class="fa fa-fw fa-file-excel"></i> Export
                        </button>
                        @if (auth()->user()->hasAnyPermission(['admin', 'stock_report.add']))
                            <a class="btn btn-primary" href="{{ route('stock_report.add') }}">
                                <i class="fa fa-fw fa-plus"></i> Add
                            </a>
                        @endif
                    </div>
                </div>

                <div class="block block-rounded">
                    <div class="block-content">
                        <div class="table-responsive w-100">
                            <table class="table table-bordered table-striped table-vcenter" id="datatable">
                                <thead>
                                    <tr>
                                        <th class="text-center">NO</th>
                                        <th class="text-center">ID</th>
                                        <th class="text-center">DATE</th>
                                        <th class="text-center">PART ID</th>
                                        <th class="text-center">PART NAME</th>
                                        <th class="text-center">UNIT</th>
                                        <th class="text-center">CONVERSION</th>
                                        <th class="text-center">WAREHOUSE</th>
                                        <th class="text-center">REPORT QTY</th>
                                        <th class="text-center">NOTES</th>
                                        <th class="text-center">PIC</th>
                                        <th class="text-center">ENTRY TIME</th>
                                        <th class="text-center" width="10%">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- END Hero -->
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-buttons-bs5/buttons.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/sweetalert2/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('scripts')
    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>

    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('js/moment.min.js') }}"></script>
    <script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.js') }}"></script>
    <script src="{{ asset('js/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('js/plugins/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.13/dist/vue.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.0/axios.min.js"></script>

    <script type="text/x-template" id="select2-template">
        <select>
            <slot></slot>
        </select>
    </script>
    <script src="{{ asset('js/vueComponent-select2.js') }}"></script>

    <!-- Page JS Code -->
    <script>
        let app = new Vue({
            el: '#vue-container',
            data: {
                warehouse: '{{ request()->get('WarehouseID') ?? '' }}',
            },
            mounted() {
                this.initDataTable();

                // Flatpickr date range
                $("#date").flatpickr({
                    mode: "range",
                    dateFormat: "d/m/Y",
                    onClose: (selectedDates, dateStr, instance) => {
                        if (dateStr) {
                            this.table.ajax.reload();
                        }
                    }
                });
            },
            methods: {
                initDataTable() {
                    this.table = $('#datatable').DataTable({
                        processing: true,
                        serverSide: true,
                        pageLength: 10,
                        responsive: false,
                        ajax: {
                            url: '{!! route('stock_report.datatable') !!}',
                            data: (d) => {
                                d.date = $('#date').val();
                                d.WarehouseID = this.warehouse;
                            }
                        },
                        sWrapper: "dataTables_wrapper dt-bootstrap5",
                        sFilterInput: "form-control form-control-sm",
                        sLengthSelect: "form-select form-select-sm",
                        language: {
                            lengthMenu: "_MENU_",
                            search: "_INPUT_",
                            searchPlaceholder: "Search..",
                            info: "Page <strong>_PAGE_</strong> of <strong>_PAGES_</strong>",
                            paginate: {
                                first: '<i class="fa fa-angle-double-left"></i>',
                                previous: '<i class="fa fa-angle-left"></i>',
                                next: '<i class="fa fa-angle-right"></i>',
                                last: '<i class="fa fa-angle-double-right"></i>'
                            },
                        },
                        order: [
                            [1, 'desc']
                        ],
                        columns: [{
                                data: 'DT_RowIndex',
                                name: 'DT_RowIndex',
                                class: 'text-center',
                                orderable: false,
                                searchable: false
                            },
                            {
                                data: 'id',
                                name: 'id',
                                class: 'text-center',
                                searchable: false
                            },
                            {
                                data: 'Date',
                                name: 'Date',
                                class: 'text-center',
                                searchable: false,
                                render: function(data, type, row) {
                                    if (data && (type === 'display' || type === 'filter')) {
                                        return moment(data).format('DD MMM YYYY');
                                    }
                                    return data;
                                }
                            },
                            {
                                data: 'part_id',
                                name: 'PartID',
                                class: 'text-center',
                                searchable: true
                            },
                            {
                                data: 'part_name',
                                name: 'part.PartName',
                                class: 'text-center',
                                searchable: true
                            },
                            {
                                data: 'unit',
                                name: 'unit.UnitName',
                                class: 'text-center',
                                searchable: false
                            },
                            {
                                data: 'Conversion',
                                name: 'Conversion',
                                class: 'text-center',
                                searchable: false
                            },
                            {
                                data: 'warehouse',
                                name: 'warehouse.WarehouseName',
                                class: 'text-center',
                                searchable: false
                            },
                            {
                                data: 'Qty',
                                name: 'Qty',
                                class: 'text-center',
                                searchable: false
                            },
                            {
                                data: 'Notes',
                                name: 'Notes',
                                class: 'text-center',
                                searchable: false
                            },
                            {
                                data: 'pic',
                                name: 'LastUpdateBy',
                                class: 'text-center',
                                searchable: false
                            },
                            {
                                data: 'input_date',
                                name: 'updated_at',
                                class: 'text-center',
                                searchable: false
                            },
                            {
                                data: 'action',
                                name: 'action',
                                orderable: false,
                                searchable: false,
                                class: 'text-center'
                            },
                        ]
                    });
                },
                reloadTable() {
                    this.table.ajax.reload();
                }
            }
        });

        $(function() {
            // Filter button click
            $('#filter-btn').on('click', function() {
                app.reloadTable();
            });

            // Summary button click
            $('#summary-btn').on('click', function() {
                let date = $('#date').val();
                let warehouse = app.warehouse;
                let url = "{{ route('stock_report.summary') }}?date=" + date + "&WarehouseID=" + warehouse;
                window.location.href = url;
            });

            // Export button click
            $('#export-btn').on('click', function() {
                let date = $('#date').val();
                let warehouse = app.warehouse;
                let url = "{{ route('stock_report.export') }}?date=" + date + "&WarehouseID=" + warehouse;
                window.open(url, '_blank');
            });

            // Clear filter
            $('#date').on('change', function() {
                if ($(this).val() === '') {
                    app.reloadTable();
                }
            });

            // Delete button handler
            $('#datatable').on('click', '.delete-btn', function() {
                let el = $(this);

                Swal.fire({
                    icon: 'info',
                    title: 'Delete Stock Report',
                    text: "Are you sure you want to delete this stock report?",
                    showDenyButton: true,
                    confirmButtonText: 'Yes',
                    denyButtonText: 'No',
                }).then(function(result) {
                    if (result.isConfirmed) {
                        One.loader('show');
                        $.ajax({
                            url: el.data('url'),
                            type: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        }).done(function(data) {
                            One.loader('hide');
                            if (data.status === 'success') {
                                app.reloadTable();
                                One.helpers('jq-notify', {
                                    type: 'success',
                                    icon: 'fa fa-fw fa-circle-check',
                                    message: data.message ||
                                        'Stock Report successfully deleted!',
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Failed',
                                    text: data.message || 'Delete failed!'
                                });
                            }
                        }).fail(function() {
                            One.loader('hide');
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to delete stock report'
                            });
                        });
                    }
                });
            });

            @if (session()->has('success'))
                One.helpers('jq-notify', {
                    type: 'success',
                    icon: 'fa fa-fw fa-circle-check',
                    message: '{{ session('success') }}',
                });
            @endif

            @if (session()->has('error'))
                One.helpers('jq-notify', {
                    type: 'danger',
                    icon: 'fa fa-fw fa-times-circle',
                    message: '{{ session('error') }}',
                });
            @endif
        });
    </script>
@endsection
