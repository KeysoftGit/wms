@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Advanced Stock Opname</title>
@endsection

@section('content')
    <div class="px-lg-5 py-lg-4 p-3 bg-light rounded-3 shadow-sm">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <h1 class="h3 fw-bold mb-0 text-center text-md-start flex-grow-1 ms-md-4">
                Stock Opname
            </h1>
        </div>

        @if (session('message'))
            <div class="alert alert-{{ session('type', 'success') }} alert-dismissible fade show" role="alert">
                {{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (count($errors->all()) > 0)
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <p class="m-0 fs-6">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="row mb-3">
            @if (auth()->user()->hasAnyPermission(['admin', 'opname.add']))
                <div class="col-auto ms-auto">
                    <a class="btn btn-primary" href="{{ route('opname.add') }}">
                        <i class="fa fa-fw fa-plus"></i> Add
                    </a>
                </div>
            @endif
        </div>

        <div class="block block-rounded">
            <div class="block-content">
                <div class="table-responsive">
                    <table id="datatable" class="table table-bordered table-striped table-vcenter nowrap">
                        <thead>
                            <tr>
                                <th class="text-center">Transaction No</th>
                                <th class="text-center">Transaction Date</th>
                                <th class="text-center">Warehouse</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('js/plugins/sweetalert2/sweetalert2.min.js') }}"></script>

    <script>
        const table = $('#datatable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            responsive: false,
            ajax: {
                url: '{{ route('opname.datatable') }}'
            },
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
            order: [[1, 'desc']],
            columns: [
                { data: 'TransactionNo', name: 'TransactionNo', class: 'text-center' },
                { data: 'TransactionDate', name: 'TransactionDate', class: 'text-center' },
                { data: 'WarehouseID', name: 'WarehouseID', class: 'text-center' },
                { data: 'Status', name: 'Status', class: 'text-center' },
                { data: 'action', orderable: false, searchable: false, className: 'text-center' },
            ],
        });

        $('#datatable').on('click', '.delete-btn', function() {
            const url = $(this).data('url');
            Swal.fire({
                icon: 'warning',
                title: 'Delete Stock Opname',
                text: 'Are you sure you want to delete this stock opname?',
                showDenyButton: true,
                confirmButtonText: 'Yes',
                denyButtonText: 'No',
            }).then(result => {
                if (!result.isConfirmed) {
                    return;
                }

                One.loader('show');
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                }).done(response => {
                    One.loader('hide');
                    if (response.status === 'success') {
                        table.ajax.reload();
                        One.helpers('jq-notify', {
                            type: 'success',
                            icon: 'fa fa-fw fa-circle-check',
                            message: 'Stock Opname successfully deleted!',
                        });
                        return;
                    }

                    Swal.fire('Failed', response.message || 'Delete failed.', 'error');
                });
            });
        });
    </script>
@endsection
