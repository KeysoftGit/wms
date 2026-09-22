@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Edit Employee</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">

                <form autocomplete="off" method="post" enctype="multipart/form-data" action="{{ route('user.employee.update') }}">
                    @csrf

                    <input type="hidden" name="id" value="{{ $employee->EmployeeID }}">

                    <div class="d-flex flex-row align-items-center mb-5">
                        <a href="{{ route('user.employee') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                        <h1 class="h3 fw-bold ms-4 mb-0">
                            Edit Employee
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
                                <div class="col-lg-2">
                                    <label class="form-label">Employee ID <span class="text-danger">*</span></label>
                                    <input type="text" name="EmployeeID" id="EmployeeID" class="form-control" value="{{ old('EmployeeID') ?? $employee->EmployeeID }}" required readonly>
                                </div>
                                <div class="col-auto px-lg-0">
                                    <label class="form-label"></label>
                                    <div class="form-check ms-lg-4 pt-lg-2">
                                        <input class="form-check-input fs-6" type="checkbox" value="1" name="Active" id="Active" {{ old('Active') ? 'checked' : ($employee->Active == 1 ? 'checked' : '') }}>
                                        <label class="form-check-label fs-6" for="Active">
                                            Active
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-6 col-12 pe-lg-5">
                                    <div class="mb-3">
                                        <label class="form-label" for="Photo">Photo (Leave empty if you're not changing it)</label>
                                        <div class="row">
                                            <div class="col">
                                                <input class="form-control" type="file" id="Photo" name="Photo" accept="image/png, image/jpg, image/jpeg">
                                            </div>
                                            <div class="col-auto">
                                                @if($employee->Photo2 != null)
                                                    <a href="{{ asset('storage/employee/'. $employee->Photo2) }}" title="See Original" class="btn btn-alt-secondary" target="_blank"><i class="fa fa-fw fa-eye"></i></a>
                                                @else
                                                    <button type="button" class="btn btn-alt-secondary" disabled><i class="fa fa-fw fa-eye"></i></button>
                                                @endif
                                            </div>
                                        </div>

                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" name="FirstName" value="{{ old('FirstName') ?? $employee->FirstName }}" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" name="LastName" value="{{ old('LastName') ?? $employee->LastName }}" class="form-control">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Gender</label>
                                        <div class="space-y-2">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" id="genderMale" name="Gender" value="M" {{ old('Gender') ? (old('Gender') == 'M' ? 'checked' : '') : ($employee->Gender == 'M' ? 'checked' : '') }}>
                                                <label class="form-check-label" for="genderMale">Male</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" id="genderFemale" name="Gender" value="F" {{ old('Gender') ? (old('Gender') == 'F' ? 'checked' : '') : ($employee->Gender == 'F' ? 'checked' : '') }}>
                                                <label class="form-check-label" for="genderFemale">Female</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3" style="max-width: 200px;">
                                        <label class="form-label" for="BirthDate">Birthdate</label>
                                        <input type="text" class="js-flatpickr form-control js-flatpickr-enabled flatpickr-input active" id="BirthDate" name="BirthDate" placeholder="d/m/Y" value="{{ old('BirthDate') ?? ($employee->BirthDate != null ? date('d/m/Y', strtotime($employee->BirthDate)) : '') }}" readonly="readonly">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Blood Type</label>
                                        <div class="space-y-2">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" id="BloodTypeA" name="BloodType" value="A" {{ old('BloodType') ? (old('BloodType') == 'A' ? 'checked' : '') : ($employee->BloodType == 'A' ? 'checked' : '') }}>
                                                <label class="form-check-label" for="BloodTypeA">A</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" id="BloodTypeB" name="BloodType" value="B" {{ old('BloodType') ? (old('BloodType') == 'B' ? 'checked' : '') : ($employee->BloodType == 'B' ? 'checked' : '') }}>
                                                <label class="form-check-label" for="BloodTypeB">B</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" id="BloodTypeAB" name="BloodType" value="AB" {{ old('BloodType') ? (old('BloodType') == 'AB' ? 'checked' : '') : ($employee->BloodType == 'AB' ? 'checked' : '') }}>
                                                <label class="form-check-label" for="BloodTypeAB">AB</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" id="BloodTypeO" name="BloodType" value="O" {{ old('BloodType') ? (old('BloodType') == 'O' ? 'checked' : '') : ($employee->BloodType == 'O' ? 'checked' : '') }}>
                                                <label class="form-check-label" for="BloodTypeO">O</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Phone 1</label>
                                        <input type="text" name="Phone1" value="{{ old('Phone1') ?? $employee->Phone1 }}" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Phone 2</label>
                                        <input type="text" name="Phone2" value="{{ old('Phone2') ?? $employee->Phone2 }}" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="Email" value="{{ old('Email') ?? $employee->Email }}" class="form-control">
                                    </div>
                                </div>

                                <div class="col-lg-6 col-12 ps-lg-5">
                                    <div class="mb-3">
                                        <label class="form-label">ID No</label>
                                        <input type="text" name="IDNumber" value="{{ old('IDNumber') ?? $employee->IDNumber }}" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">NPWP</label>
                                        <input type="text" name="NPWP" value="{{ old('NPWP') ?? $employee->NPWP }}" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Marital Status</label>
                                        <select class="form-select" name="MatrialStatus">
                                            <option value="SINGLE" {{ old('MatrialStatus') ? (old('MatrialStatus') == 'SINGLE' ? 'selected' : '') : ($employee->MatrialStatus == 'SINGLE' ? 'selected' : '') }}>SINGLE</option>
                                            <option value="MARRIED" {{ old('MatrialStatus') ? (old('MatrialStatus') == 'MARRIED' ? 'selected' : '') : ($employee->MatrialStatus == 'MARRIED' ? 'selected' : '') }}>MARRIED</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea name="Address" class="form-control" rows="3">{{ old('Address') ?? $employee->Address }}</textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">City</label>
                                        <input type="text" name="City" value="{{ old('City') ?? $employee->City }}" class="form-control">
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
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="block block-rounded">
                        <div class="block-content">
                            <div class="row mt-3">
                                <div class="col-lg-6 col-12 pe-lg-5">
                                    <div class="mb-3" style="max-width: 200px;">
                                        <label class="form-label" for="HireDate">Hire Date</label>
                                        <input type="text" class="js-flatpickr form-control js-flatpickr-enabled flatpickr-input active" id="HireDate" name="HireDate" placeholder="d/m/Y" value="{{ old('HireDate') ?? ($employee->HireDate != null ? date('d/m/Y', strtotime($employee->HireDate)) : '') }}" readonly="readonly">
                                    </div>
                                    <div class="mb-3" style="max-width: 200px;">
                                        <label class="form-label" for="ActiveDate">Active Date</label>
                                        <input type="text" class="js-flatpickr form-control js-flatpickr-enabled flatpickr-input active" id="ActiveDate" name="ActiveDate" placeholder="d/m/Y" value="{{ old('ActiveDate') ?? ($employee->ActiveDate != null ? date('d/m/Y', strtotime($employee->ActiveDate)) : '') }}" readonly="readonly">
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
                                </div>
                                <div class="col-lg-6 col-12 ps-lg-5">
                                    <div class="mb-3 loading-employee">
                                        <label class="form-label">Reference</label>
                                        <select class="form-select" disabled>
                                            <option id="loading-text">Loading Reference.....</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 employee-container">
                                        <label class="form-label">Reference</label>
                                        <select class="form-select form-select2" name="ReferenceID" id="ReferenceID">
                                            <option value="">-</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 loading-employee">
                                        <label class="form-label">Supervisor</label>
                                        <select class="form-select" disabled>
                                            <option id="loading-text">Loading Supervisors.....</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 employee-container">
                                        <label class="form-label">Supervisor</label>
                                        <select class="form-select form-select2" name="SupervisorID" id="SupervisorID">
                                            <option value="">-</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Position</label>
                                        <input type="text" name="Position" value="{{ old('Position') ?? $employee->Position }}" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="block block-rounded">
                        <div class="block-content">
                            <div class="row mt-3">
                                <div class="col-lg-6 col-12">
                                    <div class="mb-3" style="max-width: 200px;">
                                        <label class="form-label">Bank ID</label>
                                        <input type="text" name="BankID" value="{{ old('BankID') ?? $employee->BankID }}" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Bank Name</label>
                                        <input type="text" name="BankName" value="{{ old('BankName') ?? $employee->BankName }}" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Bank Account No.</label>
                                        <input type="text" name="BankAccountNo" value="{{ old('BankAccountNo') ?? $employee->BankAccountNo }}" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Bank Account Owner</label>
                                        <input type="text" name="BankAccountOwner" value="{{ old('BankAccountOwner') ?? $employee->BankAccountOwner }}" class="form-control">
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
    <link rel="stylesheet" href="{{ asset('js/plugins/flatpickr/flatpickr.min.css') }}">
@endsection

@section('scripts')
    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>

    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('js/plugins/flatpickr/flatpickr.min.js') }}"></script>

    <!-- Page JS Code -->
    <script>
        $(function() {
            $('.form-select2').select2({
                theme: 'bootstrap-5'
            });

            $('#division-container').hide();
            $('#country-container').hide();
            $('.employee-container').hide();

            $('#automatic').on('change', function () {
                if ($('#automatic').is(':checked')) {
                    $('#EmployeeID').attr('disabled', true);
                } else {
                    $('#EmployeeID').removeAttr('disabled');
                }
            });

            $('.js-flatpickr').flatpickr({
                dateFormat: "d/m/Y",
            });
            $('.js-flatpickr:visible').on('focus', function () {
                $(this).blur()
            });
            $('.js-flatpickr:visible').prop('readonly', false);


            // LOAD DIVISION

            $.ajax({
                url: '{!! route('misc.division') !!}',
                type: 'GET',
            }).done(function (data)  {
                if (data.status == 'success'){
                    var options = '';
                    var oldID = '{{ old("DivisionID") ?? $employee->DivisionID }}';
                    for(var i = 0; i < data.data.length; i++){
                        options += `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                    }

                    $('#DivisionID').append(options);

                    $('#loading-division').hide();
                    $('#division-container').show();
                }
                else {
                    $('#loading-text').html('Something went wrong!');
                }
            });



            // LOAD COUNTRY

            $.ajax({
                url: '{!! route('misc.country') !!}',
                type: 'GET',
            }).done(function (data)  {
                if (data.status == 'success'){
                    var options = '';
                    var oldID = '{{ old("CountryID") ?? $employee->CountryID }}';
                    for(var i = 0; i < data.data.length; i++){
                        options += `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                    }

                    $('#CountryID').append(options);

                    $('#loading-country').hide();
                    $('#country-container').show();
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
                    var refOpt = '';
                    var oldID = '{{ old("ReferenceID") ?? $employee->ReferenceID }}';
                    for(var i = 0; i < data.data.length; i++){
                        refOpt += `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                    }

                    $('#ReferenceID').append(refOpt);

                    var supOpt = '';
                    oldID = '{{ old("SupervisorID") ?? $employee->SupervisorID }}';
                    for(var i = 0; i < data.data.length; i++){
                        supOpt += `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                    }

                    $('#SupervisorID').append(supOpt);

                    $('.loading-employee').hide();
                    $('.employee-container').show();
                }
                else {
                    $('#loading-text').html('Something went wrong!');
                }
            });

        });
    </script>
@endsection
