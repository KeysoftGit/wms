@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Add Vendor Payment</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <form autocomplete="off" method="post" enctype="multipart/form-data" action="{{ route('vp.store') }}" id="pr-form">
            @csrf

            <div class="d-flex flex-row align-items-center mb-5">
                <a href="{{ route('vp') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                <h1 class="h3 fw-bold ms-4 mb-0">
                    Add Vendor Payment
                </h1>
            </div>

            @if(count($errors->all()) > 0)
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <p class="m-0 fs-6">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="block block-rounded">
                <div class="block-content pb-3">
                    <button type="submit" class="btn btn-primary mb-3 fs-6"><i class="fa fa-fw fa-save me-2"></i>Save</button>

                    <div class="row">
                        <div class="col-lg-6 col-12 pe-lg-5">
                            <div class="row align-items-center mb-3">
                                <div class="col-lg-3 col-12 mb-lg-0 mb-3">
                                    <label class="form-label">Transaction No <span class="text-danger">*</span></label>
                                    <input type="text" name="TransactionNo" id="TransactionNo" class="form-control" value="{{ old('TransactionNo') ?? '' }}" required {{ old('automatic') ? 'disabled' : (count($errors->all()) > 0 ? '' : 'disabled') }}>
                                </div>
                                <div class="col-lg-auto col-12 mb-lg-0 mb-3">
                                    <label class="form-label"></label>
                                    <div class="form-check pt-lg-2">
                                        <input class="form-check-input fs-6" type="checkbox" value="1" name="automatic" id="automatic" {{ old('automatic') ? 'checked' : (count($errors->all()) > 0 ? '' : 'checked') }}>
                                        <label class="form-check-label fs-6" for="automatic">
                                            Automatic
                                        </label>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-12">
                                    <label class="form-label">Transaction Date <span class="text-danger">*</span></label>
                                    <input type="text" class="js-flatpickr form-control js-flatpickr-enabled flatpickr-input active" id="TransactionDate" name="TransactionDate" placeholder="d/m/Y" value="{{ old('TransactionDate') ?? '' }}" readonly="readonly" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Supplier <span class="text-danger">*</span></label>
                                <select2 url="{{ route('misc.supplier', ['select2' => true]) }}" v-model="supplier" :prevalue="supplier" class="form-select"
                                         name="SupplierID" id="SupplierID" required>
                                    <option value="">-</option>
                                </select2>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Currency <span class="text-danger">*</span></label>
                                <select2 url="{{ route('misc.currency', ['select2' => true]) }}" class="form-select"  v-model="currency" :prevalue="currency"
                                         name="CurrencyID" id="CurrencyID" required>
                                    <option value="">-</option>
                                </select2>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <label class="form-label">Rate <span class="text-danger">*</span></label>
                                    <vue-autonumeric class="form-control" :options="autonumericFormat2" type="text"
                                                     name="Rate" id="Rate" v-model="rate"></vue-autonumeric>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">3rd  Rate <span class="text-danger">*</span></label>
                                    <vue-autonumeric class="form-control" :options="autonumericFormat2" type="text"
                                                     name="FiscalRate" id="FiscalRate" v-model="fiscalRate"></vue-autonumeric>
                                </div>
                            </div>
                        </div>


                        <div class="col-lg-6 col-12 ps-lg-5">
                            <div class="mb-3">
                                <label class="form-label">Bank Account No <span class="text-danger">*</span></label>
                                <select2 url="{{ route('misc.account', ['select2' => true, 'type' => 'CASHANDBANK', 'parent' => true, 'header' => true]) }}" v-model="bankAccount" :prevalue="bankAccount" class="form-select"
                                         id="BankAccountNo" name="BankAccountNo" placeholder="Pick Account" required>
                                    <option>-</option>
                                </select2>
                            </div>
                            <div class="row mb-3">
                                <div class="col-7">
                                    <label class="form-label">Cheque Number</label>
                                    <input type="text" class="form-control" id="ChequeNumber" name="ChequeNumber" value="{{ old('ChequeNumber') ?? '' }}">
                                </div>
                                <div class="col-5">
                                    <label class="form-label">Cheque Due Date</label>
                                    <input type="text" class="js-flatpickr form-control js-flatpickr-enabled flatpickr-input active" id="ChequeDueDate" name="ChequeDueDate" placeholder="d/m/Y" value="{{ old('ChequeDueDate') ?? '' }}" readonly="readonly">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Cheque Bank</label>
                                <input type="text" class="form-control" id="ChequeBank" name="ChequeBank" value="{{ old('ChequeBank') ?? '' }}">
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="block block-rounded">
                <div class="block-content">
                    <div class="d-flex flex-row justify-content-between align-items-center mb-3">
                        <h5 class="mb-3">Invoice</h5>
                        <div class="d-flex flex-row">
                            <p class="mb-0 me-2" v-if="isLoading"><i class="fa fa-fw fa-spin fa-circle-notch"></i></p>
                            <div style="width: 400px !important;">
                                <select2 :url="url" class="form-select pickInv" id="Invoice"
                                         placeholder="Pick Invoice" :disabled="isLoading || (supplier == '' || currency == '')">
                                    <option>-</option>
                                </select2>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive w-100" style="overflow-x: scroll">
                        <table class="table table-bordered nowrap w-100" style="table-layout: fixed; overflow-x: scroll">
                            <thead>
                            <tr>
                                <th style="width: 65px"></th>
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
                            <tr>
                                <td colspan="15" class="text-center p-3" v-if="details.length == 0">No Invoice Added Yet</td>
                            </tr>
                            <tr v-for="(detail, index) in details" :key="detail.invoice">
                                <td>
                                    <button type="button" class="btn btn-danger" @click="deleteDetail(detail)"><i class="fa fa-fw fa-trash-can"></i></button>
                                </td>
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
                                    <vue-autonumeric class="form-control auto-format" :options="getFormat()" type="text" v-model="detail.amount" readonly></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric class="form-control auto-format" :options="getFormat()" name="remain[]" type="text" v-model="detail.remain" readonly></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric class="form-control" :options="getFormat()" type="text" name="payment[]" v-model="detail.payment"></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric class="form-control auto-format" :options="getFormat()" type="text" name="bank[]" :value="getBank(index)" readonly=""></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric class="form-control" :options="getFormat()" type="text" :value="getGainLoss(index)" readonly></vue-autonumeric>
                                </td>
                                <td>
                                    <select2 url="{{ route('misc.account', ['select2' => true, 'parent' => true]) }}" v-model="detail.account1" :prevalue="detail.account1" class="form-select"
                                             name="account1[]" placeholder="Pick Account">
                                        <option>-</option>
                                    </select2>
                                </td>
                                <td>
                                    <vue-autonumeric class="form-control" :options="getFormat()" type="text" name="accAmount1[]" v-model="detail.accAmount1"></vue-autonumeric>
                                </td>
                                <td>
                                    <select2 url="{{ route('misc.account', ['select2' => true, 'parent' => true]) }}" v-model="detail.account2" :prevalue="detail.account2" class="form-select"
                                             name="account2[]" placeholder="Pick Account">
                                        <option>-</option>
                                    </select2>
                                </td>
                                <td>
                                    <vue-autonumeric class="form-control" :options="getFormat()" type="text" name="accAmount2[]" v-model="detail.accAmount2"></vue-autonumeric>
                                </td>
                                <td>
                                    <select2 url="{{ route('misc.account', ['select2' => true, 'parent' => true]) }}" v-model="detail.account3" :prevalue="detail.account3" class="form-select"
                                             name="account3[]" placeholder="Pick Account">
                                        <option>-</option>
                                    </select2>
                                </td>
                                <td>
                                    <vue-autonumeric class="form-control" :options="getFormat()" type="text" name="accAmount3[]" v-model="detail.accAmount3"></vue-autonumeric>
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
                            <textarea name="Notes" class="form-control" rows="3">{{ old('Notes') ?? '' }}</textarea>
                        </div>

                        <div class="col-auto">
                            <table>
                                <tr>
                                    <td class="fw-bold">Total Amount</td>
                                    <td style="width: 10px;"></td>
                                    <td>
                                        <vue-autonumeric :options="getFormat()" name="GrandTotal" class="form-control text-end auto-format" :value="getAmount" readonly></vue-autonumeric>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Total remain</td>
                                    <td style="width: 10px;"></td>
                                    <td>
                                        <vue-autonumeric :options="getFormat()" name="GrandTotal" class="form-control text-end auto-format" :value="getRemain" readonly></vue-autonumeric>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Total Pay</td>
                                    <td style="width: 10px;"></td>
                                    <td>
                                        <vue-autonumeric :options="autonumericFormatMinus" name="GrandTotal" class="form-control text-end auto-format" :value="getGrandTotal" readonly></vue-autonumeric>
                                    </td>
                                </tr>
                            </table>
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
                isLoading: false,
                url: '{!! route('vp.inv', ['supplier' => old('SupplierID') ?? '0', 'currency' => old('CurrencyID') ?? '0']) !!}',

                supplier: '{{ old('SupplierID') ?? '' }}',
                currency: '{{ old('CurrencyID') ?? '' }}',
                rate: {!! old('Rate') ?? 1 !!},
                fiscalRate: {!! old('FiscalRate') ?? 1 !!},
                bankAccount: '{{ old('BankAccountNo') ?? '' }}',

                details: [],
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

                getDetail(inv){
                    this.isLoading = true;
                    let app = this;

                    $.ajax({
                        url: '{{ route('vp.inv.detail') }}',
                        type: 'GET',
                        data: {
                            inv: inv,
                        }
                    }).then(function (result){
                        if(result.success){
                            app.details = app.details.concat(result.data);
                        }
                        else {
                            One.helpers('jq-notify', {
                                type: 'warning',
                                icon: '',
                                message: result.message
                            });
                        }
                    }).always(function (){
                        app.isLoading = false;
                        $('#Invoice').val('').trigger('change');
                    });
                },

                getBank(index){
                    let item = this.details[index];
                    return item.payment * this.fiscalRate;
                },

                getGainLoss(index){
                    let item = this.details[index];
                    return (item.payment - this.getBank(index)) * this.rate;
                },

                deleteDetail(item){
                    //this.details.splice(index, 1);
                    this.details = this.details.filter(function (x) { return x !== item; });
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
            watch: {
                supplier(){
                    this.details = [];
                    this.url = '{{ route('vp.inv') }}?supplier=' + this.supplier + '&currency=' + this.currency;
                },
                currency(){
                    setTimeout(function (){
                        $('.auto-format').attr('readonly', true);
                    }, 1);
                    this.details = [];
                    this.url = '{{ route('vp.inv') }}?supplier=' + this.supplier + '&currency=' + this.currency;
                },
            },
            mounted() {
                let app = this;

                $('#Invoice').on('change', function (){
                    if($(this).val() != null && $(this).val() != ''){
                        var check = false;
                        for(var i = 0; i < app.details.length;i++){
                            if(app.details[i].invoice == $(this).val()){
                                check = true;
                                break;
                            }
                        }

                        if(!check){
                            app.getDetail($(this).val());
                        }
                        else {
                            One.helpers('jq-notify', {
                                type: 'warning',
                                icon: '',
                                message: 'Invoice is already on the list!'
                            });
                            $(this).val('').trigger('change');
                        }
                    }
                });
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
            defaultDate: "today"
        });
        $('.js-flatpickr:visible').on('focus', function () {
            $(this).blur()
        });
        $('.js-flatpickr:visible').prop('readonly', false);






    </script>
@endsection
