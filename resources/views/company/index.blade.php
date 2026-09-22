@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Company Profile</title>
@endsection

@section('content')
    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">

                <h1 class="h3 fw-bold mb-5">
                    Company Profile
                </h1>

                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="row justify-content-end mb-2">
                    @if (auth()->user()->hasAnyPermission(['admin', 'company_profile.edit']))
                        <div class="col-auto">
                            <a href="{{ route('company.edit') }}" class="btn btn-primary fs-6 mb-3"><i
                                    class="fa fa-fw fa-edit"></i> Edit</a>
                        </div>
                    @endif
                </div>



                <div class="block block-rounded">
                    <div class="block-content">

                        <div class="row justify-content-between pe-lg-7">
                            <div class="col-auto d-flex flex-lg-row flex-column">
                                <div class="rounded rounded-4 overflow-hidden mb-3">
                                    @if ($company->Logo2 != null)
                                        <a href="{{ asset('storage/company/' . $company->Logo2) }}" target="_blank">
                                            <img src="{{ asset('storage/company/' . $company->Logo2) }}"
                                                style="object-fit: cover; width: 250px; height: 250px; cursor: pointer">
                                        </a>
                                    @else
                                        <img src="{{ asset('media/placeholder.jpeg') }}"
                                            style="object-fit: cover; width: 250px; height: 250px;">
                                    @endif

                                </div>

                                <div class="ms-lg-4">
                                    <div class="mb-3">
                                        <p class="m-0 fw-bold fs-6">Company Name</p>
                                        <p class="m-0 fs-6">
                                            {{ $company->CompanyName != null ? $company->CompanyName : '-' }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="m-0 fw-bold fs-6">Address</p>
                                        <p class="m-0 fs-6">{{ $company->Address != null ? $company->Address : '-' }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="m-0 fw-bold fs-6">City</p>
                                        <p class="m-0 fs-6">{{ $company->City != null ? $company->City : '-' }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="m-0 fw-bold fs-6">Country</p>
                                        <p class="m-0 fs-6">
                                            {{ $company->CountryID != null ? $company->country->CountryName : '-' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-auto col-12">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Postal Code</p>
                                    <p class="m-0 fs-6">{{ $company->PostalCode != null ? $company->PostalCode : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Phone</p>
                                    <p class="m-0 fs-6">{{ $company->Phone != null ? $company->Phone : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Email</p>
                                    <p class="m-0 fs-6">{{ $company->Email != null ? $company->Email : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Website</p>
                                    <p class="m-0 fs-6">{{ $company->Website != null ? $company->Website : '-' }}</p>
                                </div>
                            </div>

                            <div class="col-lg-auto col-12">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">NPWP</p>
                                    <p class="m-0 fs-6">{{ $company->NPWP != null ? $company->NPWP : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">SIUP No</p>
                                    <p class="m-0 fs-6">{{ $company->SIUP != null ? $company->SIUP : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">API No</p>
                                    <p class="m-0 fs-6">{{ $company->API != null ? $company->API : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Type Of Business</p>
                                    <p class="m-0 fs-6">
                                        {{ $company->TypeOfBusiness != null ? $company->TypeOfBusiness : '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="block block-rounded">
                    <div class="block-content">
                        <div class="row">
                            <div class="col-lg-6 col-12 pe-lg-5">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Contact Person</p>
                                    <p class="m-0 fs-6">
                                        {{ $company->ContactPerson != null ? $company->ContactPerson : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Register Name</p>
                                    <p class="m-0 fs-6">
                                        {{ $company->RegistrationCompanyName != null ? $company->RegistrationCompanyName : '-' }}
                                    </p>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12 ps-lg-5">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Currency</p>
                                    <p class="m-0 fs-6">{{ $company->CurrencyID != null ? $company->CurrencyID : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Freeze Unit Price and Discount in Selling Proccess</p>
                                    <p class="m-0 fs-6">{{ $company->FreezePrice != null ? $company->FreezePrice : '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- END Hero -->
@endsection

@section('styles')
    <style>
        input[readonly] {
            background: white !important;
        }
    </style>
@endsection

@section('scripts')
    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>

    <!-- Page JS Plugins -->

    <!-- Page JS Code -->
    <script>
        $(function() {

            @if (session()->has('type'))
                One.helpers('jq-notify', {
                    type: '{{ session('type') }}',
                    icon: '{{ session('icon') }}',
                    message: '{{ session('message') }}',
                });
            @endif
        });
    </script>
@endsection
