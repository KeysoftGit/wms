@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Employee - {{ $employee->EmployeeID }}</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">

                <div class="d-flex flex-row align-items-center mb-5">
                    <a href="{{ route('user.employee') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                    <h1 class="h3 fw-bold ms-4 mb-0">
                        {{ $employee->EmployeeID }} {{ $employee->FirstName != null ? '- ' . $employee->FirstName . ' ' . $employee->LastName : '' }}
                    </h1>
                </div>

                @if(auth()->user()->hasAnyPermission(['admin', 'employee.edit']))
                    <a href="{{ route('user.employee.edit', $employee->id) }}" class="btn btn-primary fs-6 mb-3"><i class="fa fa-fw fa-edit"></i> Edit</a>
                @endif

                <div class="block block-rounded">
                    <div class="block-content p-4">

                        <div class="row justify-content-between pe-7">
                            <div class="col-lg-auto col-12 row align-items-center">
                                <div class="col-lg-auto col-12 rounded rounded-4 overflow-hidden mb-3 mb-lg-0">
                                    @if($employee->Photo2 != null)
                                    <a href="{{ asset('storage/employee/'.$employee->Photo2) }}" target="_blank">
                                        <img src="{{ asset('storage/employee/'.$employee->Photo2) }}"
                                             style="object-fit: cover; width: 200px; height: 200px; cursor: pointer">
                                    </a>
                                    @else
                                        <img src="{{ asset('media/avatars/avatar0.jpg') }}"
                                             style="object-fit: cover; width: 200px; height: 200px;">
                                    @endif

                                </div>
                                <div class="col-lg-auto col-12 ms-lg-4">
                                    <div class="mb-3">
                                        <p class="m-0 fw-bold fs-6">Name</p>
                                        <p class="m-0 fs-6">{{ $employee->FirstName != null ? $employee->FirstName . ' ' . $employee->LastName : '-' }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="m-0 fw-bold fs-6">Gender</p>
                                        <p class="m-0 fs-6">{{ $employee->Gender != null ? ($employee->Gender == 'M' ? 'Male' : 'Female') : '-' }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="m-0 fw-bold fs-6">Birthdate</p>
                                        <p class="m-0 fs-6">{{ $employee->BirthDate != null ? date('d M Y', strtotime($employee->BirthDate)) : '-' }}</p>
                                    </div>
                                </div>
                            </div>


                            <div class="col-lg-auto col-12">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Phone 1</p>
                                    <p class="m-0 fs-6">{{ $employee->Phone1 != null ? $employee->Phone1 : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Phone 2</p>
                                    <p class="m-0 fs-6">{{ $employee->Phone2 != null ? $employee->Phone2 : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Email</p>
                                    <p class="m-0 fs-6">{{ $employee->Email != null ? $employee->Email : '-' }}</p>
                                </div>
                            </div>

                            <div class="col-lg-auto col-12">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">ID No</p>
                                    <p class="m-0 fs-6">{{ $employee->IDNumber != null ? $employee->IDNumber : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">NPWP</p>
                                    <p class="m-0 fs-6">{{ $employee->NPWP != null ? $employee->NPWP : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Marital Status</p>
                                    <p class="m-0 fs-6">{{ $employee->MatrialStatus != null ? $employee->MatrialStatus : '-' }}</p>
                                </div>

                            </div>

                            <div class="col-lg-auto col-12">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">City</p>
                                    <p class="m-0 fs-6">{{ $employee->City != null ? $employee->City : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Country</p>
                                    <p class="m-0 fs-6">{{ $employee->CountryID != null ? $employee->country->CountryName : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Address</p>
                                    <p class="m-0 fs-6">{{ $employee->Address != null ? $employee->Address : '-' }}</p>
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
                                    <p class="m-0 fw-bold fs-6">Hire Date</p>
                                    <p class="m-0 fs-6">{{ $employee->HireDate != null ? date('d M Y', strtotime($employee->HireDate)) : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Active Date</p>
                                    <p class="m-0 fs-6">{{ $employee->ActiveDate != null ? date('d M Y', strtotime($employee->ActiveDate)) : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Division</p>
                                    <p class="m-0 fs-6">{{ $employee->DivisionID != null ? $employee->division->DivisionName : '-' }}</p>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Reference</p>
                                    <p class="m-0 fs-6">{{ $employee->ReferenceID != null ? $employee->reference->EmployeeID . ($employee->reference->FirstName != null ? ' - ' . $employee->reference->FirstName . ' ' . $employee->reference->LastName : '') : '-' }}</p>
                                </div><div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Supervisor</p>
                                    <p class="m-0 fs-6">{{ $employee->SupervisorID != null ? $employee->supervisor->EmployeeID . ($employee->supervisor->FirstName != null ? ' - ' . $employee->supervisor->FirstName . ' ' . $employee->supervisor->LastName : '') : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Position</p>
                                    <p class="m-0 fs-6">{{ $employee->Position != null ? $employee->Position : '-' }}</p>
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
                                    <p class="m-0 fs-6">{{ $employee->BankID != null ? $employee->BankID : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Bank Name</p>
                                    <p class="m-0 fs-6">{{ $employee->BankName != null ? $employee->BankName : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Bank Account No</p>
                                    <p class="m-0 fs-6">{{ $employee->BankAccountNo != null ? $employee->BankAccountNo : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Bank Account Owner</p>
                                    <p class="m-0 fs-6">{{ $employee->BankAccountOwner != null ? $employee->BankAccountOwner : '-' }}</p>
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
