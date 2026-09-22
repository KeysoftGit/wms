@extends('layouts.admin')

@section('titles')
    <title>Keysoft - Control Panel</title>
@endsection

@section('content')
    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">
                <h1 class="h3 fw-bold mb-5">
                    Control Panel
                </h1>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Alert Error --}}
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-content">
                <div class="table-responsive">
                    <table id="datatable" class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr class="text-center">
                                <th>ID</th>
                                <th>Setting Key</th>
                                <th>Notes</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
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
        $('#datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('control_panel.datatable') }}',
            columns: [{
                    data: 'id',
                    name: 'id',
                    class: 'text-center'
                },
                {
                    data: 'SettingKey',
                    name: 'SettingKey',
                    class: 'text-center'
                },
                {
                    data: 'Notes',
                    name: 'Notes',
                    class: 'text-center'
                },
                {
                    data: 'action',
                    name: 'action',
                    class: 'text-center',
                    orderable: false,
                    searchable: false
                },
            ]
        });


        $(document).on('click', '.toggle-btn', function() {
            let id = $(this).data('id');
            let button = $(this);

            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to toggle this setting?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, toggle it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('control_panel.toggle') }}",
                        type: "PUT",
                        data: {
                            id: id,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    'Updated!',
                                    response.message,
                                    'success'
                                );

                                $('#datatable').DataTable().ajax.reload(null, false);
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error!', xhr.responseJSON.message ??
                                'Something went wrong.', 'error');
                        }
                    });
                }
            });
        });
    </script>
@endsection
