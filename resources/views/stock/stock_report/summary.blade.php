@extends('layouts.admin')

@section('titles')
    <title>Keyonline - Stock Report Summary</title>
@endsection

@section('content')
    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">
                <div class="d-flex flex-row align-items-center mb-5">
                    <a href="{{ route('stock_report') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                    <h1 class="h3 fw-bold ms-4 mb-0">
                        Stock Report Summary (Grouped per Part)
                    </h1>
                </div>

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
                        <button type="button" id="export-btn" class="btn btn-success">
                            <i class="fa fa-fw fa-file-excel"></i> Export
                        </button>
                    </div>
                </div>

                <div class="block block-rounded">
                    <div class="block-content">
                        <div class="table-responsive w-100">
                            <table class="table table-bordered table-striped table-vcenter" id="datatable">
                                <thead>
                                    <tr>
                                        <th class="text-center">NO</th>
                                        <th class="text-center">TANGGAL</th>
                                        <th class="text-center">KODE BARANG</th>
                                        <th class="text-center">NAMA BARANG</th>
                                        <th class="text-center">SATUAN</th>
                                        <th class="text-center">GUDANG</th>
                                        <th class="text-center">HASIL OPNAME</th>
                                        <th class="text-center">INVENTORY</th>
                                        <th class="text-center">SELISIH (INV)</th>
                                        <th class="text-center">JUMLAH INPUTAN</th>
                                        <th class="text-center">NOTES</th>
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
                        pageLength: 25,
                        responsive: false,
                        ajax: {
                            url: '{!! route('stock_report.summary.datatable') !!}',
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
                            [0, 'asc']
                        ],
                        columns: [{
                                data: 'DT_RowIndex',
                                name: 'DT_RowIndex',
                                class: 'text-center',
                                orderable: false,
                                searchable: false
                            },
                            {
                                data: 'date_display',
                                name: 'Date',
                                class: 'text-center',
                                searchable: false
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
                                data: 'satuan',
                                name: 'satuan',
                                class: 'text-center',
                                searchable: false
                            },
                            {
                                data: 'warehouse_display',
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
                                data: 'inventory',
                                name: 'inventory',
                                class: 'text-center',
                                searchable: false
                            },
                            {
                                data: 'inventory_diff',
                                name: 'inventory_diff',
                                class: 'text-center',
                                searchable: false
                            },
                            {
                                data: 'input_count',
                                name: 'input_count',
                                class: 'text-center',
                                visible: false,
                                searchable: false
                            },
                            {
                                data: 'all_notes',
                                name: 'all_notes',
                                class: 'text-center',
                                searchable: false,
                                defaultContent: '-'
                            }
                        ],
                        drawCallback: function() {
                            this.api().column('input_count:name').visible(false);
                            this.api().column('all_notes:name').visible(true);
                        }
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

            // Export button click
            $('#export-btn').on('click', function() {
                let date = $('#date').val();
                let warehouse = app.warehouse;
                let url = "{{ route('stock_report.summary.export') }}?date=" + date + "&WarehouseID=" + warehouse;
                window.open(url, '_blank');
            });

            // Clear filter
            $('#date').on('change', function() {
                if ($(this).val() === '') {
                    app.reloadTable();
                }
            });
        });
    </script>
@endsection
