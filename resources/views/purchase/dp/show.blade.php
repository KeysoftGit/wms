@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Direct Purchase - {{ $dp->TransactionNo }}</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <div class="d-flex flex-row align-items-center mb-5">
            <a href="{{ route('dp') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
            <h1 class="h3 fw-bold ms-4 mb-0">{{ $dp->TransactionNo }}</h1>
        </div>


        <div class="row justify-content-between mb-3 align-items-end">
            <div class="col-auto">
                @if(!request()->has('br') && $dp->Editable == 1 && auth()->user()->hasAnyPermission(['admin', 'dp.edit']))
                    <a href="{{ route('dp.edit', $dp->id) }}" class="btn btn-primary fs-6"><i class="fa fa-fw fa-edit"></i> Edit</a>
                @endif
            </div>

            <div class="col-auto">
                <form autocomplete="off" action="{{ config('app.report_url') }}/print/index">
                    <input type="hidden" name="transactionCode" value="{{ $dp->TransactionNo }}">
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
            <div class="block-content p-4">
                <div class="row">
                    <div class="col-lg-6 col-12 pe-lg-5">
                        <div class="mb-3">
                            <label class="form-label">Transaction No</label>
                            <input type="text" name="TransactionNo" id="TransactionNo" class="form-control" value="{{ $dp->TransactionNo }}" readonly>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Transaction Date</label>
                                <input type="text" class="form-control" value="{{ old('TransactionDate') ?? ($dp->TransactionDate != null ? date('d/m/Y', strtotime($dp->TransactionDate)) : '') }}" readonly>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Due Date</label>
                                <input type="text" class="form-control" value="{{ old('DueDate') ?? ($dp->DueDate != null ? date('d/m/Y', strtotime($dp->DueDate)) : '') }}" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Division</label>
                            <input type="text" class="form-control" value="{{ $dp->DivisionID . ($dp->division->DivisionName ? ' - ' . $dp->division->DivisionName : '') }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Supplier</label>
                            <input type="text" class="form-control" value="{{ $dp->SupplierID . ($dp->supplier->SupplierName ? ' - ' . $dp->supplier->SupplierName : '') }}" readonly>
                        </div>
                    </div>

                    <div class="col-lg-6 col-12 ps-lg-5">
                        <div class="mb-3">
                            <label class="form-label">Currency</label>
                            <input type="text" class="form-control" value="{{ $dp->CurrencyID . ($dp->currency->CurrencyName ? ' - ' . $dp->currency->CurrencyName : '') }}" readonly>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Rate</label>
                                <input type="text" class="form-control number-input" value="{{ $dp->Rate, }}" readonly>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Fiscal Rate</label>
                                <input type="text" class="form-control number-input" value="{{ $dp->FiscalRate }}" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">VAT</label>
                            @if($dp->VAT == 'N')
                                <input type="text" class="form-control" value="None" readonly>
                            @elseif($dp->VAT == 'E')
                                <input type="text" class="form-control" value="Exclude" readonly>
                            @else
                                <input type="text" class="form-control" value="Include" readonly>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="block block-rounded">
            <div class="block-content">
                <h5 class="mb-3">Purchase Detail</h5>

                <div class="table-responsive w-100">
                    <table class="table table-bordered nowrap w-100" style="table-layout: fixed; overflow-x: scroll">
                        <thead>
                        <tr>
                            <th style="width: 300px;">Part</th>
                            <th style="width: 200px;">Qty</th>
                            <th style="width: 200px;">Unit</th>
                            <th style="width: 200px;">Conversion</th>
                            <th style="width: 200px;">Price</th>
                            <th style="width: 200px;">Discount 1 %</th>
                            <th style="width: 200px;">Discount 1</th>
                            <th style="width: 200px;">Discount 2 %</th>
                            <th style="width: 200px;">Discount 2</th>
                            <th style="width: 200px;">Total Price</th>
                            <th style="width: 200px;">Division</th>
                            <th style="width: 200px;">Warehouse</th>
                            <th style="width: 100px;">Image</th>
                            <th v-for="(n, i) in revCount" style="width: 250px;">
                                <span v-if="revData[i].name != null && revData[i].name != ''">@{{ revData[i].name }}</span>
                                <span v-else>Rev @{{ n }}</span>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(detail, index) in details">
                            <td>
                                <input class="form-control" v-model="detail.part" readonly>
                            </td>
                            <td>
                                <vue-autonumeric :options="autonumericFormat2" name="qty[]" class="form-control" v-model="detail.qty" :prevalue="detail.qty" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <input class="form-control" v-model="detail.unit" readonly>
                            </td>
                            <td>
                                <vue-autonumeric :options="autonumericFormat" class="form-control" v-model="detail.conversion" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <vue-autonumeric :options="autonumericFormat2" v-model="detail.price" class="form-control" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <vue-autonumeric :options="autonumericFormat2" v-model="detail.discount1p" class="form-control" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <vue-autonumeric :options="autonumericFormat2" v-model="detail.discount1" class="form-control" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <vue-autonumeric :options="autonumericFormat2" v-model="detail.discount2p" class="form-control" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <vue-autonumeric :options="autonumericFormat2" v-model="detail.discount2" class="form-control" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <vue-autonumeric :options="autonumericFormat2" class="form-control" :value="totalPrice(index)" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <input class="form-control" v-model="detail.division" readonly>
                            </td>
                            <td>
                                <input class="form-control" v-model="detail.warehouse" readonly>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-alt-secondary" v-if="detail.image != ''" @click="viewImage(index)"><i class="fa fa-fw fa-eye"></i></button>
                                <button type="button" class="btn btn-alt-secondary" disabled v-else><i class="fa fa-fw fa-eye"></i></button>
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
                                        <td class="fw-bold">Tax Service</td>
                                        <td class="px-2">
                                            <div class="d-flex flex-row align-items-center">
                                                <vue-autonumeric :options="autonumericFormat2" type="text" v-model="pph" class="form-control number-input" style="width: 50px" readonly></vue-autonumeric>
                                                <p class="mb-0 ms-1 fs-6">%</p>
                                            </div>
                                        </td>
                                        <td>
                                            <vue-autonumeric :options="getFormat()" class="form-control text-end" v-model="serviceTax" readonly></vue-autonumeric>
                                        </td>
                                    </tr>
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
                                            <vue-autonumeric :options="autonumericFormat" name="Freight" class="form-control text-end" v-model="freight" readonly=""></vue-autonumeric>
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

        @include('purchase.po.img_modal')
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
                revData: @json($revData),
                details: @json($details),
                revCount: {{ $dp->RevCount }},
                selectedImg: '',
                subTotal: 0,
                serviceTax: '{{ $dp->ServiceTax }}',
                pph: '{{ $dp->PercentageServiceTax }}',
                freight: {{ $dp->Freight }},
                vatType: '{{ $dp->VAT }}',

                currencyID: '{{ $dp->CurrencyID }}',

                autonumericFormat: {
                    minimumValue: '0',
                    maximumValue: '9999999999999',
                    decimalPlaces: '0',
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
                getFormat(){
                    if(this.currencyID == "IDR"){
                        return this.autonumericFormat;
                    }

                    return this.autonumericFormat2
                },

                totalPrice(index){
                    var price = parseFloat(this.details[index].price);
                    var total = price;

                    if(this.details[index].discount1p != '' && this.details[index].discount1p != '0.00'){
                        let discount = total * parseFloat(this.details[index].discount1p) / 100;
                        total = total - discount;
                    }

                    if(this.details[index].discount1 != '' && this.details[index].discount1 != '0.00'){
                        total = total - parseFloat(this.details[index].discount1);
                    }

                    if(this.details[index].discount2p != '' && this.details[index].discount2p != '0.00'){
                        let discount = total * parseFloat(this.details[index].discount2p) / 100;
                        total = total - discount;
                    }

                    if(this.details[index].discount2 != '' && this.details[index].discount2 != '0.00'){
                        total = total - parseFloat(this.details[index].discount2);
                    }

                    let totalRaw = parseFloat(this.details[index].qty) * total;

                    if(this.vatType == 'I'){
                        let temp = total / (1 + (this.details[index].vat/100));
                        let tax = temp * this.details[index].vat/100;
                        total = total - tax;
                    }

                    this.details[index].totalPrice = parseFloat(this.details[index].qty) * total;

                    return totalRaw;
                },

                viewImage(index){
                    this.selectedImg = this.details[index].image;
                    $('#imgModal').modal('show');
                },
            },
            computed: {
                spanCount(){
                    return (this.revCount + 16);
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
                    for(var i = 0; i < this.details.length; i++){
                        this.subTotal += this.details[i].totalPrice;
                    }

                    return this.subTotal;
                },
                getServiceTax(){
                    if(this.serviceTax != ''){
                        return this.subTotal * parseFloat(this.serviceTax) / 200;
                    }

                    return 0;
                },
                getPPH(){
                    if(this.pph != ''){
                        return this.subTotal * parseFloat(this.pph) / 200;
                    }

                    return 0;
                },
                getVAT(){
                    var vat = 0;
                    if(this.vatType != 'N'){
                        for(var i = 0; i < this.details.length; i++){
                            if(this.vatType == 'E'){
                                vat += (this.details[i].totalPrice * this.details[i].vat / 100);
                            }
                            else if(this.vatType == 'I'){
                                vat += (this.details[i].totalPrice * this.details[i].vat / 100);
                            }
                        }
                    }

                    return vat;
                },
                getGrandTotal(){
                    return this.subTotal - this.serviceTax + this.getVAT + this.freight;
                },
            },
            // watch: {
            //     pph(){
            //         this.serviceTax = this.pph / 100 * this.subTotal;
            //     },
            //     serviceTax(){
            //         this.pph = this.serviceTax / this.subTotal * 100;
            //     },
            // },
            mounted() {
            }
        });

        let numeric = new AutoNumeric.multiple('.number-input', {
            decimalPlaces: 6,
            minimumValue: 0,
            allowDecimalPadding: "false",
            modifyValueOnWheel: false,
            digitGroupSeparator: '.',
            decimalCharacter: ',',
            unformatOnSubmit: true
        });
    </script>
@endsection
