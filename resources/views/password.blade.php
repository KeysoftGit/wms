@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Change Password</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">

                <form autocomplete="off" method="post" enctype="multipart/form-data" action="{{ route('password.update') }}">
                    @csrf

                    <div class="d-flex flex-row align-items-center mb-5">
                        <a href="{{ route('dashboard') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                        <h1 class="h3 fw-bold ms-4 mb-0">
                            Change Password
                        </h1>
                    </div>

                    @if(count($errors->all()) > 0)
                        <div class="alert alert-danger">
                            @foreach($errors->all() as $error)
                                <p class="m-0 fs-6">{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <div class="block block-rounded">
                        <div class="block-content pb-3">
                            <button type="submit" class="btn btn-primary mb-3 fs-6"><i class="fa fa-fw fa-save me-2"></i>Save</button>

                            <div class="row">
                                <div class="col-lg-5 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Old Password <span class="text-danger">*</span></label>
                                        <input type="password" name="old_password" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                                        <input type="password" name="new_password" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                        <input type="password" name="confirm_password" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <!-- END Hero -->

@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-buttons-bs5/buttons.bootstrap5.min.css') }}">
@endsection

@section('scripts')
    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.js') }}"></script>

    <!-- Page JS Plugins -->


    <!-- Page JS Code -->
    <script>
        @if(session()->has('type'))
        One.helpers('jq-notify', {
            type: '{{ session('type') }}',
            icon: '{{ session('icon') }}',
            message: '{{ session('message') }}',
        });
        @endif
    </script>
@endsection
