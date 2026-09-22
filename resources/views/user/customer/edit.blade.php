@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Edit Customer</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">

                <form autocomplete="off" method="post" enctype="multipart/form-data" action="{{ route('user.customer.update') }}">
                    @csrf

                    <div class="d-flex flex-row align-items-center mb-5">
                        <a href="{{ route('user.customer') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                        <h1 class="h3 fw-bold ms-4 mb-0">
                            Edit Customer
                        </h1>
                    </div>

                    <input type="hidden" name="id" value="{{ $customer->CustomerID }}">

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
                                    <label class="form-label">Customer ID <span class="text-danger">*</span></label>
                                    <input type="text" name="CustomerID" id="CustomerID" class="form-control" value="{{ old('CustomerID') ?? $customer->CustomerID }}" required readonly>
                                </div>
                                <div class="col-auto px-lg-0">
                                    <label class="form-label"></label>
                                    <div class="form-check ms-lg-4 pt-lg-2">
                                        <input class="form-check-input fs-6" type="checkbox" value="1" name="Active" id="Active" {{ old('Active') ? 'checked' : ($customer->Active == 1 ? 'checked' : '') }}>
                                        <label class="form-check-label fs-6" for="Active">
                                            Active
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-6 col-12 pe-lg-5">

                                    <div class="mb-3">
                                        <label class="form-label">Customer Name</label>
                                        <input type="text" name="CustomerName" value="{{ old('CustomerName') ?? $customer->CustomerName }}" class="form-control">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea name="Address" class="form-control" rows="3">{{ old('Address') ?? $customer->Address }}</textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">City</label>
                                        <input type="text" name="City" value="{{ old('City') ?? $customer->City }}" class="form-control">
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

                                    <div class="mb-3" id="loading-subdistrict">
                                        <label class="form-label">Sub District <span class="text-danger">*</span></label>
                                        <select class="form-select" disabled>
                                            <option id="loading-text">Loading Divisions.....</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="subdistrict-container">
                                        <label class="form-label">Sub District <span class="text-danger">*</span></label>
                                        <select class="form-select form-select2" name="SubDistrictID" id="SubDistrictID" required>
                                            <option value="">-</option>
                                        </select>
                                    </div>


                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="Phone" value="{{ old('Phone') ?? $customer->Phone }}" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="Email" value="{{ old('Email') ?? $customer->Email }}" class="form-control">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Contact Person</label>
                                        <input type="text" name="ContactPerson" value="{{ old('ContactPerson') ?? $customer->ContactPerson }}" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">NPWP</label>
                                        <input type="text" name="NPWP" value="{{ old('NPWP') ?? $customer->NPWP }}" class="form-control">
                                    </div>
                                    <div class="mb-3" style="max-width: 200px;">
                                        <label class="form-label" for="Birthday">Birthdate</label>
                                        <input type="text" class="js-flatpickr form-control js-flatpickr-enabled flatpickr-input active" id="Birthday" name="Birthday" placeholder="d/m/Y" value="{{ old('Birthday') ?? ($customer->Birthday != null ? date('d/m/Y', strtotime($customer->Birthday)) : '') }}" readonly="readonly">
                                    </div>
                                </div>

                                <div class="col-lg-6 col-12 ps-lg-5">
                                    <div class="mb-3">
                                        <label class="form-label">Term</label>
                                        <div class="d-flex flex-row align-items-center">
                                            <input type="text" name="Term" value="{{ old('Term') ?? $customer->Term }}" class="form-control number-input" style="width: 100px;">
                                            <p class="mb-0 ms-2">Day(s)</p>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">ETA</label>
                                        <div class="d-flex flex-row align-items-center">
                                            <input type="text" name="LimitDaysETA" value="{{ old('LimitDaysETA') ?? $customer->LimitDaysETA }}" class="form-control number-input" style="width: 100px;">
                                            <p class="mb-0 ms-2">Day(s)</p>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">ETD</label>
                                        <div class="d-flex flex-row align-items-center">
                                            <input type="text" name="LimitDaysETD" value="{{ old('LimitDaysETD') ?? $customer->LimitDaysETD }}" class="form-control number-input" style="width: 100px;">
                                            <p class="mb-0 ms-2">Day(s)</p>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Credit Limit</label>
                                        <div class="row align-items-center">
                                            <div class="col-lg-3 col-12 mb-3 mb-lg-0">
                                                <input type="text" name="CreditLimit" value="{{ old('CreditLimit') ?? $customer->CreditLimit }}" class="form-control number-input">
                                            </div>
                                            <div class="col-auto">
                                                <div class="form-check ms-lg-4">
                                                    <input class="form-check-input fs-6" type="checkbox" value="1" name="ChequeOutstandingRecognize" id="ChequeOutstandingRecognize" {{ old('ChequeOutstandingRecognize') ? 'checked' : ($customer->ChequeOutstandingRecognize == 1 ? 'checked' : '') }}>
                                                    <label class="form-check-label fs-6" for="ChequeOutstandingRecognize">
                                                        Cheque Outstanding Recognize
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Avoid Invoice Due</label>
                                        <input type="text" name="LockDueDateByDay" value="{{ old('LockDueDateByDay') ?? $customer->LockDueDateByDay }}" class="form-control number-input">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Invoice Outstanding Limit</label>
                                        <input type="text" name="InvoiceLimit" value="{{ old('InvoiceLimit') ?? $customer->InvoiceLimit }}" class="form-control number-input">
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

                                    <div class="mb-3" id="loading-salesman">
                                        <label class="form-label">Salesman</label>
                                        <select class="form-select" disabled>
                                            <option id="loading-text">Loading Salesmen.....</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="salesman-container">
                                        <label class="form-label">Salesman</label>
                                        <select class="form-select form-select2" name="SalesmanID" id="SalesmanID">
                                            <option value="">-</option>
                                        </select>
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
                                    <input type="text" name="IndividualID" id="IndividualID" class="form-control" value="{{ old('IndividualID') ?? $customer->IndividualID }}">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-lg-5 col-12">
                                    <label class="form-label">Individual Name</label>
                                    <input type="text" name="IndividualName" id="IndividualName" class="form-control" value="{{ old('IndividualName') ?? $customer->IndividualName }}">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-lg-5 col-12">
                                    <label class="form-label">NPWP Owner</label>
                                    <input type="text" name="NPWPOwner" id="NPWPOwner" class="form-control" value="{{ old('NPWPOwner') ?? $customer->NPWPOwner }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="block block-rounded">
                        <div class="block-content">
                            <h5>Image (Leave empty if you're not changing it)</h5>

                            <div class="mb-3">
                                <label class="form-label" for="PathImagePerson">Image Person</label>
                                <div class="row">
                                    <div class="col">
                                        <input class="form-control" type="file" id="PathImagePerson" name="PathImagePerson" accept="image/png, image/jpg, image/jpeg">
                                    </div>
                                    <div class="col-auto">
                                        @if($customer->PathImagePerson != null)
                                            <a href="{{ asset('storage/customer/person/'. $customer->PathImagePerson) }}" title="See Original" class="btn btn-alt-secondary" target="_blank"><i class="fa fa-fw fa-eye"></i></a>
                                        @else
                                            <button type="button" class="btn btn-alt-secondary" disabled><i class="fa fa-fw fa-eye"></i></button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="PathImageLocation">Image Location</label>
                                <div class="row">
                                    <div class="col">
                                        <input class="form-control" type="file" id="PathImageLocation" name="PathImageLocation" accept="image/png, image/jpg, image/jpeg">
                                    </div>
                                    <div class="col-auto">
                                        @if($customer->PathImageLocation != null)
                                            <a href="{{ asset('storage/customer/location/'. $customer->PathImageLocation) }}" title="See Original" class="btn btn-alt-secondary" target="_blank"><i class="fa fa-fw fa-eye"></i></a>
                                        @else
                                            <button type="button" class="btn btn-alt-secondary" disabled><i class="fa fa-fw fa-eye"></i></button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="PathImageId">Image ID</label>
                                <div class="row">
                                    <div class="col">
                                        <input class="form-control" type="file" id="PathImageId" name="PathImageId" accept="image/png, image/jpg, image/jpeg">
                                    </div>
                                    <div class="col-auto">
                                        @if($customer->PathImageId != null)
                                            <a href="{{ asset('storage/customer/id/'. $customer->PathImageId) }}" title="See Original" class="btn btn-alt-secondary" target="_blank"><i class="fa fa-fw fa-eye"></i></a>
                                        @else
                                            <button type="button" class="btn btn-alt-secondary" disabled><i class="fa fa-fw fa-eye"></i></button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="block block-rounded">
                        <div class="block-content">
                            <div id="vue-container">
                                <div class="row justify-content-between">
                                    <div class="col-auto">
                                        <h5>Shipment Address</h5>
                                    </div>
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-primary" @click="addShipment()"><i class="fa fa-fw fa-plus"></i> Add Shipment</button>
                                    </div>
                                </div>

                                <p class="m-0 p-4 text-center" v-if="shipments.length == 0">No Shipment Added Yet</p>

                                <div class="table-responsive">
                                    <table class="w-100 mb-3 table-shipment" v-if="shipments.length > 0">
                                        <thead>
                                        <tr>
                                            <th style="min-width: 300px;">Shipment  <span class="text-danger">*</span></th>
                                            <th style="min-width: 300px;">Address</th>
                                            <th style="width: 60px"></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr v-for="(shipment, index) in shipments">
                                            <td><input type="text" :name="'Shipment['+index+']'" class="form-control" v-model="shipment.shipment" required></td>
                                            <td><input type="text" :name="'ShipmentAddress['+index+']'" class="form-control" v-model="shipment.address"></td>
                                            <td class="text-center"><button type="button" class="btn btn-danger" @click="deleteShipment(index)"><i class="fa fa-fw fa-trash"></i></button></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>

{{--                                <div class="row" v-if="shipments.length > 0">--}}
{{--                                    <div class="col-4">--}}
{{--                                        <label class="form-label">Shipment <span class="text-danger">*</span></label>--}}
{{--                                    </div>--}}
{{--                                    <div class="col">--}}
{{--                                        <label class="form-label">Address</label>--}}
{{--                                    </div>--}}
{{--                                    <div class="col-auto">--}}
{{--                                        <button type="button" class="btn btn-danger invisible"><i class="fa fa-fw fa-trash"></i></button>--}}
{{--                                    </div>--}}
{{--                                </div>--}}

{{--                                <div class="row mb-3" v-for="(shipment, index) in shipments">--}}
{{--                                    <div class="col-4">--}}
{{--                                        <input type="text" :name="'Shipment['+index+']'" class="form-control" v-model="shipment.shipment" required>--}}
{{--                                    </div>--}}
{{--                                    <div class="col">--}}
{{--                                        <input type="text" :name="'ShipmentAddress['+index+']'" class="form-control" v-model="shipment.address">--}}
{{--                                    </div>--}}
{{--                                    <div class="col-auto">--}}
{{--                                        <button type="button" class="btn btn-danger" @click="deleteShipment(index)"><i class="fa fa-fw fa-trash"></i></button>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
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
    <style>
        .table-shipment {
            border-collapse: separate;
            border-spacing: 5px;
        }
    </style>
@endsection

@section('scripts')
    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>

    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('js/plugins/flatpickr/flatpickr.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.5.4"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.13/dist/vue.js"></script>

    <!-- Page JS Code -->
    <script>
        $(function() {
            $('.form-select2').select2({
                theme: 'bootstrap-5'
            });

            $('#division-container').hide();
            $('#country-container').hide();
            $('#subdisrict-container').hide();
            $('#salesman-container').hide();

            $('#automatic').on('change', function () {
                if ($('#automatic').is(':checked')) {
                    $('#CustomerID').attr('disabled', true);
                } else {
                    $('#CustomerID').removeAttr('disabled');
                }
            });

            $('.js-flatpickr').flatpickr({
                dateFormat: "d/m/Y",
            });
            $('.js-flatpickr:visible').on('focus', function () {
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



            // LOAD DIVISION

            $.ajax({
                url: '{!! route('misc.division') !!}',
                type: 'GET',
            }).done(function (data)  {
                if (data.status == 'success'){
                    var options = '';
                    var oldID = '{{ old("DivisionID") ?? $customer->DivisionID }}';
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
                    var oldID = '{{ old("CountryID") ?? $customer->CountryID }}';
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


            // LOAD SUBDISTRICT

            $.ajax({
                url: '{!! route('misc.subdistrict') !!}',
                type: 'GET',
            }).done(function (data)  {
                if (data.status == 'success'){
                    var options = '';
                    var oldID = '{{ old("SubDistrictID") ?? $customer->SubDistrictID }}';
                    for(var i = 0; i < data.data.length; i++){
                        options += `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                    }

                    $('#SubDistrictID').append(options);

                    $('#loading-subdistrict').hide();
                    $('#subdistrict-container').show();
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
                    var oldID = '{{ old("SalesmanID") ?? $customer->SalesmanID }}';
                    for(var i = 0; i < data.data.length; i++){
                        options += `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                    }

                    $('#SalesmanID').append(options);

                    $('#loading-salesman').hide();
                    $('#salesman-container').show();
                }
                else {
                    $('#loading-text').html('Something went wrong!');
                }
            });


            let app = new Vue({
                el: '#vue-container',
                data: {
                    shipments: [
                        @if(old('Shipment'))
                        @foreach(old('Shipment') as $i => $shipment)
                        {
                            shipment: '{{ old('Shipment.'.$i) }}',
                            address: '{{ old('ShipmentAddress.'.$i) }}'
                        },
                        @endforeach
                        @else
                        @foreach($customer->shipments as $i => $shipment)
                        {
                            shipment: '{{ $shipment->Shipment }}',
                            address: '{{ $shipment->Address }}'
                        },
                        @endforeach
                        @endif
                    ]
                },
                methods: {
                    addShipment(){
                        this.shipments.push({
                            shipment: '',
                            address: ''
                        });
                    },
                    deleteShipment(index){
                        this.shipments.splice(index, 1);
                    },
                }
            });
        });
    </script>
@endsection
