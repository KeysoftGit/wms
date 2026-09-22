@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Goods Receiving - {{ $gr->TransactionNo }}</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <div class="d-flex flex-row align-items-center mb-5">
            <a href="{{ route('gr') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
            <h1 class="h3 fw-bold ms-4 mb-0">
                Goods Receiving - {{ $gr->TransactionNo }}
            </h1>
        </div>

        @if (count($errors->all()) > 0)
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <p class="m-0 fs-6">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="row mb-3 justify-content-between align-items-end">
            <div class="col-auto d-flex flex-row">
                @if (
                    !request()->has('br') &&
                        $gr->Editable == 1 &&
                        auth()->user()->hasAnyPermission(['admin', 'gr.edit']))
                    <a href="{{ route('gr.edit', $gr->id) }}" class="btn btn-primary fs-6"><i class="fa fa-fw fa-edit"></i>
                        Edit</a>
                @endif
            </div>

            <div class="col-auto">
                <form autocomplete="off" action="{{ config('app.report_url') }}/print/index">
                    <input type="hidden" name="transactionCode" value="{{ $gr->TransactionNo }}">
                    <input type="hidden" name="dbGuid" value="{{ session('guid') }}">
                    <div class="d-flex flex-row align-items-end">
                        <div>
                            <label>Print Type</label>
                            <select class="form-select" name="code" style="width: 100px;">
                                @foreach ($options as $option)
                                    <option value="{{ $option->Code }}">{{ $option->Type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-success ms-2"><i
                                class="fa fa-fw fa-print me-2"></i>Print</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-content pb-3">
                <div class="d-flex flex-row align-items-center mb-3">
                    <div>
                        <label class="form-label">Transaction No</label>
                        <input type="text" name="TransactionNo" id="TransactionNo" class="form-control"
                            value="{{ old('TransactionNo') ?? $gr->TransactionNo }}" readonly>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 col-12 pe-lg-5">
                        <div class="row">
                            <div class="col-lg-6 col-12">
                                <div class="mb-3">
                                    <label class="form-label">Transaction Date</label>
                                    <input type="text" class="form-control" id="TransactionDate" name="TransactionDate"
                                        value="{{ date('d/m/Y', strtotime($gr->TransactionDate)) }}" readonly>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="mb-3" id="warehouse-container">
                                    <label class="form-label">Warehouse</label>
                                    <input type="text" class="form-control"
                                        value="{{ $gr->WarehouseID . ($gr->warehouse->WarehouseName ? ' - ' . $gr->warehouse->WarehouseName : '') }}"
                                        readonly>
                                </div>
                            </div>
                        </div>


                        <div class="mb-3" id="po-container">
                            <label class="form-label">Purchase Order</label>
                            <input type="text" class="form-control" value="{{ $gr->qc->PONumber }}" readonly>
                        </div>
                    </div>
                    <div class="col-lg-6 col-12 ps-lg-5">
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Currency</label>
                                <input type="text" class="form-control" name="CurrencyName" v-model="currencyName"
                                    readonly>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Rate</label>
                                <input type="text" class="form-control" name="Rate" id="Rate"
                                    value="{{ $gr->Rate }}" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-content">
                <h5 class="mb-3">Detail</h5>

                <p class="text-center m-0 p-3" v-if="!isLoading && (po == '' || po == null)">Pick a Purchase Order First</p>
                <h1 class="text-center" v-if="isLoading"><i class="fa fa-fw fa-circle-notch fa-spin"></i></h1>

                <div class="table-responsive w-100" v-if="!isLoading && (po != '' && po != null)">
                    <table class="table table-bordered w-100" style="table-layout: fixed; overflow-x: scroll">
                        <thead>
                            <tr>
                                <th style="width: 150px;">Part ID</th>
                                <th style="width: 300px;">Part Name</th>
                                <th style="width: 100px;">UnitID</th>
                                <th style="width: 100px;">UnitID1</th>
                                <th style="width: 200px;">Qty</th>
                                <th style="width: 200px;">Receive Qty</th>
                                <th style="width: 200px;">Batch No</th>
                                <th v-for="(n, i) in revCount" style="width: 250px;">
                                    <span
                                        v-if="revData[i].name != null && revData[i].name != ''">@{{ revData[i].name }}</span>
                                    <span v-else>Rev @{{ n }}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="detail in details" v-if="detail.QtyReceive > 0">
                                <input type="hidden" name="Sequence[]" v-model="detail.Sequence">
                                <td>
                                    <input type="text" class="form-control" name="PartID[]" v-model="detail.PartID"
                                        readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="PartName[]"
                                        v-model="detail.PartName" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" v-model="detail.UnitID" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="UnitID[]" v-model="detail.UnitID1"
                                        readonly>
                                </td>
                                <td>
                                    <vue-autonumeric class="form-control" :options="autonumericFormat2" type="text"
                                        name="Qty[]" v-model="detail.Qty" readonly></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric class="form-control" :options="autonumericFormat2" type="text"
                                        name="QtyReceive[]" v-model="detail.QtyReceive" readonly=""></vue-autonumeric>
                                </td>
                                <td>
                                    <input type="text" class="form-control" v-model="detail.BatchNo" readonly>
                                </td>
                                <td v-for="(n, i) in revCount">
                                    <vue-autonumeric :options="autonumericFormat2" v-model="detail.rev[i]"
                                        class="form-control" readonly
                                        v-if="revData[i].type == 'numeric'"></vue-autonumeric>
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
                        <textarea name="Notes" class="form-control" rows="3" readonly>{{ $gr->Notes }}</textarea>
                    </div>
                    <div class="col-auto" hidden>
                        <table>
                            <tr>
                                <td class="fw-bold">Grand Total</td>
                                <td style="width: 10px;"></td>
                                <td>
                                    <vue-autonumeric :options="getFormat()" name="GrandTotal"
                                        class="form-control text-end auto-format" :value="getGrandTotal"
                                        readonly></vue-autonumeric>
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
    <script src="{{ asset('js/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('js/plugins/flatpickr/flatpickr.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.5.4"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.13/dist/vue.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.0/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue-autonumeric@1.2.6/dist/vue-autonumeric.min.js"></script>

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
                revCount: {{ $gr->RevCount }},
                isLoading: false,
                po: '{{ $gr->qc->PONumber }}',
                prevPO: '{{ $gr->qc->PONumber }}',
                currency: '',
                currencyName: '',
                details: [],
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
                    decimalPlaces: 2,
                    roundingMethod: 'S',
                    digitGroupSeparator: '.',
                    decimalCharacter: ',',
                    modifyValueOnWheel: false,
                    allowDecimalPadding: false,
                    unformatOnSubmit: true
                },
            },
            methods: {
                getFormat() {
                    if (this.currency == "IDR") {
                        return this.autonumericFormat;
                    }

                    return this.autonumericFormat2
                },

                getDetail() {
                    this.isLoading = true;
                    let app = this;
                    $.ajax({
                        url: '{{ route('gr.po.detail') }}',
                        type: 'GET',
                        data: {
                            id: app.po,
                            grID: '{{ $gr->TransactionNo }}',
                            prevPO: app.prevPO,
                            warehouse_id: '{{ $gr->WarehouseID }}',
                        }
                    }).then(function(result) {
                        if (result.status == 'success') {
                            app.details = result.selected_data || [];

                            app.currency = result.currency;
                            app.currencyName = result.currencyName;
                            //$('#Rate').val(result.rate).trigger('input');
                        }
                    }).always(function() {
                        app.isLoading = false;
                    });
                },
            },
            computed: {
                getGrandTotal() {
                    this.grandTotal = 0;
                    for (var i = 0; i < this.details.length; i++) {
                        this.grandTotal += (this.details[i].UnitPrice * this.details[i].QtyReceive);
                    }

                    return this.grandTotal;
                },
            },
            watch: {
                currency() {
                    setTimeout(function() {
                        $('.auto-format').attr('readonly', true);
                    }, 1);
                },
            },
            mounted() {
                this.getDetail();
            }
        });

        let rate = new AutoNumeric('#Rate', {
            decimalPlaces: 2,
            roundingMethod: 'S',
            minimumValue: 1,
            digitGroupSeparator: '.',
            decimalCharacter: ',',
            allowDecimalPadding: "false",
            modifyValueOnWheel: false,
            unformatOnSubmit: true
        });
    </script>
@endsection
