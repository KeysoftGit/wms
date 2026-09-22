@extends('layouts.admin')

@section('titles')
    <title>Keysoft - Activity Log</title>
@endsection

@section('content')
    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0" style="width: 100%">
                <h1 class="h3 fw-bold mb-5">
                    <i class="fa fa-fw fa-history me-2"></i>Activity Log
                </h1>

                <!-- Filter Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form id="filterForm">
                            <div class="row g-3">
                                <!-- Date Range -->
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date">
                                </div>

                                <!-- User ID -->
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">User ID</label>
                                    <input type="text" class="form-control" id="user_id" name="user_id"
                                        placeholder="User ID">
                                </div>

                                <!-- Module -->
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Module</label>
                                    <select class="form-select" id="module" name="module">
                                        <option value="">All Modules</option>
                                        @foreach ($modules ?? [] as $module)
                                            @if (!empty($module))
                                                <option value="{{ $module }}">{{ $module }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Action -->
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Action</label>
                                    <select class="form-select" id="action" name="action">
                                        <option value="">All Actions</option>
                                        <option value="CREATE">CREATE</option>
                                        <option value="UPDATE">UPDATE</option>
                                        <option value="DELETE">DELETE</option>
                                        <option value="VIEW">VIEW</option>
                                    </select>
                                </div>

                                <!-- Reference ID -->
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Reference ID</label>
                                    <input type="text" class="form-control" id="reference" name="reference"
                                        placeholder="Transaction No, ID, etc">
                                </div>

                                <!-- Filter Buttons -->
                                <div class="col-md-6 d-flex align-items-end gap-2 mt-3">
                                    <button type="button" class="btn btn-primary" id="applyFilter">
                                        <i class="fa fa-fw fa-filter"></i> Apply Filter
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="resetFilter">
                                        <i class="fa fa-fw fa-refresh"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- DataTable -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter nowrap" id="datatable"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th class="text-center">#</th>
                                        <th class="text-center">Date & Time</th>
                                        <th class="text-center">User ID</th>
                                        <th class="text-center">Module</th>
                                        <th class="text-center">Action</th>
                                        <th class="text-center">Reference ID</th>
                                        <th class="text-center">Route</th>
                                        <th class="text-center">Method</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Payload Preview</th>
                                        <th class="text-center">Action</th>
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

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">
                        <i class="fa fa-fw fa-info-circle me-2"></i>Activity Log Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">User ID</label>
                            <p id="detail-user" class="form-control-static"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date & Time</label>
                            <p id="detail-time" class="form-control-static"></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Module</label>
                            <p id="detail-module" class="form-control-static"></p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Action</label>
                            <p id="detail-action" class="form-control-static"></p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Reference ID</label>
                            <p id="detail-reference" class="form-control-static"></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Route</label>
                            <p id="detail-route" class="form-control-static"></p>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Method</label>
                            <p id="detail-method" class="form-control-static"></p>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Status</label>
                            <p id="detail-status" class="form-control-static"></p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Payload</label>
                        <pre id="detail-payload" class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;"></pre>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="copyPayload">
                        <i class="fa fa-fw fa-copy"></i> Copy Payload
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-buttons-bs5/buttons.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/sweetalert2/sweetalert2.min.css') }}">
    <style>
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .badge {
            font-size: 0.85em;
        }

        #detail-payload {
            font-size: 0.9em;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        .form-control-static {
            min-height: calc(1.5em + .75rem + 2px);
            padding: .375rem .75rem;
            border: 1px solid #d5d5d5;
            border-radius: .25rem;
            background-color: #f8f9fa;
        }
    </style>
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

    <!-- Page JS Code -->
    <script>
        $(function() {
            // Set default dates (last 7 days)
            var today = new Date();
            var lastWeek = new Date(today);
            lastWeek.setDate(today.getDate() - 7);

            $('#start_date').val(lastWeek.toISOString().split('T')[0]);
            $('#end_date').val(today.toISOString().split('T')[0]);

            // Initialize DataTable
            var table = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                responsive: false,
                scrollX: true,
                order: [
                    [1, 'desc']
                ], // Sort by date descending
                ajax: {
                    url: '{!! route('activity_log.datatable') !!}',
                    data: function(d) {
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.user_id = $('#user_id').val();
                        d.module = $('#module').val();
                        d.action = $('#action').val();
                        d.reference = $('#reference').val();
                    }
                },
                dom: '<"row"<"col-md-6"l><"col-md-6"f>>' +
                    '<"row"<"col-md-12"tr>>' +
                    '<"row"<"col-md-6"i><"col-md-6"p>>',
                language: {
                    lengthMenu: "Show _MENU_ entries",
                    search: "_INPUT_",
                    searchPlaceholder: "Search..",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "No entries found",
                    paginate: {
                        first: '<i class="fa fa-angle-double-left"></i>',
                        previous: '<i class="fa fa-angle-left"></i>',
                        next: '<i class="fa fa-angle-right"></i>',
                        last: '<i class="fa fa-angle-double-right"></i>'
                    },
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        class: 'text-center',
                        width: '50px'
                    },
                    {
                        data: 'EntryTime',
                        name: 'EntryTime',
                        class: 'text-center',
                        width: '150px'
                    },
                    {
                        data: 'UserID',
                        name: 'UserID',
                        class: 'text-center',
                        width: '100px'
                    },
                    {
                        data: 'FrmName',
                        name: 'FrmName',
                        class: 'text-center',
                        width: '100px'
                    },
                    {
                        data: 'Action',
                        name: 'Action',
                        class: 'text-center',
                        width: '100px'
                    },
                    {
                        data: 'ReffID',
                        name: 'ReffID',
                        class: 'text-center',
                        width: '150px'
                    },
                    {
                        data: 'RoutePath',
                        name: 'RoutePath',
                        class: 'text-center',
                        width: '200px'
                    },
                    {
                        data: 'method_badge',
                        name: 'Method',
                        class: 'text-center',
                        orderable: true,
                        searchable: true,
                        width: '80px'
                    },
                    {
                        data: 'ResponseStatus',
                        name: 'ResponseStatus',
                        class: 'text-center',
                        width: '80px'
                    },
                    {
                        data: 'payload_preview',
                        name: 'payload_preview',
                        class: 'text-center',
                        orderable: false,
                        searchable: false,
                        width: '150px'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        class: 'text-center',
                        width: '80px'
                    }
                ]
            });

            // Apply filter
            $('#applyFilter').on('click', function() {
                table.ajax.reload();
            });

            // Reset filter
            $('#resetFilter').on('click', function() {
                $('#filterForm')[0].reset();
                $('#start_date').val(lastWeek.toISOString().split('T')[0]);
                $('#end_date').val(today.toISOString().split('T')[0]);
                table.ajax.reload();
            });

            // Enter key in filter inputs
            $('#filterForm input, #filterForm select').on('keyup', function(e) {
                if (e.keyCode === 13) {
                    table.ajax.reload();
                }
            });

            // View detail modal
            // View detail modal - Perbaikan untuk Base64
            $(document).on('click', '.view-detail', function() {
                var payloadEncoded = $(this).data('payload-encoded');
                var formattedPayload = '';

                if (payloadEncoded && payloadEncoded !== '') {
                    try {
                        // Decode dari base64
                        var decodedPayload = atob(payloadEncoded);

                        // Parse JSON dan format dengan pretty print
                        var jsonObj = JSON.parse(decodedPayload);
                        formattedPayload = JSON.stringify(jsonObj, null, 2);
                    } catch (e) {
                        console.error('Error decoding/parsing payload:', e);
                        formattedPayload = 'Error displaying payload: ' + e.message + '\n\nRaw data:\n' +
                            (payloadEncoded ? atob(payloadEncoded) : 'No data');
                    }
                } else {
                    formattedPayload = 'No payload data';
                }

                // Set modal data
                $('#detail-user').text($(this).data('user') || '-');
                $('#detail-time').text($(this).data('time') || '-');
                $('#detail-module').text($(this).data('module') || '-');
                $('#detail-action').text($(this).data('action') || '-');
                $('#detail-reference').text($(this).data('reference') || '-');
                $('#detail-route').text($(this).data('route') || '-');
                $('#detail-method').text($(this).data('method') || '-');
                $('#detail-status').html($(this).data('status') || '-');
                $('#detail-payload').text(formattedPayload);

                $('#detailModal').modal('show');
            });

            // Copy payload to clipboard
            $('#copyPayload').on('click', function() {
                var payloadText = $('#detail-payload').text();

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(payloadText).then(function() {
                        One.helpers('jq-notify', {
                            type: 'success',
                            icon: 'fa fa-fw fa-check',
                            message: 'Payload copied to clipboard!'
                        });
                    }).catch(function(err) {
                        console.error('Copy failed:', err);
                        One.helpers('jq-notify', {
                            type: 'danger',
                            icon: 'fa fa-fw fa-times',
                            message: 'Failed to copy payload'
                        });
                    });
                } else {
                    // Fallback for older browsers
                    var textArea = document.createElement("textarea");
                    textArea.value = payloadText;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand("copy");
                    document.body.removeChild(textArea);

                    One.helpers('jq-notify', {
                        type: 'success',
                        icon: 'fa fa-fw fa-check',
                        message: 'Payload copied to clipboard!'
                    });
                }
            });

            // Initialize tooltips
            $(document).on('mouseenter', '[data-bs-toggle="tooltip"]', function() {
                var tooltip = new bootstrap.Tooltip(this);
                tooltip.show();
            });

            @if (session()->has('type'))
                One.helpers('jq-notify', {
                    type: '{{ session('type') }}',
                    icon: '{{ session('icon') }}',
                    message: '{{ session('message') }}',
                });
            @endif
        });
    </script>
@endsection
