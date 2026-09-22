@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Edit Purchase Invoice</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class=" w-100 px-lg-5 py-lg-3 p-3" id="vue-container" style="overflow-x: hidden">

        <form autocomplete="off" method="post" enctype="multipart/form-data" action="{{ route('pi.update') }}" id="pi-form">
            @csrf

            <div class="d-flex flex-row align-items-center mb-5">
                <a href="{{ route('pi') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                <h1 class="h3 fw-bold ms-4 mb-0">
                    Edit Purchase Invoice
                </h1>
            </div>

            @if(count($errors->all()) > 0)
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <p class="m-0 fs-6">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <input type="hidden" name="id" value="{{ $pi->TransactionNo }}">
            <input type="hidden" name="rev" v-model="revCount">

            <div class="block block-rounded">
                <div class="block-content pb-3">
                    <button type="submit" class="btn btn-primary mb-3 fs-6"><i class="fa fa-fw fa-save me-2"></i>Save</button>

                    <div class="d-flex flex-row align-items-center mb-3">
                        <div>
                            <label class="form-label">Transaction No <span class="text-danger">*</span></label>
                            <input type="text" name="TransactionNo" id="TransactionNo" class="form-control" value="{{ old('TransactionNo') ?? $pi->TransactionNo }}" readonly>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-12 pe-lg-5">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">Transaction Date <span class="text-danger">*</span></label>
                                    <input type="text" class="js-flatpickr form-control js-flatpickr-enabled flatpickr-input active" id="TransactionDate" name="TransactionDate" placeholder="d/m/Y" value="{{ old('TransactionDate') ?? date('d/m/Y', strtotime($pi->TransactionDate)) }}" required readonly="readonly">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Due Date <span class="text-danger">*</span></label>
                                    <input type="text" class="js-flatpickr form-control js-flatpickr-enabled flatpickr-input active" id="DueDate" name="DueDate" placeholder="d/m/Y" value="{{ old('DueDate') ?? date('d/m/Y', strtotime($pi->DueDate)) }}" readonly="readonly" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Supplier <span class="text-danger">*</span></label>
                                <input type="hidden" name="SupplierID" v-model="supplierID">
                                <input type="text" class="form-control" v-model="supplier" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Currency <span class="text-danger">*</span></label>
                                <input type="hidden" name="CurrencyID" v-model="currencyID">
                                <input type="text" class="form-control" v-model="currency" readonly>
                            </div>
                        </div>
                        <div class="col-lg-6 col-12 ps-lg-5">
                            <div class="mb-3">
                                <label class="form-label">Invoice Type <span class="text-danger">*</span></label>
                                <div class="space-y-2">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="tProcurement" name="InvoiceType" value="PROCEREMENT" v-model="invType">
                                        <label class="form-check-label" for="tProcurement">Procurement</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="tDownP" name="InvoiceType" value="DOWN_PAYMENT" v-model="invType">
                                        <label class="form-check-label" for="tDownP">Down Payment</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">Rate <span class="text-danger">*</span></label>
                                    <vue-autonumeric :options="autonumericFormat" class="form-control" name="Rate" id="Rate" v-model="rate"></vue-autonumeric>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Fiscal Rate <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="FiscalRate" id="FiscalRate" value="{{ old('FiscalRate') ?? $pi->FiscalRate }}">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="form-label">VAT <span class="text-danger">*</span></label>
                                <input type="hidden" name="VAT" v-model="vatType">
                                <div class="space-y-2">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="VATNone" name="VAT" value="N" v-model="vatType" :disabled="pickedVat == 'I'">
                                        <label class="form-check-label" for="VATNone">None</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="VATExclude" name="VAT" value="E" v-model="vatType" :disabled="pickedVat == 'I'">
                                        <label class="form-check-label" for="VATExclude">Exclude</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="VATInclude" name="VAT" value="I" v-model="vatType" disabled>
                                        <label class="form-check-label" for="VATInclude">Include</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block block-rounded" v-if="invType == 'DOWN_PAYMENT'">
                <div class="block-content">
                    <div class="d-flex flex-row justify-content-between align-items-center mb-3">
                        <h5 class="m-0">Down Payment</h5>
                    </div>

                    <div class="table-responsive w-100">
                        <table class="table table-bordered w-100">
                            <thead>
                            <tr>
                                <th>PO Number</th>
                                <th>PO Date</th>
                                <th>PO Amount</th>
                                <th style="width: 10%;">%DP</th>
                                <th >DP Amount</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>
                                    <select2 url="{{route('pi.po', ['inv' => ($pi->InvoiceType == 'DOWN_PAYMENT' ? $pi->dt3[0]->ReffNumber : '')])}}" v-model="dpInv" :prevalue="dpInv" id="DPReff" class="form-select" name="DPReff"
                                             placeholder="Pick Purchase Order" required>
                                        <option>-</option>
                                    </select2>
                                </td>
                                <td>
                                    <input class="form-control" name="PODate" :value="poDate" readonly>
                                </td>
                                <td>
                                    <vue-autonumeric :options="getFormat()" name="POAmount" class="form-control text-end" :value="poAmount" readonly=""></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat2" type="text" name="DownPaymentPercent" class="form-control text-end sync-edit" v-model="dpP" data-type="dpP" required></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric :options="getFormat()" name="DownPayment" class="form-control text-end sync-edit" v-model="dp" data-type="dp" required></vue-autonumeric>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

            <div class="block block-rounded" v-if="invType == 'PROCEREMENT'">
                <div class="block-content">
                    <div class="d-flex flex-row justify-content-between align-items-center mb-3">
                        <h5 class="m-0">General</h5>
                        <div class="row">
                            <div class="col-auto d-flex flex-row align-items-center">
                                <p class="mb-0 me-2" v-if="loadGR"><i class="fa fa-fw fa-spin fa-circle-notch"></i></p>
                                <div style="width: 300px;">
                                    <select2 url="{{route('pi.gr', ['id' => $pi->id])}}" id="PickGR" class="form-select"
                                             placeholder="Pick Goods Receiving" :disabled="loadGR">
                                        <option>-</option>
                                    </select2>
                                </div>
                            </div>
                            <div class="col-auto d-flex flex-row align-items-center">
                                <button type="button" class="btn btn-secondary" @click="addRev"><i class="fa fa-fw fa-plus me-1"></i>Add Rev</button>
                                <button type="button" class="btn btn-secondary ms-2" @click="removeRev"><i class="fa fa-fw fa-minus me-1"></i>Decrease Rev</button>
                            </div>
                        </div>

                    </div>

                    <div id="detail-table" class="table-responsive w-100" style="overflow-x: scroll">
                        <table class="table table-bordered nowrap w-100" style="table-layout: fixed; overflow-x: scroll">
                            <thead>
                            <tr>
                                <th style="width: 65px"></th>
                                <th style="width: 250px;">Receiving Number</th>
                                <th style="width: 250px;">Receiving Date</th>
                                <th style="width: 250px;">PO Number</th>
                                <th style="width: 200px;">PartID</th>
                                <th style="width: 250px;">Part Name</th>
                                <th style="width: 200px;">Qty</th>
                                <th style="width: 200px;">UnitID</th>
                                <th style="width: 200px;">Unit Price</th>
                                <th style="width: 200px;">Sub Total</th>
                                <th style="width: 200px;">Division ID</th>
                                <th v-for="(n, i) in revCount" style="width: 250px;">
                                    <span v-if="revData[i].name != null && revData[i].name != ''">@{{ revData[i].name }}</span>
                                    <span v-else>Rev @{{ n }}</span>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td :colspan="spanCount" class="text-center p-3" v-if="details.length == 0">No GR Added Yet</td>
                            </tr>
                            <tr v-for="(detail, index) in details">
                                <td>
                                    <button type="button" class="btn btn-danger" @click="deleteDetail(detail)"><i class="fa fa-fw fa-trash-can"></i></button>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="ReffNumber[]" v-model="detail.number" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" v-model="detail.date" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" v-model="detail.po" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="PartID[]" v-model="detail.part" readonly>
                                    <input type="hidden" name="Sequence[]" v-model="detail.sequence">
                                </td>
                                <td>
                                    <input type="text" class="form-control" v-model="detail.part_name" readonly>
                                </td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat2" type="text" class="form-control" name="Qty[]" v-model="detail.qty" readonly></vue-autonumeric>
                                    <input type="hidden" name="Conversion[]" v-model="detail.conversion">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="UnitID[]" v-model="detail.unit" readonly>
                                </td>
                                <td>
                                    <input type="hidden" name="Price[]" v-model="detail.price">
                                    <vue-autonumeric :options="autonumericFormat2" type="text" class="form-control" v-model="detail.price_tax" readonly></vue-autonumeric>
                                    <input type="hidden" name="discount[]" v-model="detail.discount">
                                    <input type="hidden" name="discount1[]" v-model="detail.discount1">
                                    <input type="hidden" name="discount1p[]" v-model="detail.discount1p">
                                    <input type="hidden" name="discount2[]" v-model="detail.discount2">
                                    <input type="hidden" name="discount2p[]" v-model="detail.discount2p">
                                </td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat2" type="text" class="form-control" v-model="detail.subtotal_tax" readonly></vue-autonumeric>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="DivisionID[]" v-model="detail.division" readonly>
                                </td>
                                <td v-for="(n, i) in revCount">
                                    <input type="text" :name="'rev' + n + '[]'" v-model="detail.rev[i]" class="form-control js-flatpickr" v-if="revData[i].type == 'date'">
                                    <vue-autonumeric :options="autonumericFormat2" :name="'rev' + n + '[]'" v-model="detail.rev[i]" class="form-control" v-else-if="revData[i].type == 'numeric'"></vue-autonumeric>
                                    <select2 :url="getRevUrl(n)" v-model="detail.rev[i]" :prevalue="detail.rev[i]" class="form-select" :name="'rev' + n + '[]'" placeholder="Pick an Item" v-else-if="revData[i].type == 'select'">
                                        <option>-</option>
                                    </select2>
                                    <input type="text" :name="'rev' + n + '[]'" v-model="detail.rev[i]" class="form-control" v-else>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

            <div class="block block-rounded" v-if="invType == 'PROCEREMENT'">
                <div class="block-content">
                    <div class="d-flex flex-row justify-content-between align-items-center mb-3">
                        <h5 class="m-0">Down Payment</h5>
                        <div class="d-flex flex-row align-items-center">
                            <p class="mb-0 me-2" v-if="loadPI"><i class="fa fa-fw fa-spin fa-circle-notch"></i></p>
                            <select2 :url="getPIUrl" id="PickPI" class="form-select" style="width: 400px !important;"
                                     placeholder="Pick Down Payment Invoice" :disabled="loadPI">
                                <option>-</option>
                            </select2>
                        </div>
                    </div>

                    <div class="table-responsive w-100">
                        <table class="table table-bordered w-100">
                            <thead>
                            <tr>
                                <th style="width: 65px"></th>
                                <th>Reff Number</th>
                                <th>Transaction Date</th>
                                <th>Currency ID</th>
                                <th style="width: 120px;">Rate</th>
                                <th>Rest Amount</th>
                                <th>Amount</th>
                                <th>Amount IDR</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td colspan="8" class="text-center p-3" v-if="reffs.length == 0">No Invoice Added Yet</td>
                            </tr>
                            <tr v-for="(detail, index) in reffs" :key="detail.number">
                                <td>
                                    <button type="button" class="btn btn-danger" @click="deleteReff(detail)"><i class="fa fa-fw fa-trash-can"></i></button>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="DPReffNumber[]" v-model="detail.number" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" v-model="detail.date" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" v-model="detail.currency" readonly>
                                </td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat2" type="text" class="form-control" v-model="detail.rate" readonly></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat2" type="text" name="RestAmount[]" class="form-control" v-model="detail.rest_amount" readonly></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat2" type="text" class="form-control" name="Amount[]" v-model="detail.amount" @input="changeAmountIDR(index)" required></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat2" type="text" class="form-control" v-model="detail.amount_idr" @input="changeAmount(index)" required></vue-autonumeric>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

            <div class="block block-rounded">
                <div class="block-content">
                    <div class="row justify-content-end mb-3">

                        <div class="col-auto">
                            <div class="row align-items-start">
                                <div class="col-auto d-flex flex-row align-items-center">
                                    <label class="form-label m-0">Total Qty</label>
                                    <input class="form-control ms-2" style="width: 50px;" :value="totalQty" readonly>
                                </div>
                                <div class="col-auto">
                                    <table>
                                        <tr>
                                            <td class="fw-bold">Sub Total</td>
                                            <td></td>
                                            <td>
                                                <input type="hidden" name="SubTotal" :value="getSubTotal">
                                                <vue-autonumeric :options="getFormat()" class="form-control text-end auto-format" :value="getSubTotal" readonly></vue-autonumeric>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Down Payment</td>
                                            <td></td>
                                            <td>
                                                <vue-autonumeric :options="getFormat()" type="text" class="form-control text-end auto-format" :value="getDownPayment" readonly></vue-autonumeric>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Tax Service</td>
                                            <td class="px-2">
                                                <div class="d-flex flex-row align-items-center">
                                                    <vue-autonumeric :options="autonumericFormat2" name="PercentageServiceTax" data-type="serviceP" v-model="serviceP" class="form-control sync-edit" style="width: 50px"></vue-autonumeric>
                                                    <p class="mb-0 ms-1 fs-6">%</p>
                                                </div>
                                            </td>
                                            <td>
                                                <vue-autonumeric :options="getFormat()" class="form-control text-end sync-edit" name="ServiceTax" data-type="serviceTax" v-model="serviceTax"></vue-autonumeric>
                                            </td>
                                        </tr>
{{--                                        <tr>--}}
{{--                                            <td class="fw-bold">PPh 22</td>--}}
{{--                                            <td class="px-2">--}}
{{--                                                <div class="d-flex flex-row align-items-center">--}}
{{--                                                    <vue-autonumeric :options="autonumericFormat2" data-type="pph22Percentage" v-model="pph22Percentage" name="pph22Percentage" class="form-control sync-edit" style="width: 50px"></vue-autonumeric>--}}
{{--                                                    <p class="mb-0 ms-1 fs-6">%</p>--}}
{{--                                                </div>--}}
{{--                                            </td>--}}
{{--                                            <td>--}}
{{--                                                <vue-autonumeric :options="getFormat()" name="PPH22" class="form-control text-end sync-edit" v-model="pph22" data-type="pph22"></vue-autonumeric>--}}
{{--                                            </td>--}}
{{--                                        </tr>--}}
                                        <tr>
                                            <td class="fw-bold">VAT</td>
                                            <td></td>
                                            <td>
                                                <input type="hidden" name="VATValue" :value="getVAT">
                                                <vue-autonumeric :options="autonumericFormat" type="text" class="form-control text-end" :value="getVAT" readonly></vue-autonumeric>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Freight Charge</td>
                                            <td></td>
                                            <td>
                                                <vue-autonumeric :options="getFormat()" name="Freight" class="form-control text-end" v-model="freight"></vue-autonumeric>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Grand Total</td>
                                            <td></td>
                                            <td>
                                                <input type="hidden" name="GrandTotal" :value="getGrandTotal">
                                                <vue-autonumeric :options="getFormat()" class="form-control text-end auto-format" :value="getGrandTotal" readonly></vue-autonumeric>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>


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
    </style>
@endsection

@section('scripts')
    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>

    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.js') }}"></script>
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
                firstInit: false,
                rate: {{ old('Rate') ?? $pi->Rate }},

                subTotal: 0,
                serviceTax: '{{ old('ServiceTax') ?? $pi->ServiceTax }}',
                serviceP: '{{ old('PercentageServiceTax') ?? $pi->PercentageServiceTax }}',
                //pph22: '{{ old('PPH22') ?? $pi->PPH22 }}',
                pph22: 0,
                pph22Percentage: '{{ old('PercentagePPH22') ?? '0' }}',
                freight: {{ old('Freight') ?? $pi->Freight }},
                invType: '{{ old('InvoiceType') ?? $pi->InvoiceType }}',
                vatType: '{{ old('VAT') ?? $pi->VAT }}',
                vatP: '{{ old('VATP') ?? $vatP }}',
                pickedVat: '{{ old('VAT') ?? $pi->VAT }}',
                vatValue: 0,

                supplierID: '{{ $pi->SupplierID }}',
                supplier: '{{ $pi->SupplierID . ($pi->supplier->SupplierName ? ' - ' . $pi->supplier->SupplierName : '') }}',
                currencyID: '{{ $pi->CurrencyID }}',
                currency: '{{ $pi->CurrencyID . ($pi->currency->CurrencyName ? ' - ' . $pi->currency->CurrencyName : '') }}',

                //DP
                dpInv: '{{ old('DPReff') ?? $pi->InvoiceType == 'DOWN_PAYMENT' ? $pi->dt3[0]->ReffNumber : '' }}',
                poDate: '{{ old('PODate') ?? '' }}',
                poAmount: '{{ old('POAmount') ?? '0' }}',
                dp: {!! old('DownPayment') ?? $pi->InvoiceType == 'DOWN_PAYMENT' ? $pi->dt3[0]->Amount : 0 !!},
                dpP: '{{ old('DownPaymentPercent') ?? '0' }}',
                initiateDP: false,

                //PROCUREMENT
                loadGR: false,
                loadPI: false,
                details: @json($details),
                reffs: @json($reffs),
                revCount: {!! $pi->RevCount !!},
                pickedPO: '',
                dpValue: 0,

                init: false,
                editType: '',

                autonumericFormat: {
                    decimalPlaces: 0,
                    digitGroupSeparator: '.',
                    decimalCharacter: ',',
                    modifyValueOnWheel: false,
                    allowDecimalPadding: false,
                    unformatOnSubmit: true
                },

                autonumericFormat2: {
                    decimalPlaces: 6,
                    digitGroupSeparator: '.',
                    decimalCharacter: ',',
                    modifyValueOnWheel: false,
                    allowDecimalPadding: false,
                    unformatOnSubmit: true
                },
            },
            methods: {
                getFormat(){
                    if(this.currencyID == "IDR"){
                        return this.autonumericFormat;
                    }

                    return this.autonumericFormat2
                },

                getPIUrl(){
                    return '{{ route('pi.pi') }}' + '?supplier=' + this.supplierID + '&id={{ $pi->id }}';
                },


                getPOInfo(){
                    let app = this;
                    $.ajax({
                        url: '{{ route('pi.po.info') }}',
                        type: 'GET',
                        data: {
                            inv: app.dpInv,
                            edit: '{{ $pi->TransactionNo }}'
                        }
                    }).then(function (result){
                        app.poDate = result.date;
                        app.poAmount = result.amount;
                        app.vatType = result.vat;
                        app.pickedVat = result.vat;
                        app.vatP = result.vatP;
                        app.supplierID = result.supplier;
                        app.supplier = result.supplier_name;
                        app.currencyID = result.currency;
                        app.currency = result.currency_name;
                        $('#DueDate').val(result.due);

                        if(app.firstInit){
                            //$('#Rate').val(result.rate).trigger('change');
                            app.rate = result.rate;
                        }
                        else {
                            app.firstInit = true;
                        }

                        if(app.initiateDP){
                            app.dpP = 0;
                            app.dp = 0;

                            // app.serviceP = result.service;
                            // app.pph22Percentage = result.pph;
                            // app.freight = result.freight;
                        }
                        // app.serviceTax = app.serviceP / 100 * app.subTotal;
                        // app.pph22 = app.pph22Percentage / 100 * app.subTotal;

                        if(app.poAmount != 0 && !app.initiateDP){
                            app.dpP = app.dp / app.poAmount * 100;
                            app.initiateDP = true;
                        }
                    });
                },


                // GR

                addRev(){
                    if(this.revCount < 15){
                        this.revCount++;
                        for(var i = 0; i < this.details.length; i++){
                            this.details[i].rev.push('');
                        }

                        setTimeout(function (){
                            $('.js-flatpickr').flatpickr({
                                dateFormat: "d/m/Y",
                            });
                            $('.js-flatpickr:visible').on('focus', function () {
                                $(this).blur()
                            });
                            $('.js-flatpickr:visible').prop('readonly', false);
                        }, 50);
                    }
                },
                removeRev(){
                    if(this.revCount > 0){
                        this.revCount--;
                        if(this.revCount == 0){
                            for(var i = 0; i < this.details.length; i++){
                                this.details[i].rev.splice(this.revCount-1, 1);
                            }
                        }
                    }
                },
                getRevUrl(index){
                    return '{{ route('rev.select') }}?type=PURCHASING&index=' + index;
                },


                getGRDetail(inv){
                    this.loadGR = true;
                    let app = this;
                    $.ajax({
                        url: '{{ route('pi.gr.detail') }}',
                        type: 'GET',
                        data: {
                            inv: inv,
                            po: app.pickedPO,
                        }
                    }).then(function (result){
                        if(result.success){
                            app.vatType = result.vat;
                            app.pickedVat = result.vat;
                            app.vatP = result.vatP;
                            app.supplierID = result.supplier;
                            app.supplier = result.supplier_name;
                            app.currencyID = result.currency;
                            app.currency = result.currency_name;
                            if(app.firstInit){
                                //$('#Rate').val(result.rate).trigger('change');
                                app.rate = result.rate;
                            }
                            else {
                                app.firstInit = true;
                            }
                            $('#DueDate').val(result.due);

                            app.serviceP = result.service;
                            app.serviceTax = app.serviceP / 100 * app.subTotal;
                            app.pph22Percentage = result.pph;
                            app.pph22 = app.pph22Percentage / 100 * app.subTotal;
                            app.freight = result.freight;

                            var newData = result.data;
                            if(result.rev > app.revCount){
                                for(var i = 0; i < app.details.length; i++){
                                    for(var j = app.details[i].rev.length; j <= result.rev; j++){
                                        app.details[i].rev.push('');
                                    }
                                }

                                app.revCount = result.rev;
                            }
                            else {
                                for(var i = 0; i < newData.length; i++){
                                    for(var j = newData[i].rev.length; j <= app.revCount; j++){
                                        newData[i].rev.push('');
                                    }
                                }
                            }

                            app.details = app.details.concat(newData);
                            app.pickedPO = result.po;

                            setTimeout(function (){
                                $('.js-flatpickr').flatpickr({
                                    dateFormat: "d/m/Y",
                                });
                                $('.js-flatpickr:visible').on('focus', function () {
                                    $(this).blur()
                                });
                                $('.js-flatpickr:visible').prop('readonly', false);
                            }, 50);

                            $('#detail-table').animate({
                                scrollLeft: 0
                            }, 250);
                        }
                        else {
                            One.helpers('jq-notify', {
                                type: 'warning',
                                icon: '',
                                message: "Picked GR doesn't have the same Supplier/Currency as the already picked GR!"
                            });
                            $(this).val('').trigger('change');
                        }
                    }).always(function (){
                        app.loadGR = false;
                        $('#PickGR').val('').trigger('change');
                    });
                },

                getPIDetail(inv){
                    this.loadPI = true;
                    let app = this;
                    $.ajax({
                        url: '{{ route('pi.pi.detail') }}',
                        type: 'GET',
                        data: {
                            inv: inv,
                            edit: '{!! $pi->TransactionNo !!}',
                        }
                    }).then(function (result){
                        app.reffs.push(result.data);
                    }).always(function (){
                        app.loadPI = false;
                        $('#PickPI').val('').trigger('change');
                    });
                },







                changeAmount(index){
                    this.reffs[index].amount = this.reffs[index].amount_idr / this.reffs[index].rate;
                },
                changeAmountIDR(index){
                    this.reffs[index].amount_idr = this.reffs[index].amount * this.reffs[index].rate;
                },




                deleteDetail(item){
                    //this.details.splice(index, 1);
                    this.details = this.details.filter(function (x) { return x.number !== item.number; });
                    if(this.details.length == 0){
                        this.pickedPO = '';
                    }
                },
                deleteReff(item){
                    this.reffs = this.reffs.filter(function (x) { return x !== item; });
                }
            },
            computed: {
                spanCount(){
                    return (this.revCount + 11);
                },
                totalQty(){
                    var total = 0;
                    for(var i = 0; i < this.details.length; i++){
                        total += parseFloat(this.details[i].qty);
                    }

                    return total;
                },
                getSubTotal(){
                    this.subTotal = 0;
                    if(this.invType == 'DOWN_PAYMENT'){
                        if(this.vatType == 'I'){
                            let temp = this.dp / (1 + (this.vatP/100));
                            let tax = temp * this.vatP/100;
                            this.subTotal = this.dp - tax;
                        }
                        else {
                            this.subTotal = this.dp;
                        }
                    }
                    else {
                        for(var i = 0; i < this.details.length; i++){
                            this.subTotal += this.details[i].subtotal;
                        }
                    }

                    return this.subTotal;
                },
                getDownPayment(){
                    if(this.invType == 'DOWN_PAYMENT'){
                        return 0;
                    }
                    else {
                        this.dpValue = 0;
                        for(var i = 0; i < this.reffs.length; i++){
                            // if(this.reffs[i].vat == 'I'){
                            //     let temp = this.reffs[i].amount_idr / (1 + (this.reffs[i].vatP/100));
                            //     let tax = temp * this.reffs[i].vatP/100;
                            //     this.dpValue += (this.reffs[i].amount_idr - tax);
                            // }
                            // else {
                            //     this.dpValue += this.reffs[i].amount_idr;
                            // }
                            this.dpValue += this.reffs[i].amount;
                        }
                        return this.dpValue;
                    }
                },
                getServiceTax(){
                    if(this.serviceTax != ''){
                        return this.subTotal * parseFloat(this.serviceTax) / 100;
                    }

                    return 0;
                },
                getPPH(){
                    if(this.pph != ''){
                        return this.subTotal * parseFloat(this.pph) / 100;
                    }

                    return 0;
                },
                getVAT(){
                    this.vatValue = 0;
                    if(this.vatType != 'N'){
                        if(this.invType == 'DOWN_PAYMENT'){
                            this.vatValue = this.subTotal * this.vatP / 100;
                        }
                        else {
                            if(this.dpValue > 0){
                                this.vatValue = Math.round(this.subTotal - this.dpValue) * this.vatP / 100;
                            }
                            else {
                                var totalVat = 0;
                                for(var i = 0; i < this.details.length; i++){
                                    totalVat += (this.details[i].subtotal * this.details[i].vat/100);
                                }
                                this.vatValue = totalVat;
                            }
                        }
                    }

                    return this.vatValue;
                },
                getGrandTotal(){
                    if(this.invType == 'DOWN_PAYMENT'){
                        return this.subTotal - this.serviceTax + this.pph22 + this.vatValue + this.freight;
                    }
                    else{
                        return this.subTotal - this.dpValue - this.serviceTax + this.pph22 + this.vatValue + this.freight;
                    }
                },
            },
            watch: {
                currencyID(){
                    setTimeout(function (){
                        $('.auto-format').attr('readonly', true);
                    }, 1);
                },
                subTotal(){
                    if(this.init){
                        this.serviceTax = this.serviceP / 100 * this.subTotal;
                        this.pph22 = this.pph22Percentage / 100 * this.subTotal;
                    }
                },
                dp(){
                    if(this.editType == 'dp'){
                        this.dpP = (this.dp / this.poAmount) * 100;
                    }
                },
                dpP(){
                    if(this.editType == 'dpP'){
                        this.dp = (this.dpP / 100) * this.poAmount;
                    }
                },
                serviceP(){
                    if(this.editType == 'serviceP'){
                        this.serviceTax = this.serviceP / 100 * this.subTotal;
                    }
                },
                serviceTax(){
                    if(this.editType == 'serviceTax'){
                        this.serviceP = this.serviceTax / this.subTotal * 100;
                    }
                },
                pph22() {
                    if(this.editType == 'pph22'){
                        this.pph22Percentage = this.pph22 / this.subTotal * 100;
                    }
                },
                pph22Percentage(){
                    if(this.editType == 'pph22Percentage'){
                        this.pph22 = this.pph22Percentage / 100 * this.subTotal;
                    }
                }
            },
            mounted() {
                let app = this;

                $('#pi-form').on('change', '#DPReff', function (){
                    if($(this).val() != null){
                        app.getPOInfo($(this).val());
                    }
                });

                $('#pi-form').on('change', '#PickGR', function (){
                    if($(this).val() != null){
                        var check = false;
                        for(var i = 0; i < app.details.length;i++){
                            if(app.details[i].number == $(this).val()){
                                check = true;
                                break;
                            }
                        }

                        if(!check){
                            app.getGRDetail($(this).val());
                        }
                        else {
                            One.helpers('jq-notify', {
                                type: 'warning',
                                icon: '',
                                message: 'Goods Receiving is already on the list!'
                            });
                            $(this).val('').trigger('change');
                        }
                    }
                });

                $('#pi-form').on('change', '#PickPI', function (){
                    if($(this).val() != null){
                        var check = false;
                        for(var i = 0; i < app.reffs.length;i++){
                            if(app.reffs[i].number == $(this).val()){
                                check = true;
                                break;
                            }
                        }

                        if(!check){
                            app.getPIDetail($(this).val());
                        }
                        else {
                            One.helpers('jq-notify', {
                                type: 'warning',
                                icon: '',
                                message: 'Down Payment Invoice is already on the list!'
                            });
                            $(this).val('').trigger('change');
                        }
                    }
                });

                $('#pi-form').on('focus', '.sync-edit', function (){
                    app.editType = $(this).data('type');
                });

                setTimeout(function (){
                    app.pph22Percentage = app.pph22 / app.subTotal * 100;
                    app.init = true;
                }, 100);

                // $('#pi-form').on('change', '.part', function (){
                //     if($(this).val() != null){
                //         app.changePart($(this).data('index'));
                //     }
                // });
                //
                // $('#pi-form').on('change', '.unit', function (){
                //     if($(this).val() != null){
                //         app.getConversion($(this).data('index'));
                //     }
                // });
            }
        });


        $('.form-select2').select2({
            theme: 'bootstrap-5'
        });

        $('#supplier-container').hide();
        $('#currency-container').hide();

        $('#automatic').on('change', function () {
            if($('#automatic').is(':checked')){
                $('#TransactionNo').attr('disabled', true);
            }
            else {
                $('#TransactionNo').removeAttr('disabled');
            }
        });

        $('.js-flatpickr').flatpickr({
            dateFormat: "d/m/Y",
        });
        $('.js-flatpickr:visible').on('focus', function () {
            $(this).blur()
        });
        $('.js-flatpickr:visible').prop('readonly', false);

        // let rate = new AutoNumeric('#Rate', {
        //     decimalPlaces: 6,
        //     minimumValue: 1,
        //     digitGroupSeparator: '.',
        //     decimalCharacter: ',',
        //     allowDecimalPadding: "false",
        //     modifyValueOnWheel: false,
        //     unformatOnSubmit: true
        // });

        let frate = new AutoNumeric('#FiscalRate', {
            decimalPlaces: 6,
            minimumValue: 1,
            digitGroupSeparator: '.',
            decimalCharacter: ',',
            allowDecimalPadding: "false",
            modifyValueOnWheel: false,
            unformatOnSubmit: true
        });

        let numeric = new AutoNumeric.multiple('.number-input', {
            decimalPlaces: 0,
            minimumValue: 0,
            digitGroupSeparator: '.',
            decimalCharacter: ',',
            allowDecimalPadding: "false",
            modifyValueOnWheel: false,
            unformatOnSubmit: true
        });



    </script>
@endsection
