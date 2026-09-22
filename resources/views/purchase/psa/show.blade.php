@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Purchase Settle Approval - {{ $psa->TransactionNo }}</title>
@endsection

@section('content')
    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <div class="d-flex flex-row align-items-center mb-5">
            <a href="{{ route('psa') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
            <h1 class="h3 fw-bold ms-4 mb-0">{{ $psa->TransactionNo }}</h1>
        </div>


        <div class="block block-rounded">
            <div class="block-content p-4">
                <div class="row">
                    <div class="col-lg-6 col-12 pe-lg-5">
                        <div class="mb-3">
                            <label class="form-label">User</label>
                            <input type="text" class="form-control"
                                value="{{ $psa->UserID . ($psa->user->UserName ? ' - ' . $psa->user->UserName : '') }}"
                                readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction No</label>
                            <input type="text" name="TransactionNo" id="TransactionNo" class="form-control"
                                value="{{ $psa->TransactionNo }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Settle No</label>
                            <input type="text" name="PRS_TransactionNo" id="PRS_TransactionNo" class="form-control"
                                value="{{ $psa->SettleNo }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="text" class="form-control"
                                value="{{ old('ApprovalDate') ?? ($psa->ApprovalDate != null ? date('d/m/Y', strtotime($psa->ApprovalDate)) : '') }}"
                                readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Supplier</label>
                            <input type="text" class="form-control"
                                value="{{ $psa->SupplierID . ($psa->supplier->SupplierName ? ' - ' . $psa->supplier->SupplierName : '') }}"
                                readonly>
                        </div>
                    </div>

                    <div class="col-lg-6 col-12 ps-lg-5">
                        <div class="col-6 mb-3" style="width: 100px">
                            <label class="form-label">Status</label>
                            <input type="text" class="form-control"
                                value="{{ $psa->Approved == 1 ? 'Approved' : 'Rejected' }}" readonly>
                        </div>
                        @if ($psa->ReasonsForReject)
                            <div class="mb-3">
                                <label class="form-label">Reasons For Rejection</label>
                                <textarea class="form-control" rows="3" readonly>{{ $psa->ReasonsForReject }}</textarea>
                            </div>
                        @endif
                        @if ($psa->ReasonsForApprove)
                            <div class="mb-3">
                                <label class="form-label">Reasons For Approve</label>
                                <textarea class="form-control" rows="3" readonly>{{ $psa->ReasonsForApprove }}</textarea>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>

        @include('purchase.psa.img_modal')
    </div>
    <!-- END Hero -->
@endsection

@section('styles')
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/flatpickr/flatpickr.min.css') }}">
    <style>
        th {
            white-space: nowrap;
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.0/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue-autonumeric@1.2.6/dist/vue-autonumeric.min.js"></script>

    <!-- Page JS Code -->
    <script>
        let app = new Vue({
            el: '#vue-container',
            data: {

            },
            methods: {

            },
            computed: {

            },
            watch: {},
            mounted() {}
        });
    </script>
@endsection
