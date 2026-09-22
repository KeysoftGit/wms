@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Add Division</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">

                <form autocomplete="off" method="post" enctype="multipart/form-data" action="{{ route('user.division.store') }}">
                    @csrf

                    <div class="d-flex flex-row align-items-center mb-5">
                        <a href="{{ route('user.division') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                        <h1 class="h3 fw-bold ms-4 mb-0">
                            Add Division
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

                            <div class="row align-items-center mb-3">
                                <div class="col-lg-2 col-12">
                                    <label class="form-label">Division ID <span class="text-danger">*</span></label>
                                    <input type="text" name="DivisionID" id="DivisionID" class="form-control" value="{{ old('DivisionID') ?? '' }}" required {{ old('automatic') ? 'disabled' : (count($errors->all()) > 0 ? '' : 'disabled') }}>
                                </div>
                                <div class="col-auto px-lg-0">
                                    <label class="form-label"></label>
                                    <div class="form-check ms-lg-4 pt-lg-2">
                                        <input class="form-check-input fs-6" type="checkbox" value="1" name="automatic" id="automatic" {{ old('automatic') ? 'checked' : (count($errors->all()) > 0 ? '' : 'checked') }}>
                                        <label class="form-check-label fs-6" for="automatic">
                                            Automatic
                                        </label>
                                    </div>
                                </div>
                                <div class="col-auto px-lg-0">
                                    <label class="form-label"></label>
                                    <div class="form-check ms-4 pt-lg-2">
                                        <input class="form-check-input fs-6" type="checkbox" value="1" name="Active" id="Active" {{ old('Active') ? 'checked' : (count($errors->all()) > 0 ? '' : 'checked') }}>
                                        <label class="form-check-label fs-6" for="Active">
                                            Active
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-5 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Division Name</label>
                                        <input type="text" name="DivisionName" value="{{ old('DivisionName') ?? '' }}"class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-3 col-12">
                                    <div class="mb-3" id="loading-sub">
                                        <label class="form-label">Sub Division</label>
                                        <select class="form-select" disabled>
                                            <option id="loading-text">Loading Divisions.....</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="sub-container">
                                        <label class="form-label">Sub Division</label>
                                        <select class="form-select form-select2" name="SubDivisionID" id="SubDivisionID">
                                            <option value="">-</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-12">
                                    <div class="mb-3" id="loading-employee">
                                        <label class="form-label">Staff In Charge</label>
                                        <select class="form-select" disabled>
                                            <option id="loading-text">Loading Employees.....</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="employee-container">
                                        <label class="form-label">Staff In Charge</label>
                                        <select class="form-select form-select2" name="StaffInChargeID" id="StaffInChargeID">
                                            <option value="">-</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-8 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea name="Notes" class="form-control" rows="3">{{ old('Notes') ?? '' }}</textarea>
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <style>
        .form-select {
            width: 100% !important;
        }
    </style>
@endsection

@section('scripts')
    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>

    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>

    <!-- Page JS Code -->
    <script>
        $(function() {
            $('.form-select2').select2({
                theme: 'bootstrap-5'
            });

            $('#sub-container').hide();
            $('#employee-container').hide();

            $('#automatic').on('change', function () {
                if ($('#automatic').is(':checked')) {
                    $('#DivisionID').attr('disabled', true);
                } else {
                    $('#DivisionID').removeAttr('disabled');
                }
            });


            // LOAD DIVISION

            $.ajax({
                url: '{!! route('misc.division') !!}',
                type: 'GET',
            }).done(function (data)  {
                if (data.status == 'success'){
                    var options = '';
                    var oldID = '{{ old("SubDivisionID") ?? "" }}';
                    for(var i = 0; i < data.data.length; i++){
                        options += `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                    }

                    $('#SubDivisionID').append(options);

                    $('#loading-sub').hide();
                    $('#sub-container').show();
                }
                else {
                    $('#loading-text').html('Something went wrong!');
                }
            });




            // LOAD EMPLOYEE

            $.ajax({
                url: '{!! route('misc.employee') !!}',
                type: 'GET',
            }).done(function (data)  {
                if (data.status == 'success'){
                    var options = '';
                    var oldID = '{{ old("StaffInChargeID") ?? "" }}';
                    for(var i = 0; i < data.data.length; i++){
                        options += `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                    }

                    $('#StaffInChargeID').append(options);

                    $('#loading-employee').hide();
                    $('#employee-container').show();
                }
                else {
                    $('#loading-text').html('Something went wrong!');
                }
            });
        });
    </script>
@endsection
