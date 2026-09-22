@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Supplier - {{ $supplier->SupplierID }}</title>
@endsection

@section('content')
    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">

                <div class="d-flex flex-row align-items-center mb-5">
                    <a href="{{ route('user.supplier') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                    <h1 class="h3 fw-bold ms-4 mb-0">
                        {{ $supplier->SupplierID }}
                        {{ $supplier->SupplierName != null ? '- ' . $supplier->SupplierName : '' }}
                    </h1>
                </div>

                @if (auth()->user()->hasAnyPermission(['admin', 'supplier.edit']))
                    <a href="{{ route('user.supplier.edit', $supplier->id) }}" class="btn btn-primary fs-6 mb-3"><i
                            class="fa fa-fw fa-edit"></i> Edit</a>
                @endif

                <div class="block block-rounded">
                    <div class="block-content p-4">

                        <div class="row justify-content-between pe-7">
                            <div class="col-lg-auto col-12">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Name</p>
                                    <p class="m-0 fs-6">
                                        {{ $supplier->SupplierName != null ? $supplier->SupplierName : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Address</p>
                                    <p class="m-0 fs-6">{{ $supplier->Address != null ? $supplier->Address : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">City</p>
                                    <p class="m-0 fs-6">{{ $supplier->City != null ? $supplier->City : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Country</p>
                                    <p class="m-0 fs-6">
                                        {{ $supplier->CountryID != null ? $supplier->country->CountryName : '-' }}</p>
                                </div>
                            </div>


                            <div class="col-lg-auto col-12">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Phone</p>
                                    <p class="m-0 fs-6">{{ $supplier->Phone != null ? $supplier->Phone : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Email</p>
                                    <p class="m-0 fs-6">{{ $supplier->Email != null ? $supplier->Email : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Website</p>
                                    <p class="m-0 fs-6">{{ $supplier->Website != null ? $supplier->Website : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Contact Person</p>
                                    <p class="m-0 fs-6">
                                        {{ $supplier->ContactPerson != null ? $supplier->ContactPerson : '-' }}</p>
                                </div>
                            </div>

                            <div class="col-lg-auto col-12">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">NPWP</p>
                                    <p class="m-0 fs-6">{{ $supplier->NPWP != null ? $supplier->NPWP : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Term</p>
                                    <p class="m-0 fs-6">{{ $supplier->Term != null ? $supplier->Term : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">ETA</p>
                                    <p class="m-0 fs-6">
                                        {{ $supplier->LimitDaysETA != null ? $supplier->LimitDaysETA : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">ETD</p>
                                    <p class="m-0 fs-6">
                                        {{ $supplier->LimitDaysETD != null ? $supplier->LimitDaysETD : '-' }}</p>
                                </div>
                            </div>

                            <div class="col-lg-auto col-12">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Division</p>
                                    <p class="m-0 fs-6">
                                        {{ $supplier->DivisionID != null ? $supplier->division->DivisionName : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Available As Forwarder</p>
                                    <p class="m-0 fs-6">{{ $supplier->Forwarder != 0 ? 'Yes' : 'No' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Account Payable Limit</p>
                                    <p class="m-0 fs-6">
                                        {{ $supplier->AccountPayableLimit != null ? number_format($supplier->AccountPayableLimit, 0, ',', '.') : '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="block block-rounded">
                    <div class="block-content">
                        <div class="row">
                            <div class="col-lg-6 col-12">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Individual ID</p>
                                    <p class="m-0 fs-6">
                                        {{ $supplier->IndividualID != null ? $supplier->IndividualID : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Individual Name</p>
                                    <p class="m-0 fs-6">
                                        {{ $supplier->IndividualName != null ? $supplier->IndividualName : '-' }}</p>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">NPWP Owner</p>
                                    <p class="m-0 fs-6">{{ $supplier->NPWPOwner != null ? $supplier->NPWPOwner : '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="block block-rounded">
                    <div class="block-content">
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Bank ID</p>
                                    <p class="m-0 fs-6">{{ $supplier->BankID != null ? $supplier->BankID : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Bank Name</p>
                                    <p class="m-0 fs-6">{{ $supplier->BankName != null ? $supplier->BankName : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Bank Account No</p>
                                    <p class="m-0 fs-6">
                                        {{ $supplier->BankAccountNo != null ? $supplier->BankAccountNo : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Bank Account Owner</p>
                                    <p class="m-0 fs-6">
                                        {{ $supplier->BankAccountOwner != null ? $supplier->BankAccountOwner : '-' }}</p>
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
@endsection

@section('scripts')
    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>

    <!-- Page JS Plugins -->

    <!-- Page JS Code -->
    <script>
        $(function() {


        });
    </script>
@endsection
