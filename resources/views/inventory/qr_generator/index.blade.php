@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - QR Generator</title>
@endsection

@section('content')
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center mb-4">
            <h1 class="h3 fw-bold mb-3 mb-md-0">QR Generator</h1>
            @if(auth()->user()->hasAnyPermission(['admin', 'qr_generator.add']))
                <a class="btn btn-primary" href="{{ route('inventory.qr_generator.add') }}">
                    <i class="fa fa-fw fa-plus"></i> Add
                </a>
            @endif
        </div>

        <div class="block block-rounded">
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter" id="datatable">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>QR Content</th>
                                <th>Created At</th>
                                <th class="text-center" width="10%">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
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
    <script src="{{ asset('js/moment.min.js') }}"></script>
    <script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.js') }}"></script>
    <script src="{{ asset('js/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(function () {
            $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                ajax: { url: @json(route('inventory.qr_generator.datatable')) },
                order: [[2, 'desc']],
                columns: [
                    { data: 'code', name: 'code' },
                    { data: 'summary', name: 'summary', orderable: false, searchable: false },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        render: function (data, type) {
                            if (data && (type === 'display' || type === 'filter')) {
                                return moment(data).format('DD MMM YYYY');
                            }
                            return data;
                        }
                    },
                    { data: 'action', name: 'action', orderable: false, searchable: false, class: 'text-center' },
                ]
            });

            $('#datatable').on('click', '.delete-btn', function () {
                const el = $(this);

                Swal.fire({
                    icon: 'info',
                    title: 'Delete QR',
                    text: 'Are you sure you want to delete this QR?',
                    showDenyButton: true,
                    confirmButtonText: 'Yes',
                    denyButtonText: 'No',
                }).then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    One.loader('show');
                    $.ajax({
                        url: el.data('url'),
                        type: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': @json(csrf_token()) }
                    }).done(function (data) {
                        One.loader('hide');
                        if (data.status === 'success') {
                            $('#datatable').DataTable().ajax.reload();
                            One.helpers('jq-notify', {
                                type: 'success',
                                icon: 'fa fa-fw fa-circle-check',
                                message: 'QR successfully deleted!',
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Failed', text: 'Delete failed.' });
                        }
                    });
                });
            });

            @if(session()->has('type'))
                One.helpers('jq-notify', {
                    type: @json(session('type')),
                    icon: @json(session('icon')),
                    message: @json(session('message')),
                });
            @endif
        });
    </script>
@endsection
