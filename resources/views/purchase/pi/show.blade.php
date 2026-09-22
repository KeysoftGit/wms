@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Purchase Invoice - {{ $pi->TransactionNo }}</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class=" w-100 px-lg-5 py-lg-3 p-3" id="vue-container" style="overflow-x: hidden">

        <div class="d-flex flex-row align-items-center mb-5">
            <a href="{{ route('pi') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
            <h1 class="h3 fw-bold ms-4 mb-0">
                {{ $pi->TransactionNo }}
            </h1>
        </div>

        <input type="hidden" name="rev" v-model="revCount">

        <div class="row justify-content-between mb-3 align-items-end">
            <div class="col-auto d-flex flex-row">
                @if(!request()->has('br') && $pi->Editable == 1 && auth()->user()->hasAnyPermission(['admin', 'pi.edit']))
                    <a href="{{ route('pi.edit', $pi->id) }}" class="btn btn-primary fs-6"><i class="fa fa-fw fa-edit"></i> Edit</a>
                @endif
            </div>

            <div class="col-auto">
                <form autocomplete="off" action="{{ config('app.report_url') }}/print/index">
                    <input type="hidden" name="transactionCode" value="{{ $pi->TransactionNo }}">
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

                <div class=" mb-3" style="width: 200px;">
                    <label class="form-label">Transaction No</label>
                    <input type="text" name="TransactionNo" id="TransactionNo" class="form-control" value="{{ $pi->TransactionNo }}" readonly>
                </div>

                <div class="row">
                    <div class="col-lg-6 col-12 pe-lg-5">
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Transaction Date</label>
                                <input type="text" class="form-control" id="TransactionDate" name="TransactionDate" placeholder="d/m/Y" value="{{ date('d/m/Y', strtotime($pi->TransactionDate)) }}" readonly="readonly">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Due Date</label>
                                <input type="text" class="form-control" id="DueDate" name="DueDate" placeholder="d/m/Y" value="{{ date('d/m/Y', strtotime($pi->DueDate)) }}" readonly="readonly">
                            </div>
                        </div>

                        <div class="mb-3" id="supplier-container">
                            <label class="form-label">Supplier</label>
                            <input type="text" class="form-control" value="{{ $pi->SupplierID . ($pi->supplier->SupplierName ? ' - ' . $pi->supplier->SupplierName : '') }}" readonly>
                        </div>


                        <div class="mb-3" id="loading-currency">
                            <label class="form-label">Currency</label>
                            <input type="text" class="form-control" value="{{ $pi->CurrencyID . ($pi->currency->CurrencyName ? ' - ' . $pi->currency->CurrencyName : '') }}" readonly>
                        </div>
                    </div>
                    <div class="col-lg-6 col-12 ps-lg-5">
                        <div class="mb-3">
                            <label class="form-label">Invoice Type</label>
                            @if($pi->InvoiceType == 'PROCEREMENT')
                                <input type="text" class="form-control" value="Procurement" readonly>
                            @else
                                <input type="text" class="form-control" value="Down Payment" readonly>
                            @endif
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Rate</label>
                                <vue-autonumeric :options="autonumericFormat2" type="text" class="form-control" name="Rate" id="Rate" v-model="rate" readonly></vue-autonumeric>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Fiscal Rate</label>
                                <vue-autonumeric :options="autonumericFormat2" type="text" class="form-control" name="FiscalRate" id="FiscalRate" value="{{ $pi->FiscalRate }}" readonly></vue-autonumeric>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">VAT</label>
                            @if($pi->VAT == 'N')
                                <input type="text" class="form-control" value="None" readonly>
                            @elseif($pi->VAT == 'E')
                                <input type="text" class="form-control" value="Exclude" readonly>
                            @else
                                <input type="text" class="form-control" value="Include" readonly>
                            @endif
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
                                         placeholder="Pick Purchase Order" disabled="">
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
                                <vue-autonumeric :options="autonumericFormat2" name="DownPaymentPercent" class="form-control text-end" v-model="dpP" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <vue-autonumeric :options="getFormat()" name="DownPayment" class="form-control text-end" v-model="dp" readonly></vue-autonumeric>
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
                </div>

                <div class="table-responsive w-100" style="overflow-x: scroll">
                    <table class="table table-bordered nowrap w-100" style="table-layout: fixed; overflow-x: scroll">
                        <thead>
                        <tr>
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
                                <vue-autonumeric :options="autonumericFormat2" type="text" class="form-control" v-model="detail.price_tax" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <vue-autonumeric :options="autonumericFormat2" type="text" class="form-control" v-model="detail.subtotal_tax" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="DivisionID[]" v-model="detail.division" readonly>
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

        <div class="block block-rounded" v-if="invType == 'PROCEREMENT'">
            <div class="block-content">
                <div class="d-flex flex-row justify-content-between align-items-center mb-3">
                    <h5 class="m-0">Down Payment</h5>
                </div>

                <div class="table-responsive w-100">
                    <table class="table table-bordered w-100">
                        <thead>
                        <tr>
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
                        <tr v-for="(detail, index) in reffs">
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
                                <vue-autonumeric :options="autonumericFormat2" type="text" class="form-control" v-model="detail.rest_amount" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <vue-autonumeric :options="autonumericFormat2" type="text" class="form-control" v-model="detail.amount" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <vue-autonumeric :options="autonumericFormat2" type="text" class="form-control" name="Amount[]" v-model="detail.amount_idr" readonly></vue-autonumeric>
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
                                            <vue-autonumeric :options="getFormat()" name="SubTotal" class="form-control text-end" :value="getSubTotal" readonly></vue-autonumeric>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Down Payment</td>
                                        <td></td>
                                        <td>
                                            <vue-autonumeric :options="getFormat()" class="form-control text-end" :value="getDP" readonly></vue-autonumeric>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Tax Service</td>
                                        <td class="px-2">
                                            <div class="d-flex flex-row align-items-center">
                                                <vue-autonumeric :options="autonumericFormat2" name="PercentageServiceTax" v-model="serviceP" class="form-control " style="width: 50px" readonly></vue-autonumeric>
                                                <p class="mb-0 ms-1 fs-6">%</p>
                                            </div>
                                        </td>
                                        <td>
                                            <vue-autonumeric :options="getFormat()" class="form-control text-end" name="ServiceTax" v-model="serviceTax" readonly></vue-autonumeric>
                                        </td>
                                    </tr>
{{--                                    <tr>--}}
{{--                                        <td class="fw-bold">PPh 22</td>--}}
{{--                                        <td class="px-2">--}}
{{--                                            <div class="d-flex flex-row align-items-center">--}}
{{--                                                <vue-autonumeric :options="autonumericFormat2" v-model="pph22Percentage" name="pph22Percentage" class="form-control" style="width: 50px" readonly></vue-autonumeric>--}}
{{--                                                <p class="mb-0 ms-1 fs-6">%</p>--}}
{{--                                            </div>--}}
{{--                                        </td>--}}
{{--                                        <td>--}}
{{--                                            <vue-autonumeric :options="getFormat()" name="PPH22" class="form-control text-end" v-model="pph22" readonly></vue-autonumeric>--}}
{{--                                        </td>--}}
{{--                                    </tr>--}}
                                    <tr>
                                        <td class="fw-bold">VAT</td>
                                        <td></td>
                                        <td>
                                            <vue-autonumeric :options="autonumericFormat" name="VATValue" class="form-control text-end" :value="getVAT" readonly></vue-autonumeric>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Freight Charge</td>
                                        <td></td>
                                        <td>
                                            <vue-autonumeric :options="getFormat()" name="Freight" class="form-control text-end" v-model="freight" readonly></vue-autonumeric>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Grand Total</td>
                                        <td></td>
                                        <td>
                                            <vue-autonumeric :options="getFormat()" name="GrandTotal" class="form-control text-end" :value="getGrandTotal" readonly></vue-autonumeric>
                                        </td>
                                    </tr>
                                </table>
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
    <script src="{{ asset('js/utf8.js') }}"></script>


    <!-- Page JS Code -->
    <script>
        Date.prototype.getWeekNumber = function(){
            var d = new Date(Date.UTC(this.getFullYear(), this.getMonth(), this.getDate()));
            var dayNum = d.getUTCDay() || 7;
            d.setUTCDate(d.getUTCDate() + 4 - dayNum);
            var yearStart = new Date(Date.UTC(d.getUTCFullYear(),0,1));
            return Math.ceil((((d - yearStart) / 86400000) + 1)/7)
        };

        let app = new Vue({
            el: '#vue-container',
            data: {
                revData: @json($revData),
                rate: {{ $pi->Rate }},

                subTotal: 0,
                serviceTax: '{{ $pi->ServiceTax }}',
                serviceP: '{{ $pi->PercentageServiceTax }}',
                //pph22: '{{ old('PPH22') ?? $pi->PPH22 }}',
                pph22: 0,
                pph22Percentage: 0,
                freight: {{ $pi->Freight }},
                invType: '{{ $pi->InvoiceType }}',
                vatType: '{{ $pi->VAT }}',
                vatP: '{{ $vatP ?? 11 }}',
                pickedVat: '{{ $vat ?? 'N' }}',
                initiateDP: false,

                currencyID: '{{ $pi->CurrencyID }}',

                //DP
                dpInv: '{{ $pi->InvoiceType == 'DOWN_PAYMENT' ? $pi->dt3[0]->ReffNumber : '' }}',
                poDate: '',
                poAmount: '',
                dp: '{{ $pi->InvoiceType == 'DOWN_PAYMENT' ? $pi->dt3[0]->Amount : '0' }}',
                dpP: 0,

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
                        $('#SupplierID').val(result.supplier).trigger('change');
                        $('#CurrencyID').val(result.currency).trigger('change');
                        //$('#Rate').val(result.rate).trigger('change');
                        $('#DueDate').val(result.due);

                        if(app.poAmount != 0){
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
                            $('#SupplierID').val(result.supplier).trigger('change');
                            $('#CurrencyID').val(result.currency).trigger('change');
                            $('#Rate').val(result.rate).trigger('change');
                            $('#DueDate').val(result.due);

                            app.details = app.details.concat(result.data);
                            app.pickedPO = result.po;
                        }
                        else {
                            One.helpers('jq-notify', {
                                type: 'warning',
                                icon: '',
                                message: "Picked GR doesn't have the same PO Number as the already picked GR!"
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
                        }
                    }).then(function (result){
                        app.reffs.push(result.data);
                    }).always(function (){
                        app.loadPI = false;
                        $('#PickPI').val('').trigger('change');
                    });
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
                getDP(){
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
                    }

                    return this.dpValue;
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
                    var vat = 0;
                    if(this.vatType != 'N'){
                        if(this.invType == 'DOWN_PAYMENT'){
                            vat = this.subTotal * this.vatP / 100;
                        }
                        else {
                            if(this.dpValue > 0){
                                vat = Math.round(this.subTotal - this.dpValue) * this.vatP / 100;
                            }
                            else {
                                for(var i = 0; i < this.details.length; i++){
                                    vat += (this.details[i].subtotal * this.details[i].vat/100);
                                }
                            }
                        }
                    }

                    return vat;
                },
                getGrandTotal(){
                    if(this.invType == 'DOWN_PAYMENT'){
                        return this.subTotal - this.serviceTax + this.pph22 + this.getVAT + this.freight;
                    }

                    return this.subTotal - this.dpValue - this.serviceTax + this.pph22 + this.getVAT + this.freight;
                },
            },
            watch: {
                // subTotal(){
                //     if(this.init){
                //         this.serviceTax = this.serviceP / 100 * this.subTotal;
                //         this.pph22 = this.pph22Percentage / 100 * this.subTotal;
                //     }
                // },
                // dp(){
                //     if(this.editType == 'dp'){
                //         this.dpP = (this.dp / this.poAmount) * 100;
                //     }
                // },
                // dpP(){
                //     if(this.editType == 'dpP'){
                //         this.dp = (this.dpP / 100) * this.poAmount;
                //     }
                // },
                serviceP(){
                    this.serviceTax = this.serviceP / 100 * this.subTotal;
                },
                serviceTax(){
                    this.serviceP = this.serviceTax / this.subTotal * 100;
                },
                pph22() {
                    this.pph22Percentage = this.pph22 / this.subTotal * 100;
                },
                pph22Percentage(){
                    this.pph22 = this.pph22Percentage / 100 * this.subTotal;
                }
            },
            mounted() {
                let app = this;

                app.getPOInfo(this.dpInv);

{{--                @if($pi->InvoiceType == 'DOWN_PAYMENT')--}}
{{--                    console.log('Masuk');--}}
{{--                    $("#pi-form #DPReff").trigger('change');--}}
{{--                @endif--}}

            }
        });


        $('.form-select2').select2({
            theme: 'bootstrap-5'
        });

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
        //     decimalPlaces: 0,
        //     minimumValue: 1,
        //     digitGroupSeparator: '.',
        //     decimalCharacter: ',',
        //     allowDecimalPadding: "false",
        //     modifyValueOnWheel: false,
        //     unformatOnSubmit: true
        // });

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
