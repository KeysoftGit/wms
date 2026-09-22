@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Edit Warehouse</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">

                <form autocomplete="off" method="post" enctype="multipart/form-data" action="{{ route('warehouse.update') }}">
                    @csrf

                    <div class="d-flex flex-row align-items-center mb-5">
                        <a href="{{ route('warehouse') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                        <h1 class="h3 fw-bold ms-4 mb-0">
                            Edit Warehouse
                        </h1>
                    </div>

                    <input type="hidden" name="id" value="{{ $warehouse->WarehouseID }}">

                    @if (count($errors->all()) > 0)
                        <div class="alert alert-danger">
                            @foreach ($errors->all() as $error)
                                <p class="m-0 fs-6">{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <div class="block block-rounded">
                        <div class="block-content pb-3">
                            <button type="submit" class="btn btn-primary mb-3 fs-6"><i
                                    class="fa fa-fw fa-save me-2"></i>Save</button>

                            <div class="row align-items-center mb-3">
                                <div class="col-lg-2 col-12">
                                    <label class="form-label">Warehouse ID <span class="text-danger">*</span></label>
                                    <input type="text" name="WarehouseID" id="WarehouseID" class="form-control"
                                        value="{{ old('WarehouseID') ?? $warehouse->WarehouseID }}" readonly>
                                </div>
                                <div class="col-auto px-lg-0">
                                    <label class="form-label"></label>
                                    <div class="form-check ms-lg-4 pt-lg-2">
                                        <input class="form-check-input fs-6" type="checkbox" value="1" name="Active"
                                            id="Active"
                                            {{ old('Active') ? 'checked' : ($warehouse->Active == 1 ? 'checked' : '') }}>
                                        <label class="form-check-label fs-6" for="Active">
                                            Active
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-6 col-12 pe-lg-5">
                                    <div class="mb-3">
                                        <label class="form-label">Warehouse Name</label>
                                        <input type="text" name="WarehouseName"
                                            value="{{ old('WarehouseName') ?? $warehouse->WarehouseName }}"class="form-control">
                                    </div>
                                    @if ($showDivisions)
                                        <label class="form-label">Division</label>
                                        <select class="form-select form-select2" name="DivisionID" id="DivisionID">
                                            <option value="" disabled {{ old('DivisionID') ? '' : 'selected' }}>--
                                                Select Division --</option>
                                            <option value="" {{ $warehouse->DivisionID ? '' : 'selected' }}>-- No
                                                Division --</option>
                                            @foreach ($divisions as $division)
                                                <option value="{{ $division->DivisionID }}"
                                                    {{ $warehouse->DivisionID == $division->DivisionID ? 'selected' : '' }}>
                                                    {{ $division->DivisionID }} - {{ $division->DivisionName }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif

                                    <div class="mb-3 mt-3">
                                        <label class="form-label">Location</label>
                                        <textarea name="Location" class="form-control" rows="3">{{ old('Location') ?? $warehouse->Location }}</textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea name="Notes" class="form-control" rows="3">{{ old('Notes') ?? $warehouse->Notes }}</textarea>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-12 ps-lg-5">
                                    <div class="mb-3" id="loading-parent">
                                        <label class="form-label">Parent</label>
                                        <select class="form-select" disabled>
                                            <option id="loading-text">Loading Warehouses.....</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="parent-container">
                                        <label class="form-label">Parent</label>
                                        <select class="form-select form-select2" name="ParentID" id="ParentID">
                                            <option value="">-</option>
                                        </select>
                                    </div>

                                    <div class="mb-3" id="loading-staff">
                                        <label class="form-label">Staff In Charge</label>
                                        <select class="form-select" disabled>
                                            <option id="loading-text">Loading Employees.....</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="staff-container">
                                        <label class="form-label">Staff In Charge</label>
                                        <select class="form-select form-select2" name="StaffInChargeID"
                                            id="StaffInChargeID">
                                            <option value="">-</option>
                                        </select>
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
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
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

            $('#parent-container').hide();
            $('#staff-container').hide();

            $('#automatic').on('change', function() {
                if ($('#automatic').is(':checked')) {
                    $('#WarehouseID').attr('disabled', true);
                } else {
                    $('#WarehouseID').removeAttr('disabled');
                }
            });



            // LOAD WAREHOUSE

            $.ajax({
                url: '{!! route('misc.warehouse') !!}',
                type: 'GET',
                data: {
                    id: '{{ $warehouse->WarehouseID }}'
                }
            }).done(function(data) {
                if (data.status == 'success') {
                    var options = '';
                    var oldID = '{{ old('ParentID') ?? $warehouse->ParentID }}';
                    for (var i = 0; i < data.data.length; i++) {
                        options +=
                            `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                    }

                    $('#ParentID').append(options);

                    $('#loading-parent').hide();
                    $('#parent-container').show();
                } else {
                    $('#loading-text').html('Something went wrong!');
                }
            });


            // LOAD EMPLOYEE

            $.ajax({
                url: '{!! route('misc.employee') !!}',
                type: 'GET',
            }).done(function(data) {
                if (data.status == 'success') {
                    var options = '';
                    var oldID = '{{ old('StaffInChargeID') ?? $warehouse->StaffInChargeID }}';
                    for (var i = 0; i < data.data.length; i++) {
                        options +=
                            `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                    }

                    $('#StaffInChargeID').append(options);

                    $('#loading-staff').hide();
                    $('#staff-container').show();
                } else {
                    $('#loading-text').html('Something went wrong!');
                }
            });
        });
    </script>
@endsection
