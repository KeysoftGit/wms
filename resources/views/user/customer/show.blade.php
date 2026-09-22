@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Customer - {{ $customer->CustomerID }}</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-center text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">

                <div class="d-flex flex-row align-items-center mb-5">
                    <a href="{{ route('user.customer') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                    <h1 class="h3 fw-bold ms-4 mb-0">
                        {{ $customer->CustomerID }} {{ $customer->CustomerName != null ? '- ' . $customer->CustomerName : '' }}
                    </h1>
                </div>

                @if(auth()->user()->hasAnyPermission(['admin', 'customer.edit']))
                    <a href="{{ route('user.customer.edit', $customer->id) }}" class="btn btn-primary fs-6 mb-3"><i class="fa fa-fw fa-edit"></i> Edit</a>
                @endif

                <div class="block block-rounded">
                    <div class="block-content">

                        <div class="row justify-content-between pe-7">
                            <div class="col-auto">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Name</p>
                                    <p class="m-0 fs-6">{{ $customer->CustomerName != null ? $customer->CustomerName : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Address</p>
                                    <p class="m-0 fs-6">{{ $customer->Address != null ? $customer->Address : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">City</p>
                                    <p class="m-0 fs-6">{{ $customer->City != null ? $customer->City : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Country</p>
                                    <p class="m-0 fs-6">{{ $customer->CountryID != null ? $customer->country->CountryName : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Sub District</p>
                                    <p class="m-0 fs-6">{{ $customer->SubDistrictID != null ? $customer->subdistrict->SubDistrictID . ($customer->subdistrict->SubDistrictName != null ? ' - ' . $customer->subdistrict->SubDistrictName : '') : '-' }}</p>
                                </div>
                            </div>


                            <div class="col-auto">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Phone</p>
                                    <p class="m-0 fs-6">{{ $customer->Phone != null ? $customer->Phone : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Email</p>
                                    <p class="m-0 fs-6">{{ $customer->Email != null ? $customer->Email : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Contact Person</p>
                                    <p class="m-0 fs-6">{{ $customer->ContactPerson != null ? $customer->ContactPerson : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">NPWP</p>
                                    <p class="m-0 fs-6">{{ $customer->NPWP != null ? $customer->NPWP : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Birthdate</p>
                                    <p class="m-0 fs-6">{{ $customer->Birthday != null ? date('d M Y', strtotime($customer->Birthday)) : '-' }}</p>
                                </div>
                            </div>

                            <div class="col-auto">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Term</p>
                                    <p class="m-0 fs-6">{{ $customer->Term != null ? $customer->Term : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">ETA</p>
                                    <p class="m-0 fs-6">{{ $customer->LimitDaysETA != null ? $customer->LimitDaysETA : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">ETD</p>
                                    <p class="m-0 fs-6">{{ $customer->LimitDaysETD != null ? $customer->LimitDaysETD : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Credit Limit</p>
                                    <p class="m-0 fs-6">{{ $customer->CreditLimit != null ? number_format($customer->CreditLimit, 0, ",", ".") : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Cheque Outstanding Recognize</p>
                                    <p class="m-0 fs-6">{{ $customer->ChequeOutstandingRecognize != 0 ? 'Yes' : 'No' }}</p>
                                </div>
                            </div>

                            <div class="col-auto">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Avoid Invoice Due</p>
                                    <p class="m-0 fs-6">{{ $customer->LockDueDateByDay != null ? $customer->LockDueDateByDay : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Invoice Outstanding Limit</p>
                                    <p class="m-0 fs-6">{{ $customer->InvoiceLimit != null ? number_format($customer->InvoiceLimit, 0, ",", ".") : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Division</p>
                                    <p class="m-0 fs-6">{{ $customer->DivisionID != null ? $customer->division->DivisionName : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Salesman</p>
                                    <p class="m-0 fs-6">{{ $customer->SalesmanID != null ? $customer->salesman->EmployeeID . ($customer->salesman->FirstName != null ? ' - ' . $customer->salesman->FirstName . ' ' . $customer->salesman->LastName : '') : '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="block block-rounded">
                    <div class="block-content">
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Individual ID</p>
                                    <p class="m-0 fs-6">{{ $customer->IndividualID != null ? $customer->IndividualID : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Individual Name</p>
                                    <p class="m-0 fs-6">{{ $customer->IndividualName != null ? $customer->IndividualName : '-' }}</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">NPWP Owner</p>
                                    <p class="m-0 fs-6">{{ $customer->NPWPOwner != null ? $customer->NPWPOwner : '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="block block-rounded">
                    <div class="block-content">
                        <div class="row mb-3">
                            <div class="col-4">
                                <p class="fw-bold fs-6 mb-3">Image Location</p>
                                <div class="rounded rounded-3 overflow-hidden">
                                    @if($customer->PathImageLocation != null)
                                        <a href="{{ asset('storage/customer/location/'.$customer->PathImageLocation) }}" target="_blank">
                                            <img src="{{ asset('storage/customer/location/'.$customer->PathImageLocation) }}"
                                                 style="object-fit: cover; width: 100%; height: 200px; cursor: pointer">
                                        </a>
                                    @else
                                        <img src="{{ asset('media/avatars/avatar0.jpg') }}"
                                             style="object-fit: cover; width: 100%; height: 200px;">
                                    @endif
                                </div>
                            </div>
                            <div class="col-4">
                                <p class="fw-bold fs-6 mb-3">Image Person</p>
                                <div class="rounded rounded-3 overflow-hidden">
                                    @if($customer->PathImagePerson != null)
                                        <a href="{{ asset('storage/customer/person/'.$customer->PathImagePerson) }}" target="_blank">
                                            <img src="{{ asset('storage/customer/person/'.$customer->PathImagePerson) }}"
                                                 style="object-fit: cover; width: 100%; height: 200px; cursor: pointer">
                                        </a>
                                    @else
                                        <img src="{{ asset('media/avatars/avatar0.jpg') }}"
                                             style="object-fit: cover; width: 100%; height: 200px;">
                                    @endif
                                </div>
                            </div>
                            <div class="col-4">
                                <p class="fw-bold fs-6 mb-3">Image ID</p>
                                <div class="rounded rounded-3 overflow-hidden">
                                    @if($customer->PathImageId != null)
                                        <a href="{{ asset('storage/customer/id/'.$customer->PathImageId) }}" target="_blank">
                                            <img src="{{ asset('storage/customer/id/'.$customer->PathImageId) }}"
                                                 style="object-fit: cover; width: 100%; height: 200px; cursor: pointer">
                                        </a>
                                    @else
                                        <img src="{{ asset('media/avatars/avatar0.jpg') }}"
                                             style="object-fit: cover; width: 100%; height: 200px;">
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="block block-rounded">
                    <div class="block-content">
                        <h5>Shipment Address</h5>

                        <table class="table table-bordered table-striped table-vcenter mb-3">
                            <thead>
                                <tr>
                                    <th>Shipment</th>
                                    <th>Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(count($customer->shipments) > 0)
                                    @foreach($customer->shipments as $shipment)
                                        <tr>
                                            <td>{{ $shipment->Shipment }}</td>
                                            <td>{{ $shipment->Address }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="2" class="text-center">No Shipment Address</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
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
