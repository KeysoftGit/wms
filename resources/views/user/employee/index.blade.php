
@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Master Employee</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2  text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">
                <h1 class="h3 fw-bold mb-5">
                    Master Employee
                </h1>

                <div class="row justify-content-end mb-2">
                    @if(auth()->user()->hasAnyPermission(['admin', 'employee.add']))
                        <div class="col-auto">
                            <a class="btn btn-primary" href="{{ route('user.employee.add') }}"><i class="fa fa-fw fa-plus"></i> Add</a>
                        </div>
                    @endif
                </div>

                <div class="block block-rounded">
                    <div class="block-content">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter table-fixed" id="datatable">
                                <thead>
                                <tr>
                                    <th class="text-center">Employee ID</th>
                                    <th class="text-center">Active</th>
                                    <th class="text-center">Employee Name</th>
                                    <th class="text-center">Division</th>
                                    <th class="text-center">Position</th>
                                    <th class="text-center">Created At</th>
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
            $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                responsive: false,
                ajax: {
                    url: '{!! route('user.employee.datatable') !!}'
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
                order: [ [5, 'desc'] ],
                columns: [
                    { data: 'EmployeeID', name: 'EmployeeID', class: 'text-center'},
                    { data: 'Active', name: 'Active', class: 'text-center',
                        render: function ( data, type, row ){
                            if(data == 1){
                                return 'YES'
                            }
                            else {
                                return 'NO'
                            }
                        }
                    },
                    { data: 'EmployeeName', name: 'EmployeeName', class: 'text-center'},
                    { data: 'DivisionID', name: 'DivisionID', class: 'text-center'},
                    { data: 'Position', name: 'Position', class: 'text-center'},
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


            $('#datatable').on('click', '.delete-btn', function () {
                let el  = $(this);

                Swal.fire({
                    icon: 'info',
                    title: 'Delete Employee',
                    text: "Are you sure you want to delete this employee?",
                    showDenyButton: true,
                    confirmButtonText: 'Yes',
                    denyButtonText: 'No',
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
                                    message: 'Employee successfully deleted!',
                                });
                            }
                            else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Failed',
                                    text: 'Delete failed! Make sure this employee is not used in any other data!'
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
