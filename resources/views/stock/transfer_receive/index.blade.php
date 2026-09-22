@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Item Transfer Receive</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">
                <h1 class="h3 fw-bold mb-5">
                    Item Transfer Receive
                </h1>

                <div class="row justify-content-between align-items-end mb-3">
                    <div class="col-lg-auto col-12 mb-lg-0 mb-3">
                        <form autocomplete="off" id="filter-form">
                            <div class="d-flex flex-row align-items-end">
                                <div>
                                    <label class="form-label">Period</label>
                                    <input type="text" class="js-flatpickr form-control js-flatpickr-enabled flatpickr-input active" id="date" name="date" value="{{ request()->get('date' ?? '') }}">
                                </div>
                                <button type="submit" class="btn btn-secondary ms-3" id="filter"><i class="fa fa-fw fa-filter"></i>Filter</button>
                            </div>
                        </form>
                    </div>
                    @if(auth()->user()->hasAnyPermission(['admin', 'transfer_receive.add']))
                        <div class="col-auto">
                            <a class="btn btn-primary" href="{{ route('transfer_receive.add') }}"><i class="fa fa-fw fa-plus"></i> Add</a>
                        </div>
                    @endif
                </div>

                <div class="block block-rounded">
                    <div class="block-content">
                        <div class="table-responsive w-100">
                            <table class="table table-bordered table-striped table-vcenter table-fixed" id="datatable">
                                <thead>
                                <tr>
                                    <th class="text-center">Transaction No</th>
                                    <th class="text-center">Transaction Date</th>
                                    <th class="text-center">Execute No</th>
                                    <th class="text-center">Staff In Charge</th>
                                    <th class="text-center" width="10%">Action</th>
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

    <!-- Page JS Code -->
    <script>
        $(function() {
            $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                responsive: false,
                ajax: {
                    url: '{!! route('transfer_receive.datatable') !!}',
                    data: function(d) {
                        d.date = $('#date').val();
                    },
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
                order: [ [1, 'desc'] ],
                columns: [
                    { data: 'TransactionNo', name: 'TransactionNo', class: 'text-center'},
                    { data: 'TransactionDate', name: 'TransactionDate', class: 'text-center',
                        render: function ( data, type, row ){
                            if(data != ''){
                                if ( type === 'display' || type === 'filter' ){
                                    return moment(data).format('DD MMM YYYY');
                                }
                            }

                            return data;
                        }
                    },
                    { data: 'ExecuteNo', name: 'ExecuteNo', class: 'text-center'},
                    { data: 'StaffInChargeTo', name: 'StaffInChargeTo', class: 'text-center'},
                    { data: 'action', name: 'action', orderable: false, searchable: false, class: 'text-center'},
                ]
            });


            $('#datatable').on('click', '.delete-btn', function () {
                let el  = $(this);

                Swal.fire({
                    icon: 'info',
                    title: 'Delete Item Transfer Receive',
                    text: "Are you sure you want to delete this receive?",
                    showDenyButton: true,
                    confirmButtonText: 'Yes',
                    denyButtonText: 'No',
                }).then(function (result) {
                    if(result.isConfirmed){
                        $.ajax({
                            url: el.data('url'),
                            type: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        }).done(function (data)  {
                            $('#datatable').DataTable().ajax.reload();
                            One.helpers('jq-notify', {
                                type: 'success',
                                icon: 'fa fa-fw fa-circle-check',
                                message: data.message,
                            });
                        }).fail(function(xhr) {
                            One.helpers('jq-notify', {
                                type: 'danger',
                                icon: 'fa fa-fw fa-circle-xmark',
                                message: xhr.responseJSON.message || 'Something went wrong!',
                            });
                        });
                    }
                });
            });

            $("#date").flatpickr({
                mode: "range",
                dateFormat: "d/m/Y",
            });

            $('#filter-form').on('submit', function(e) {
                e.preventDefault();
                $('#datatable').DataTable().ajax.reload();
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
