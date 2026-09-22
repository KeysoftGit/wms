
@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Inventory Part</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">
                <h1 class="h3 fw-bold mb-2">
                    Inventory Part
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt mb-0">
                        <li class="breadcrumb-item">Inventory</li>
                        <li class="breadcrumb-item active" aria-current="page">Part</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="block block-rounded shadow-sm">
            <div class="block-header block-header-default">
                <h3 class="block-title">Data Part</h3>
                <div class="block-options">
                    {{-- <button type="button" class="btn btn-sm btn-alt-info me-1" id="generate-qr-btn">
                        <i class="fa fa-fw fa-qrcode"></i> Generate QR
                    </button> --}}
                    @if(auth()->user()->hasAnyPermission(['admin', 'part.view']))
                        <a class="btn btn-sm btn-alt-success me-1" href="{{ route('inventory.part.export') }}">
                            <i class="fa fa-fw fa-file-excel"></i> Export All
                        </a>
                    @endif
                    @if(auth()->user()->hasAnyPermission(['admin', 'part.add']))
                        <a class="btn btn-sm btn-primary" href="{{ route('inventory.part.add') }}">
                            <i class="fa fa-fw fa-plus"></i> Add Part
                        </a>
                    @endif
                </div>
            </div>
            <div class="block-content pb-3">
                <table class="table table-bordered table-striped table-hover table-vcenter nowrap w-100" id="datatable">
                    <thead class="table-light">
                    <tr>
                        {{-- <th class="text-center" style="width: 50px;"><input type="checkbox" id="check-all" class="form-check-input"></th> --}}
                        <th class="text-center">Part ID</th>
                        <th class="text-center">Active</th>
                        <th>Part Name</th>
                        <th>Category</th>
                        <th>Specification</th>
                        <th>Variant</th>
                        <th>Type</th>
                        <th class="text-center">Created At</th>
                        <th class="text-center" style="width: 100px;">Action</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- END Hero -->

@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-buttons-bs5/buttons.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/sweetalert2/sweetalert2.min.css') }}">
    <style>
        /* Modern Scrollbar for DataTables */
        .dataTables_scrollBody::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }
        .dataTables_scrollBody::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .dataTables_scrollBody::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Essential Sticky Logic for DataTables scrollX */
        .dataTables_scrollHead,
        .dataTables_scrollBody {
            overflow: visible !important;
        }

        .dataTables_scroll {
            position: relative;
        }

        /* Target BOTH the header table and the body table */
        .dataTables_scrollHead table,
        .dataTables_scrollBody table {
            border-collapse: separate !important;
            border-spacing: 0;
        }

        /*
        Left Sticky: First column (Checkbox)
        .dataTables_scrollHead th:nth-child(1),
        .dataTables_scrollBody td:nth-child(1) {
            position: sticky !important;
            left: 0 !important;
            z-index: 40 !important;
        }
        */

        /* Left Sticky: Second column (Part ID) */
        .dataTables_scrollHead th:nth-child(1),
        .dataTables_scrollBody td:nth-child(1) {
            position: sticky !important;
            left: 0 !important;
            z-index: 40 !important;
            border-right: 2px solid #e2e8f0 !important;
        }

        /* Right Sticky: Last column (Action) */
        .dataTables_scrollHead th:last-child,
        .dataTables_scrollBody td:last-child {
            position: sticky !important;
            right: 0 !important;
            z-index: 40 !important;
            border-left: 2px solid #e2e8f0 !important;
        }

        /* Backgrounds for Header Sticky Cells */
        .dataTables_scrollHead th:nth-child(1),
        .dataTables_scrollHead th:last-child {
            background-color: #f8fafc !important;
        }

        /* Backgrounds for Body Sticky Cells */
        .dataTables_scrollBody td:nth-child(1),
        .dataTables_scrollBody td:last-child {
            background-color: #ffffff !important;
        }

        /* Striped & Hover effects for Sticky Cells */
        .table-striped tbody tr:nth-of-type(odd) td:nth-child(1),
        .table-striped tbody tr:nth-of-type(odd) td:last-child {
            background-color: #f8fafc !important;
        }

        .table-hover tbody tr:hover td:nth-child(1),
        .table-hover tbody tr:hover td:last-child {
            background-color: #f1f5f9 !important;
        }

        /* Fix for DataTables alignment during scroll */
        .dataTables_scrollHead, .dataTables_scrollBody {
            overflow: visible !important;
        }
        .dataTables_scroll {
            overflow-x: auto !important;
            overflow-y: hidden !important;
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
            // let selectedParts = [];

            /* const updateCheckAll = () => {
                let currentPageCheckboxes = $('.part-checkbox');
                if (currentPageCheckboxes.length === 0) {
                    $('#check-all').prop('checked', false);
                    return;
                }

                let allChecked = true;
                currentPageCheckboxes.each(function() {
                    if (!selectedParts.includes($(this).val())) {
                        allChecked = false;
                        return false;
                    }
                });
                $('#check-all').prop('checked', allChecked);

                // Update button text with count
                if (selectedParts.length > 0) {
                    $('#generate-qr-btn').html('<i class="fa fa-fw fa-qrcode"></i> QR (' + selectedParts.length + ')');
                    $('#generate-qr-btn').removeClass('btn-alt-info').addClass('btn-info');
                } else {
                    $('#generate-qr-btn').html('<i class="fa fa-fw fa-qrcode"></i> Generate QR');
                    $('#generate-qr-btn').removeClass('btn-info').addClass('btn-alt-info');
                }
            }; */

            $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                responsive: false,
                scrollX: true,
                ajax: {
                    url: '{!! route('inventory.part.datatable') !!}'
                },
                /* drawCallback: function() {
                    updateCheckAll();
                }, */
                language: {
                    lengthMenu: "_MENU_",
                    search: "_INPUT_",
                    searchPlaceholder: "Search Part..",
                    info: "Page <strong>_PAGE_</strong> of <strong>_PAGES_</strong>",
                    paginate: {
                        first: '<i class="fa fa-angle-double-left"></i>',
                        previous: '<i class="fa fa-angle-left"></i>',
                        next: '<i class="fa fa-angle-right"></i>',
                        last: '<i class="fa fa-angle-double-right"></i>'
                    },
                },
                order: [ [8, 'desc'] ],
                columns: [
                    /* { data: 'PartID', name: 'PartID', orderable: false, searchable: false, class: 'text-center',
                        render: function ( data, type, row ){
                            let checked = selectedParts.includes(data) ? 'checked' : '';
                            return '<input type="checkbox" class="part-checkbox form-check-input" value="' + data + '" ' + checked + '>';
                        }
                    }, */
                    { data: 'PartID', name: 'PartID', class: 'text-center fw-semibold'},
                    { data: 'Active', name: 'Active', class: 'text-center',
                        render: function ( data, type, row ){
                            if(data == 1){
                                return '<span class="badge bg-success">YES</span>'
                            }
                            else {
                                return '<span class="badge bg-danger">NO</span>'
                            }
                        }
                    },
                    { data: 'PartName', name: 'PartName'},
                    { data: 'Category', name: 'Category'},
                    { data: 'Specification', name: 'Specification'},
                    { data: 'Variant', name: 'Variant'},
                    { data: 'Type', name: 'Type'},
                    { data: 'created_at', name: 'created_at', class: 'text-center',
                        render: function ( data, type, row ){
                            if(data != ''){
                                if ( type === 'display' || type === 'filter' ){
                                    return moment(data).format('DD MMM YYYY');
                                }
                            }

                            return data;
                        }
                    },
                    { data: 'action', name: 'action', orderable: false, searchable: false, class: 'text-center'},
                ]
            });

            /* $('#check-all').on('click', function () {
                let isChecked = $(this).prop('checked');

                $('.part-checkbox').each(function() {
                    let id = $(this).val();
                    $(this).prop('checked', isChecked);

                    if (isChecked) {
                        if (!selectedParts.includes(id)) {
                            selectedParts.push(id);
                        }
                    } else {
                        selectedParts = selectedParts.filter(item => item !== id);
                    }
                });

                updateCheckAll();
            });

            $('#datatable').on('change', '.part-checkbox', function () {
                let id = $(this).val();
                if ($(this).prop('checked')) {
                    if (!selectedParts.includes(id)) {
                        selectedParts.push(id);
                    }
                } else {
                    selectedParts = selectedParts.filter(item => item !== id);
                }
                updateCheckAll();
            });

            $('#generate-qr-btn').on('click', function () {
                if (selectedParts.length == 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Warning',
                        text: 'Please select at least one part!'
                    });
                    return;
                }

                let url = '{{ route('inventory.part.qr') }}?' + $.param({ids: selectedParts});
                window.location.href = url;
            }); */


            $('#datatable').on('click', '.delete-btn', function () {
                let el  = $(this);

                Swal.fire({
                    icon: 'warning',
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then(function (result) {
                    if(result.isConfirmed){
                        One.loader('show');
                        $.ajax({
                            url: el.data('url'),
                            type: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        }).done(function (data)  {
                            One.loader('hide');
                            if (data.status == 'success'){
                                $('#datatable').DataTable().ajax.reload();
                                One.helpers('jq-notify', {
                                    type: 'success',
                                    icon: 'fa fa-fw fa-circle-check',
                                    message: 'Part successfully deleted!',
                                });
                            }
                            else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Failed',
                                    text: 'Delete failed! Make sure this part is not used in any other data!'
                                });
                            }
                        });
                    }
                });
            });


            @if(session()->has('type'))
            One.helpers('jq-notify', {
                type: '{{ session('type') }}',
                icon: '{{ session('icon') }}',
                message: '{{ session('message') }}',
            });
            @endif
        });
    </script>
@endsection
