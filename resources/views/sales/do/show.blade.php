@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Delivery Order - {{ $do->TransactionNo }}</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <div class="d-flex flex-row align-items-center mb-5">
            <a href="{{ route('do') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
            <h1 class="h3 fw-bold ms-4 mb-0">
                {{ $do->TransactionNo }}
            </h1>
        </div>

        @if(count($errors->all()) > 0)
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <p class="m-0 fs-6">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="row justify-content-between mb-3 align-items-end">
            <div class="col-auto d-flex flex-row">
                @if(!request()->has('br') && $do->Editable == 1 && auth()->user()->hasAnyPermission(['admin', 'do.edit']))
                    <a href="{{ route('do.edit', $do->id) }}" class="btn btn-primary fs-6"><i class="fa fa-fw fa-edit"></i> Edit</a>
                @endif
            </div>

            <div class="col-auto">
                <form autocomplete="off" action="{{ config('app.report_url') }}/print/index">
                    <input type="hidden" name="transactionCode" value="{{ $do->TransactionNo }}">
                    <input type="hidden" name="dbGuid" value="{{ session('guid') }}">
                    <div class="d-flex flex-row align-items-end">
                        <div>
                            <label>Print Type</label>
                            <select class="form-select" name="code" style="width: 100px;">
                                @foreach($options as $option)
                                    <option value="{{ $option->Code }}">{{ $option->Type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-success ms-2"><i class="fa fa-fw fa-print me-2"></i>Print</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-content pb-3">
                <div class="row align-items-center mb-3">
                    <div class="col-lg-2 col-12 pe-lg-0">
                        <label class="form-label">Transaction No <span class="text-danger">*</span></label>
                        <input type="text" name="TransactionNo" id="TransactionNo" class="form-control" value="{{ $do->TransactionNo }}" readonly>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 col-12 pe-lg-5">
                        <div class="row mb-3">
                            <div class="col-lg-4 col-12 mb-lg-0 mb-3">
                                <label class="form-label">Transaction Date <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="TransactionDate" name="TransactionDate" placeholder="d/m/Y" value="{{ old('TransactionDate') ?? ($do->TransactionDate != null ? date('d/m/Y', strtotime($do->TransactionDate)) : '') }}" readonly>
                            </div>
                            <div class="col-lg-4 col-12 mb-lg-0 mb-3">
                                <label class="form-label">Expired <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="ExpiredDate" name="ExpiredDate" placeholder="d/m/Y" value="{{ ($do->ExpiredDate != null ? date('d/m/Y', strtotime($do->ExpiredDate)) : '') }}" readonly>
                            </div>
                            <div class="col-lg-4 col-12">
                                <label class="form-label">ETA <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="ETA" name="ETA" placeholder="d/m/Y" value="{{ ($do->ETA != null ? date('d/m/Y', strtotime($do->ETA)) : '') }}" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Customer <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" value="{{ $so->CustomerID . ($so->customer->CustomerName ? ' - ' . $so->customer->CustomerName : '') }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sales Order <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" value="{{ $do->ReffNumber }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Vehicle</label>
                            <input type="text" class="form-control" value="{{ $do->VehicleID ? $do->VehicleID . ($do->vehicle?->VehicleName ? ' - ' . $do->vehicle->VehicleName : '') : '' }}" readonly>
                        </div>
                    </div>
                    <div class="col-lg-6 col-12 ps-lg-5">
                        <div class="mb-4">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea name="ShipmentAddress" id="ShipmentAddress" class="form-control" rows="4" readonly>{{ $do->ShipmentAddress }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Driver</label>
                            <input type="text" class="form-control" value="{{ $do->DriverID ? $do->DriverID . ($do->driver?->FirstName != null ? ' - ' . $do->driver->FirstName . ' ' . $do->driver->LastName : '') : '' }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-content">
                <div class="d-flex flex-row justify-content-between align-items-center mb-3">
                    <h5 class="m-0">Detail</h5>
                </div>

                <div class="table-responsive w-100" style="overflow-x: scroll">
                    <table class="table table-bordered w-100" style="table-layout: fixed; overflow-x: scroll">
                        <thead>
                        <tr>
                            <th style="width: 150px;">Part ID</th>
                            <th style="width: 200px;">Part Name</th>
                            <th style="width: 100px;">UnitID</th>
                            <th style="width: 100px;">UnitID1</th>
                            <th style="width: 200px;">Qty</th>
                            <th style="width: 200px;">Remaining Qty</th>
                            <th style="width: 200px;">Deliver Qty</th>
                            <th style="width: 200px;">Division</th>
                            <th style="width: 200px;">Warehouse</th>
                            <th style="width: 160px;">Batch No</th>
                            <th v-for="(n, i) in revCount" style="width: 250px;">
                                <span v-if="revData[i].name != null && revData[i].name != ''">@{{ revData[i].name }}</span>
                                <span v-else>Rev @{{ n }}</span>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-if="details.length == 0">
                            <td colspan="11" class="text-center p-3">No Details Added Yet</td>
                        </tr>
                        <tr v-for="(detail, index) in details">
                            <input type="hidden" name="Sequence[]" v-model="detail.Sequence">
                            <td>
                                <input type="text" class="form-control" name="PartID[]" v-model="detail.PartID" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="PartName[]" v-model="detail.PartName" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control" v-model="detail.UnitID" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="UnitID[]" v-model="detail.UnitID1" readonly>
                            </td>
                            <td>
                                <vue-autonumeric class="form-control" :options="autonumericFormat2" type="text" name="Qty[]" v-model="detail.Qty" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <vue-autonumeric class="form-control" :options="autonumericFormat2" type="text" name="QtyRemaining[]" v-model="detail.QtyRemaining" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <vue-autonumeric class="form-control" :options="autonumericFormat2" type="text" name="QtyDeliver[]" v-model="detail.QtyDeliver" readonly=""></vue-autonumeric>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="division[]" v-model="detail.division" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="warehouse[]" v-model="detail.warehouse" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control" :value="displayStockValue(detail.BatchNo)" readonly>
                            </td>
                            <td v-for="(n, i) in revCount">
                                <vue-autonumeric :options="autonumericFormat2" v-model="detail.rev[i]" class="form-control" readonly v-if="revData[i].type == 'numeric'"></vue-autonumeric>
                                <input type="text" v-model="detail.rev[i]" class="form-control" readonly v-else>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-content">
                <div class="row justify-content-between mb-3">
                    <div class="col-lg-4 col-12 mb-lg-0 mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="Notes" class="form-control" rows="3" readonly>{{ $do->Notes }}</textarea>
                    </div>

                    <div class="col-auto" hidden>
                        <table>
                            <tr>
                                <td class="fw-bold">Grand Total</td>
                                <td style="width: 10px;"></td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat2" name="SubTotal" class="form-control text-end" :value="getGrandTotal" readonly></vue-autonumeric>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
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
        th {
            white-space: nowrap;
        }

        .detail-select {
            width: 300px;
        }

        @media only screen and (max-width: 575.98px){
            .detail-select {
                width: 100%;
            }
        }
    </style>
@endsection

@section('scripts')
    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>

    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('js/plugins/flatpickr/flatpickr.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.5.4"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.13/dist/vue.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.0/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue-autonumeric@1.2.6/dist/vue-autonumeric.min.js"></script>
    <script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.js') }}"></script>

    <script type="text/x-template" id="select2-template">
        <select>
            <slot></slot>
        </select>
    </script>
    <script src="{{ asset('js/vueComponent-select2.js') }}"></script>

    <!-- Page JS Code -->
    <script>
        let app = new Vue({
            el: '#vue-container',
            data: {
                revData: @json($revData),
                revCount: {{ $do->RevCount }},
                details: @json($details),
                grandTotal: 0,

                autonumericFormat: {
                    minimumValue: '0',
                    maximumValue: '9999999999999',
                    decimalPlaces: 0,
                    digitGroupSeparator: '.',
                    decimalCharacter: ',',
                    modifyValueOnWheel: false,
                    allowDecimalPadding: false,
                    unformatOnSubmit: true
                },

                autonumericFormat2: {
                    minimumValue: '0',
                    maximumValue: '9999999999999',
                    decimalPlaces: 6,
                    digitGroupSeparator: '.',
                    decimalCharacter: ',',
                    modifyValueOnWheel: false,
                    allowDecimalPadding: false,
                    unformatOnSubmit: true
                },
            },
            methods: {
                displayStockValue(value) {
                    return value === '__NULL__' ? '' : (value || '');
                },
            },
            computed: {
                getGrandTotal(){
                    this.grandTotal = 0;
                    for(var i = 0; i < this.details.length; i++){
                        this.grandTotal += (this.details[i].Price * this.details[i].QtyDeliver);
                    }

                    return this.grandTotal;
                },
            },
            watch: {

            },
            mounted() {

            }
        });

    </script>
@endsection
