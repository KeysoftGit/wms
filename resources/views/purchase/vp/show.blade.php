@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Vendor Payment - {{ $vp->TransactionNo }}</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">

        <div class="d-flex flex-row align-items-center mb-5">
            <a href="{{ route('vp') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
            <h1 class="h3 fw-bold ms-4 mb-0">
                {{ $vp->TransactionNo }}
            </h1>
        </div>

        <div class="row justify-content-between mb-3 align-items-end">
            @if(!request()->has('br') && $vp->Editable == 1 && auth()->user()->hasAnyPermission(['admin', 'vp.edit']))
                <div class="col-auto d-flex flex-row">
                    <a href="{{ route('vp.edit', $vp->id) }}" class="btn btn-primary fs-6"><i class="fa fa-fw fa-edit"></i> Edit</a>
                </div>
            @endif

            <div class="col-auto">
                <form autocomplete="off" action="{{ config('app.report_url') }}/print/index">
                    <input type="hidden" name="transactionCode" value="{{ $vp->TransactionNo }}">
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
                <div class="row">
                    <div class="col-lg-6 col-12 pe-lg-5">
                        <div class="d-flex flex-row align-items-center mb-3">
                            <div>
                                <label class="form-label">Transaction No</label>
                                <input type="text" name="TransactionNo" id="TransactionNo" class="form-control" value="{{ $vp->TransactionNo }}" readonly>
                            </div>
                            <div class="ms-4">
                                <label class="form-label">Transaction Date</label>
                                <input type="text" class="form-control" id="TransactionDate" name="TransactionDate" placeholder="d/m/Y" value="{{ date('d/m/Y', strtotime($vp->TransactionDate)) }}" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Supplier</label>
                            <input type="text" class="form-control" value="{{ $vp->SupplierID . ($vp->supplier->SupplierName ? ' - ' . $vp->supplier->SupplierName : '') }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Currency</label>
                            <input type="text" class="form-control" value="{{ $vp->CurrencyID . ($vp->currency->CurrencyName ? ' - ' . $vp->currency->CurrencyName : '') }}" readonly>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Rate</label>
                                <vue-autonumeric class="form-control" :options="autonumericFormat2" type="text"
                                                 name="Rate" id="Rate" v-model="rate" readonly></vue-autonumeric>
                            </div>
                            <div class="col-6">
                                <label class="form-label">3rd  Rate</label>
                                <vue-autonumeric class="form-control" :options="autonumericFormat2" type="text"
                                                 name="FiscalRate" id="FiscalRate" v-model="fiscalRate" readonly></vue-autonumeric>
                            </div>
                        </div>
                    </div>


                    <div class="col-lg-6 col-12 ps-lg-5">
                        <div class="mb-3">
                            <label class="form-label">Bank Account No</label>
                            <input type="text" class="form-control" value="{{ $vp->account->AccountNo . ($vp->account->AccountName != null ? ' - ' . $vp->account->AccountName : '') }}" readonly>
                        </div>
                        <div class="row mb-3">
                            <div class="col-7">
                                <label class="form-label">Cheque Number</label>
                                <input type="text" class="form-control" id="ChequeNumber" name="ChequeNumber" value="{{ $vp->ChequeNumber }}" readonly>
                            </div>
                            <div class="col-5">
                                <label class="form-label">Cheque Due Date</label>
                                <input type="text" class="form-control" id="ChequeDueDate" name="ChequeDueDate" value="{{ $vp->ChequeDueDate ? date('d/m/Y', strtotime($vp->ChequeDueDate)) : '' }}" readonly=>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cheque Bank</label>
                            <input type="text" class="form-control" id="ChequeBank" name="ChequeBank" value="{{ $vp->ChequeBank }}" readonly>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-content">
                <div class="d-flex flex-row justify-content-between align-items-center mb-3">
                    <h5 class="mb-3">Invoice</h5>
                </div>

                <div class="table-responsive w-100" style="overflow-x: scroll">
                    <table class="table table-bordered nowrap w-100" style="table-layout: fixed; overflow-x: scroll">
                        <thead>
                        <tr>
                            <th style="width: 200px;">Invoice Number</th>
                            <th style="width: 150px;">Invoice Date</th>
                            <th style="width: 150px;">Due Date</th>
                            <th style="width: 200px;">Amount</th>
                            <th style="width: 200px;">Remain Amount</th>
                            <th style="width: 200px;">Payment Amount</th>
                            <th style="width: 200px;">Bank Amount</th>
                            <th style="width: 200px;">Gain Loss ExRate</th>
                            <th style="width: 200px;">Account1</th>
                            <th style="width: 200px;">AccountAmount1</th>
                            <th style="width: 200px;">Account2</th>
                            <th style="width: 200px;">AccountAmount2</th>
                            <th style="width: 200px;">Account3</th>
                            <th style="width: 200px;">AccountAmount3</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(detail, index) in details">
                            <td>
                                <input type="hidden" name="division[]" :value="detail.division">
                                <input type="text" class="form-control" :value="detail.invoice" name="invoice[]" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control" :value="detail.date" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="due[]" :value="detail.due" readonly>
                            </td>
                            <td>
                                <vue-autonumeric class="form-control" :options="getFormat()" type="text" v-model="detail.amount" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <vue-autonumeric class="form-control" :options="getFormat()" name="remain[]" type="text" v-model="detail.remain" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <vue-autonumeric class="form-control" :options="getFormat()" type="text" name="payment[]" v-model="detail.payment" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <vue-autonumeric class="form-control" :options="getFormat()" type="text" name="bank[]" :value="getBank(index)" readonly=""></vue-autonumeric>
                            </td>
                            <td>
                                <vue-autonumeric class="form-control" :options="getFormat()" type="text" :value="getGainLoss(index)" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <input type="text" class="form-control" :value="detail.account1" readonly>
                            </td>
                            <td>
                                <vue-autonumeric class="form-control" :options="autonumericFormatMinus" type="text" name="accAmount1[]" v-model="detail.accAmount1" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <input type="text" class="form-control" :value="detail.account2" readonly>
                            </td>
                            <td>
                                <vue-autonumeric class="form-control" :options="getFormat()" type="text" name="accAmount2[]" v-model="detail.accAmount2" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <input type="text" class="form-control" :value="detail.account3" readonly>
                            </td>
                            <td>
                                <vue-autonumeric class="form-control" :options="getFormat()" type="text" name="accAmount3[]" v-model="detail.accAmount3" readonly></vue-autonumeric>
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
                        <textarea name="Notes" class="form-control" rows="3" readonly>{{ $vp->Notes }}</textarea>
                    </div>

                    <div class="col-auto">
                        <table>
                            <tr>
                                <td class="fw-bold">Total Amount</td>
                                <td style="width: 10px;"></td>
                                <td>
                                    <vue-autonumeric :options="getFormat()" name="GrandTotal" class="form-control text-end" :value="getAmount" readonly></vue-autonumeric>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Total remain</td>
                                <td style="width: 10px;"></td>
                                <td>
                                    <vue-autonumeric :options="getFormat()" name="GrandTotal" class="form-control text-end" :value="getRemain" readonly></vue-autonumeric>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Total Pay</td>
                                <td style="width: 10px;"></td>
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
                isLoading: false,

                rate: {!! $vp->Rate !!},
                fiscalRate: {!! $vp->FiscalRate !!},
                currency: '{{ $vp->CurrencyID }}',
                details: @json($details),
                grandTotal: 0,

                autonumericFormat: {
                    decimalPlaces: 6,
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
                autonumericFormatMinus: {
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
                    if(this.currency == "IDR"){
                        return this.autonumericFormat;
                    }

                    return this.autonumericFormatMinus;
                },

                getBank(index){
                    let item = this.details[index];
                    return item.payment * this.fiscalRate;
                },

                getGainLoss(index){
                    let item = this.details[index];
                    return (item.payment - this.getBank(index)) * this.rate;
                },
            },
            computed: {
                getAmount(){
                    var total = 0;
                    for(var i = 0; i < this.details.length; i++){
                        total += this.details[i].remain;
                    }

                    return total;
                },
                getRemain(){
                    var remain = 0;
                    for(var i = 0; i < this.details.length; i++){
                        remain += (this.details[i].remain - this.details[i].payment);
                    }

                    return remain;
                },
                getGrandTotal(){
                    this.grandTotal = 0;
                    for(var i = 0; i < this.details.length; i++){
                        this.grandTotal += (this.details[i].payment + this.details[i].accAmount1 + this.details[i].accAmount2 + this.details[i].accAmount3);
                    }

                    return this.grandTotal;
                },
            },
            mounted() {

            }
        });






    </script>
@endsection
