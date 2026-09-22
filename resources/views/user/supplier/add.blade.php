@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Add Supplier</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">

                <form autocomplete="off" method="post" enctype="multipart/form-data"
                    action="{{ route('user.supplier.store') }}">
                    @csrf

                    <div class="d-flex flex-row align-items-center mb-5">
                        <a href="{{ route('user.supplier') }}" class="h3 text-dark m-0"><i
                                class="fa fa-fw fa-arrow-left"></i></a>
                        <h1 class="h3 fw-bold ms-4 mb-0">
                            Add Supplier
                        </h1>
                    </div>

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
                                    <label class="form-label">Supplier ID <span class="text-danger">*</span></label>
                                    <input type="text" name="SupplierID" id="SupplierID" class="form-control"
                                        value="{{ old('SupplierID') ?? '' }}" required
                                        {{ old('automatic') ? 'disabled' : (count($errors->all()) > 0 ? '' : 'disabled') }}>
                                </div>
                                <div class="col-auto px-lg-0">
                                    <label class="form-label"></label>
                                    <div class="form-check ms-lg-4 pt-lg-2">
                                        <input class="form-check-input fs-6" type="checkbox" value="1" name="automatic"
                                            id="automatic"
                                            {{ old('automatic') ? 'checked' : (count($errors->all()) > 0 ? '' : 'checked') }}>
                                        <label class="form-check-label fs-6" for="automatic">
                                            Automatic
                                        </label>
                                    </div>
                                </div>
                                <div class="col-auto px-lg-0">
                                    <label class="form-label"></label>
                                    <div class="form-check ms-4 pt-lg-2">
                                        <input class="form-check-input fs-6" type="checkbox" value="1" name="Active"
                                            id="Active"
                                            {{ old('Active') ? 'checked' : (count($errors->all()) > 0 ? '' : 'checked') }}>
                                        <label class="form-check-label fs-6" for="Active">
                                            Active
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-6 col-12 pe-lg-5">
                                    <div class="mb-3">
                                        <label class="form-label">Supplier Name</label>
                                        <input type="text" name="SupplierName" value="{{ old('SupplierName') ?? '' }}"
                                            class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea name="Address" class="form-control" rows="3">{{ old('Address') ?? '' }}</textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">City</label>
                                        <input type="text" name="City" value="{{ old('City') ?? '' }}"
                                            class="form-control">
                                    </div>

                                    <div class="mb-3" id="loading-country">
                                        <label class="form-label">Country</label>
                                        <select class="form-select" disabled>
                                            <option id="loading-text">Loading Countries.....</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="country-container">
                                        <label class="form-label">Country</label>
                                        <select class="form-select form-select2" name="CountryID" id="CountryID">
                                            <option value="">-</option>
                                        </select>
                                    </div>

                                    <div class="mb-3" id="loading-division">
                                        <label class="form-label">Division</label>
                                        <select class="form-select" disabled>
                                            <option id="loading-text">Loading Divisions.....</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="division-container">
                                        <label class="form-label">Division</label>
                                        <select class="form-select form-select2" name="DivisionID" id="DivisionID">
                                            <option value="">-</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="Phone" value="{{ old('Phone') ?? '' }}"
                                            class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="Email" value="{{ old('Email') ?? '' }}"
                                            class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Website</label>
                                        <input type="text" name="Website" value="{{ old('Website') ?? '' }}"
                                            class="form-control">
                                    </div>
                                </div>

                                <div class="col-lg-6 col-12 ps-lg-5">
                                    <div class="mb-3">
                                        <label class="form-label">Contact Person</label>
                                        <input type="text" name="ContactPerson"
                                            value="{{ old('ContactPerson') ?? '' }}" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">NPWP</label>
                                        <input type="text" name="NPWP" value="{{ old('NPWP') ?? '' }}"
                                            class="form-control">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Term</label>
                                        <div class="d-flex flex-row align-items-center">
                                            <input type="text" name="Term" value="{{ old('Term') ?? '0' }}"
                                                class="form-control number-input" style="width: 100px;">
                                            <p class="mb-0 ms-2">Day(s)</p>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">ETA</label>
                                        <div class="d-flex flex-row align-items-center">
                                            <input type="text" name="LimitDaysETA"
                                                value="{{ old('LimitDaysETA') ?? '0' }}"
                                                class="form-control number-input" style="width: 100px;">
                                            <p class="mb-0 ms-2">Day(s)</p>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">ETD</label>
                                        <div class="d-flex flex-row align-items-center">
                                            <input type="text" name="LimitDaysETD"
                                                value="{{ old('LimitDaysETD') ?? '0' }}"
                                                class="form-control number-input" style="width: 100px;">
                                            <p class="mb-0 ms-2">Day(s)</p>
                                        </div>
                                    </div>
                                    <div class="form-check mb-3 ms-2">
                                        <input class="form-check-input fs-6" type="checkbox" value="1"
                                            name="Forwarder" id="Forwarder" {{ old('Forwarder') ? 'checked' : '' }}>
                                        <label class="form-check-label fs-6" for="Forwarder">
                                            Available As Forwarder
                                        </label>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Account Payable Limit</label>
                                        <input type="text" name="AccountPayableLimit"
                                            value="{{ old('AccountPayableLimit') ?? '0' }}"
                                            class="form-control number-input">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="block block-rounded">
                        <div class="block-content">
                            <h5>Personal Info</h5>

                            <div class="row mb-2">
                                <div class="col-lg-3 col-12">
                                    <label class="form-label">Individual ID</label>
                                    <input type="text" name="IndividualID" id="IndividualID" class="form-control"
                                        value="{{ old('IndividualID') ?? '' }}">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-lg-5 col-12">
                                    <label class="form-label">Individual Name</label>
                                    <input type="text" name="IndividualName" id="IndividualName" class="form-control"
                                        value="{{ old('IndividualName') ?? '' }}">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-lg-5 col-12">
                                    <label class="form-label">NPWP Owner</label>
                                    <input type="text" name="NPWPOwner" id="NPWPOwner" class="form-control"
                                        value="{{ old('NPWPOwner') ?? '' }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="block block-rounded">
                        <div class="block-content">
                            <h5>Bank Info</h5>

                            <div class="row mt-3">
                                <div class="col-lg-6 col-12">
                                    <div class="mb-3" style="max-width: 200px;">
                                        <label class="form-label">Bank ID</label>
                                        <input type="text" name="BankID" value="{{ old('BankID') ?? '' }}"
                                            class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Bank Name</label>
                                        <input type="text" name="BankName" value="{{ old('BankName') ?? '' }}"
                                            class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Bank Account No.</label>
                                        <input type="text" name="BankAccountNo"
                                            value="{{ old('BankAccountNo') ?? '' }}" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Bank Account Owner</label>
                                        <input type="text" name="BankAccountOwner"
                                            value="{{ old('BankAccountOwner') ?? '' }}" class="form-control">
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
    <link rel="stylesheet" href="{{ asset('js/plugins/flatpickr/flatpickr.min.css') }}">
@endsection

@section('scripts')
    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>

    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('js/plugins/flatpickr/flatpickr.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.5.4"></script>

    <!-- Page JS Code -->
    <script>
        $(function() {
            $('.form-select2').select2({
                theme: 'bootstrap-5'
            });

            $('#division-container').hide();
            $('#country-container').hide();
            $('.supplier-container').hide();

            $('#automatic').on('change', function() {
                if ($('#automatic').is(':checked')) {
                    $('#SupplierID').attr('disabled', true);
                } else {
                    $('#SupplierID').removeAttr('disabled');
                }
            });

            $('.js-flatpickr').flatpickr({
                dateFormat: "d/m/Y",
            });
            $('.js-flatpickr:visible').on('focus', function() {
                $(this).blur()
            });
            $('.js-flatpickr:visible').prop('readonly', false);

            let numeric = new AutoNumeric.multiple('.number-input', {
                decimalPlaces: 0,
                minimumValue: 0,
                allowDecimalPadding: "false",
                modifyValueOnWheel: false,
                digitGroupSeparator: '.',
                decimalCharacter: ',',
                unformatOnSubmit: true
            });



            // LOAD COUNTRY

            $.ajax({
                url: '{!! route('misc.country') !!}',
                type: 'GET',
            }).done(function(data) {
                if (data.status == 'success') {
                    var options = '';
                    var oldID = '{{ old('CountryID') ?? '' }}';
                    for (var i = 0; i < data.data.length; i++) {
                        options +=
                            `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                    }

                    $('#CountryID').append(options);

                    $('#loading-country').hide();
                    $('#country-container').show();
                } else {
                    $('#loading-text').html('Something went wrong!');
                }
            });


            $.ajax({
                url: '{!! route('misc.division') !!}',
                type: 'GET',
            }).done(function(data) {
                if (data.status == 'success') {
                    var options = '';
                    var oldID = '{{ old('DivisionID') ?? '' }}';
                    for (var i = 0; i < data.data.length; i++) {
                        options +=
                            `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                    }

                    $('#DivisionID').append(options);

                    $('#loading-division').hide();
                    $('#division-container').show();
                } else {
                    $('#loading-text').html('Something went wrong!');
                }
            });


        });
    </script>
@endsection
