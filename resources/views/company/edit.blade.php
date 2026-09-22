@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Edit Company Profile</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2">
            <div class="flex-grow-1 mb-1 mb-md-0">

                <form autocomplete="off" method="post" enctype="multipart/form-data" action="{{ route('company.update') }}">
                    @csrf

                    <div class="d-flex flex-row align-items-center mb-5">
                        <a href="{{ route('company') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                        <h1 class="h3 fw-bold ms-4 mb-0">
                            Edit Company Profile
                        </h1>
                    </div>

                    <input type="hidden" name="id" value="{{ $company->CompanyID }}">

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
                                <div class="col-lg-6 col-12 pe-lg-5">

                                    <div class="mb-3">
                                        <label class="form-label" for="PathImagePerson">Company Logo</label>
                                        <div class="row">
                                            <div class="col">
                                                <input class="form-control" type="file" id="Logo" name="Logo" accept="image/png, image/jpg, image/jpeg">
                                            </div>
                                            <div class="col-auto">
                                                @if($company->Logo2 != null)
                                                    <a href="{{ asset('storage/company/'. $company->Logo2) }}" title="See Original" class="btn btn-alt-secondary" target="_blank"><i class="fa fa-fw fa-eye"></i></a>
                                                @else
                                                    <button type="button" class="btn btn-alt-secondary" disabled><i class="fa fa-fw fa-eye"></i></button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Company Name</label>
                                        <input type="text" name="CompanyName" value="{{ old('CompanyName') ?? $company->CompanyName }}" class="form-control">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea name="Address" class="form-control" rows="3">{{ old('Address') ?? $company->Address }}</textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">City</label>
                                        <input type="text" name="City" value="{{ old('City') ?? $company->City }}" class="form-control">
                                    </div>

                                    <div class="mb-3" id="loading-country">
                                        <label class="form-label">Country <span class="text-danger">*</span></label>
                                        <select class="form-select" disabled>
                                            <option id="loading-text">Loading Countries.....</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="country-container">
                                        <label class="form-label">Country <span class="text-danger">*</span></label>
                                        <select class="form-select form-select2" name="CountryID" id="CountryID" required>
                                            <option value="">-</option>
                                        </select>
                                    </div>


                                    <div class="mb-3">
                                        <label class="form-label">Postal Code</label>
                                        <input type="text" name="PostalCode" value="{{ old('PostalCode') ?? $company->PostalCode }}" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="Phone" value="{{ old('Phone') ?? $company->Phone }}" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="Email" value="{{ old('Email') ?? $company->Email }}" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Website</label>
                                        <input type="texxt" name="Website" value="{{ old('Website') ?? $company->Website }}" class="form-control">
                                    </div>
                                </div>

                                <div class="col-lg-6 col-12 ps-lg-5">
                                    <div class="mb-3">
                                        <label class="form-label">NPWP</label>
                                        <input type="text" name="NPWP" value="{{ old('NPWP') ?? $company->NPWP }}" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">SIUP No</label>
                                        <input type="text" name="SIUP" value="{{ old('SIUP') ?? $company->SIUP }}" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">API No</label>
                                        <input type="text" name="API" value="{{ old('API') ?? $company->API }}" class="form-control">
                                    </div>

                                    <div class="mb-3" id="loading-currency">
                                        <label class="form-label">Currency</label>
                                        <select class="form-select" disabled>
                                            <option id="loading-text">Loading Currencies.....</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="currency-container">
                                        <label class="form-label">Currency</label>
                                        <select class="form-select form-select2" name="CurrencyID" id="CurrencyID" required>
                                            <option value="">-</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Type Of Business</label>
                                        <input type="text" name="TypeOfBusiness" value="{{ old('TypeOfBusiness') ?? $company->TypeOfBusiness }}" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Contact Person</label>
                                        <input type="text" name="ContactPerson" value="{{ old('ContactPerson') ?? $company->ContactPerson }}" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Register Name</label>
                                        <input type="text" name="RegistrationCompanyName" value="{{ old('RegistrationCompanyName') ?? $company->RegistrationCompanyName }}" class="form-control">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Freeze Unit Price and Discount in Selling Proccess</label>
                                        <div class="row">
                                            <div class="col">
                                                <div class="space-x-2">
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" id="FREEZE" name="FreezePrice" value="FREEZE" {{ old('FreezePrice') ? (old('FreezePrice') == '1' ? 'checked' : '') : ($company->FreezePrice == 'FREEZE' ? 'checked' : 'checked') }}>
                                                        <label class="form-check-label" for="FREEZE">FREEZE</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" id="NOT_FREEZE" name="FreezePrice" value="NOT_FREEZE" {{ old('FreezePrice') ? (old('FreezePrice') == 'NOT_FREEZE' ? 'checked' : '') : ($company->FreezePrice == 'NOT_FREEZE' ? 'checked' : '') }}>
                                                        <label class="form-check-label" for="NOT_FREEZE">NOT_FREEZE</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" id="FREEZE_UNIT_PRICE_ONLY" name="FreezePrice" value="FREEZE_UNIT_PRICE_ONLY" {{ old('FreezePrice') ? (old('FreezePrice') == 'FREEZE_UNIT_PRICE_ONLY' ? 'checked' : '') : ($company->FreezePrice == 'FREEZE_UNIT_PRICE_ONLY' ? 'checked' : '') }}>
                                                        <label class="form-check-label" for="FREEZE_UNIT_PRICE_ONLY">FREEZE_UNIT_PRICE_ONLY</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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

    <!-- Page JS Code -->
    <script>
        $(function() {
            $('.form-select2').select2({
                theme: 'bootstrap-5'
            });

            $('#country-container').hide();
            $('#currency-container').hide();



            // LOAD CURRENCY

            $.ajax({
                url: '{!! route('misc.currency') !!}',
                type: 'GET',
            }).done(function (data)  {
                if (data.status == 'success'){
                    var options = '';
                    var oldID = '{{ old("CurrencyID") ?? $company->CurrencyID }}';
                    for(var i = 0; i < data.data.length; i++){
                        options += `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                    }

                    $('#CurrencyID').append(options);

                    $('#loading-currency').hide();
                    $('#currency-container').show();
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
                    var oldID = '{{ old("CountryID") ?? $company->CountryID }}';
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
        });
    </script>
@endsection
