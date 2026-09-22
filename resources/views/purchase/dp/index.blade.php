
@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Direct Purchase</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">
                <h1 class="h3 fw-bold mb-5">
                    Direct Purchase
                </h1>

                <div class="row justify-content-between align-items-end mb-3">
                    <div class="col-lg-auto col-12 mb-lg-0 mb-3">
                        <form autocomplete="off" id="filter-form">
                            <div class="d-flex flex-row align-items-end">
                                <div>
                                    <label class="form-label">Period</label>
                                    <input type="text" class="js-flatpickr form-control js-flatpickr-enabled flatpickr-input active" id="date" name="date" value="{{ request()->get('date' ?? '') }}">
                                </div>
                                <div class="form-check ms-3">
                                    <input class="form-check-input" type="checkbox" value="1" id="outstanding" name="outstanding" {{ request()->get('outstanding') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="outstanding">
                                        Outstanding
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-secondary ms-3" id="filter"><i class="fa fa-fw fa-filter"></i>Filter</button>
                            </div>
                        </form>
                    </div>
                    @if(auth()->user()->hasAnyPermission(['admin', 'dp.add']))
                        <div class="col-auto">
                            <a class="btn btn-primary" href="{{ route('dp.add') }}"><i class="fa fa-fw fa-plus"></i> Add</a>
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
                                    <th class="text-center">Division</th>
                                    <th class="text-center">Supplier</th>
                                    <th class="text-center">Grand Total</th>
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
            var params = $('#filter-form').serialize();

            $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                responsive: false,
                ajax: {
                    url: '{!! route('dp.datatable') !!}',
                    data: {
                        date: '{{ request()->get('date') ?? '' }}',
                        outstanding: '{{ request()->get('outstanding') ?? '' }}'
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
                    { data: 'DivisionID', name: 'DivisionID', class: 'text-center'},
                    { data: 'SupplierID', name: 'SupplierID', class: 'text-center'},
                    { data: 'GrandTotal', name: 'GrandTotal', class: 'text-center',
                        render: $.fn.dataTable.render.number(',', '.', 0)
                    },
                    { data: 'action', name: 'action', orderable: false, searchable: false, class: 'text-center'},
                ]
            });


            $('#datatable').on('click', '.delete-btn', function () {
                let el  = $(this);

                Swal.fire({
                    icon: 'info',
                    title: 'Delete Direct Purchase',
                    text: "Are you sure you want to delete this direct purchase?",
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
                                    message: 'Direct Purchase successfully deleted!',
                                });
                            }
                            else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Failed',
                                    text: 'Delete failed! Make sure this order is not used in any other data!'
                                });
                            }
                        });
                    }
                });
            });

            $("#date").flatpickr({
                mode: "range",
                dateFormat: "d/m/Y",
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
