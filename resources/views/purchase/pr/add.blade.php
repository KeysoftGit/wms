@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Add Purchase Return</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <form autocomplete="off" method="post" enctype="multipart/form-data" action="{{ route('pr.store') }}" id="pr-form">
            @csrf

            <div class="d-flex flex-row align-items-center mb-5">
                <a href="{{ route('pr') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                <h1 class="h3 fw-bold ms-4 mb-0">
                    Add Purchase Return
                </h1>
            </div>

            @if(count($errors->all()) > 0)
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <p class="m-0 fs-6">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <input type="hidden" name="rev" v-model="revCount">

            <div class="block block-rounded">
                <div class="block-content pb-3">
                    <button type="submit" class="btn btn-primary mb-3 fs-6"><i class="fa fa-fw fa-save me-2"></i>Save</button>

                    <div class="row align-items-center mb-3">
                        <div class="col-lg-2 col-12 mb-lg-0 mb-3">
                            <label class="form-label">Transaction No <span class="text-danger">*</span></label>
                            <input type="text" name="TransactionNo" id="TransactionNo" class="form-control" value="{{ old('TransactionNo') ?? '' }}" required {{ old('automatic') ? 'disabled' : (count($errors->all()) > 0 ? '' : 'disabled') }}>
                        </div>
                        <div class="col-lg-auto col-12 mb-lg-0 mb-3">
                            <label class="form-label"></label>
                            <div class="form-check ms-lg-4 pt-lg-2">
                                <input class="form-check-input fs-6" type="checkbox" value="1" name="automatic" id="automatic" {{ old('automatic') ? 'checked' : (count($errors->all()) > 0 ? '' : 'checked') }}>
                                <label class="form-check-label fs-6" for="automatic">
                                    Automatic
                                </label>
                            </div>
                        </div>
                        <div class="col-lg-auto col-12 ms-lg-4">
                            <label class="form-label">Transaction Date <span class="text-danger">*</span></label>
                            <input type="text" class="js-flatpickr form-control js-flatpickr-enabled flatpickr-input active" id="TransactionDate" name="TransactionDate" placeholder="d/m/Y" value="{{ old('TransactionDate') ?? '' }}" readonly="readonly" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-12 pe-lg-5">
                            <div v-if="prType == 'N'">
                                <div class="mb-3">
                                    <label class="form-label">Division <span class="text-danger">*</span></label>
                                    <select2 url="{{ route('misc.division2') }}" class="form-select" v-model="division" :prevalue="division"
                                             name="DivisionID" id="DivisionID" required>
                                        <option value="">-</option>
                                    </select2>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Supplier <span class="text-danger">*</span></label>
                                    <select2 url="{{ route('misc.supplier', ['select2' => true]) }}" v-model="supplier" :prevalue="supplier" class="form-select"
                                             name="SupplierID" id="SupplierID" required>
                                        <option value="">-</option>
                                    </select2>
                                </div>
                            </div>
                            <div v-else>
                                <div class="mb-3">
                                    <label class="form-label">Division <span class="text-danger">*</span></label>
                                    <input type="hidden" name="DivisionID" v-model="division">
                                    <input type="text" class="form-control" v-model="divisionName" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Supplier <span class="text-danger">*</span></label>
                                    <input type="hidden" name="SupplierID" v-model="supplier">
                                    <input type="text" class="form-control" v-model="supplierName" readonly>
                                </div>
                            </div>

                            <div class="mb-3" v-if="prType != 'GR'">
                                <label class="form-label">VAT <span class="text-danger">*</span></label>
                                <div class="space-y-2">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="VATNone" name="VAT" value="N" v-model="vatType">
                                        <label class="form-check-label" for="VATNone">None</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="VATExclude" name="VAT" value="E" v-model="vatType">
                                        <label class="form-check-label" for="VATExclude">Exclude</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="VATInclude" name="VAT" value="I" v-model="vatType">
                                        <label class="form-check-label" for="VATInclude">Include</label>
                                    </div>
                                </div>
                            </div>
                            <div v-else>
                                <input type="hidden" name="VAT" :value="vatType">
                            </div>
                        </div>
                        <div class="col-lg-6 col-12 ps-lg-5">
                            <div class="row mb-3">
                                <div class="col-6" v-if="prType == 'N'">
                                    <div class="mb-3">
                                        <label class="form-label">Currency <span class="text-danger">*</span></label>
                                        <select2 url="{{ route('misc.currency', ['select2' => true]) }}" class="form-select"  v-model="currency" :prevalue="currency"
                                                 name="CurrencyID" id="CurrencyID" required>
                                            <option value="">-</option>
                                        </select2>
                                    </div>
                                </div>
                                <div class="col-6" v-else>
                                    <div class="mb-3">
                                        <label class="form-label">Currency <span class="text-danger">*</span></label>
                                        <input type="hidden" name="CurrencyID" v-model="currency">
                                        <input type="text" class="form-control" v-model="currencyName" readonly>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Rate <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="Rate" id="Rate" v-model="rate" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Based On <span class="text-danger">*</span></label>
                                <div class="space-y-2">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="TypeGR" name="Type" value="GR" v-model="prType">
                                        <label class="form-check-label" for="VATNone">Goods Receiving</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="TypeDP" name="Type" value="DP" v-model="prType">
                                        <label class="form-check-label" for="VATExclude">Direct Purchase</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="TypeNone" name="Type" value="N" v-model="prType">
                                        <label class="form-check-label" for="VATInclude">None</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block block-rounded">
                <div class="block-content">
                    <div class="d-flex flex-row justify-content-between align-items-center mb-3">
                        <h5 class="mb-3">Detail</h5>
                        <div class="d-flex flex-row">
                            <div style="width: 250px;">
                                <select2 url="{{route('pr.gr')}}" v-model="reff" :prevalue="reff" class="form-select pickInv" name="ReceivingNumber"
                                         placeholder="Pick Goods Receiving" v-if="prType == 'GR'" required>
                                    <option>-</option>
                                </select2>
                                <select2 url="{{route('pr.dp')}}" v-model="reff" :prevalue="reff" class="form-select pickInv" name="ReceivingNumber"
                                         placeholder="Pick Direct Purchase" v-if="prType == 'DP'" required>
                                    <option>-</option>
                                </select2>
                            </div>
                            <button type="button" class="btn btn-primary" @click="addDetail" v-if="prType == 'N'"><i class="fa fa-fw fa-plus me-1"></i>Add Detail</button>
                            <button type="button" class="btn btn-secondary ms-1" @click="addRev" :disabled="details.length == 0"><i class="fa fa-fw fa-plus me-1"></i>Add Rev</button>
                            <button type="button" class="btn btn-secondary ms-1" @click="removeRev" :disabled="details.length == 0"><i class="fa fa-fw fa-minus me-1"></i>Decrease Rev</button>
                        </div>
                    </div>

                    <p class="text-center m-0 p-3" v-if="!isLoading && (reff == '' || reff == null) && prType != 'N'">Pick a Transaction First</p>
                    <h1 class="text-center" v-if="isLoading"><i class="fa fa-fw fa-circle-notch fa-spin"></i></h1>

                    <div id="detail-table" class="table-responsive w-100" style="overflow-x: scroll" v-if="!isLoading && ((reff != '' && reff != null) || prType == 'N')">
                        <table class="table table-bordered nowrap w-100" style="table-layout: fixed; overflow-x: scroll">
                            <thead>
                            <tr>
                                <th style="width: 65px" v-if="prType == 'N'"></th>
                                <th style="width: 200px;">Part</th>
                                <th style="width: 200px;">UnitID</th>
                                <th style="width: 200px;" v-if="prType != 'N'">Qty</th>
                                <th style="width: 120px;">Qty Return</th>
                                <th style="width: 200px;">Price</th>
                                <th style="width: 200px;">Total Price</th>
                                <th style="width: 200px;">Division</th>
                                <th style="width: 200px;">Warehouse</th>
                                <th v-for="(n, i) in revCount" style="width: 250px;">
                                    <span v-if="revData[i].name != null && revData[i].name != ''">@{{ revData[i].name }}</span>
                                    <span v-else>Rev @{{ n }}</span>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td :colspan="spanCount" class="text-center p-3" v-if="details.length == 0">No Details Added Yet</td>
                            </tr>
                            <tr v-for="(detail, index) in details">
                                <input type="hidden" name="sequence[]" v-model="detail.sequence">
                                <input type="hidden" name="conversion[]" v-model="detail.conversion">
                                <td v-if="prType == 'N'">
                                    <button type="button" class="btn btn-danger" @click="deleteDetail(detail)"><i class="fa fa-fw fa-trash-can"></i></button>
                                </td>
                                <td>
                                    <input type="hidden" name="part[]" v-model="detail.part_id" v-if="prType != 'N'">
                                    <input type="text" class="form-control" v-model="detail.part" readonly v-if="prType != 'N'">
                                    <select2 url="{{route('misc.part')}}" v-model="detail.part" :prevalue="detail.part" class="form-select part" name="part[]"
                                             placeholder="Pick Part" :data-index="index" required v-if="prType == 'N'">
                                        <option>-</option>
                                    </select2>
                                </td>
                                <td>
                                    <input type="hidden" name="unit[]" v-model="detail.unit_id" v-if="prType != 'N'">
                                    <input type="text" class="form-control" v-model="detail.unit" readonly v-if="prType != 'N'">
                                    <select2 :url="detail.unitUrl" v-model="detail.unit" :prevalue="detail.unit" class="form-control unit" name="unit[]" :id="'unit'+index"
                                             :disabled="detail.part == null" placeholder="Pick Unit" :data-index="index" required v-if="prType == 'N'">
                                    </select2>
                                </td>
                                <td v-if="prType != 'N'">
                                    <vue-autonumeric class="form-control" :options="autonumericFormat2" type="text" name="qty_ori[]" v-model="detail.qty_ori" readonly></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric class="form-control" :options="autonumericFormat2" type="text" name="qty[]" v-model="detail.qty"></vue-autonumeric>
                                </td>
                                <td>
                                    <input type="hidden" name="discount[]" v-model="detail.totalDiscount">
                                    <input type="hidden" name="discount1[]" v-model="detail.discount1">
                                    <input type="hidden" name="discount1p[]" v-model="detail.discount1p">
                                    <input type="hidden" name="discount2[]" v-model="detail.discount2">
                                    <input type="hidden" name="discount2p[]" v-model="detail.discount2p">
                                    <vue-autonumeric class="form-control" :class="{'auto-format': prType != 'N'}" :options="getFormat()" type="text" name="price[]" v-model="detail.price" :readonly="prType != 'N'"></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric class="form-control auto-format" :options="getFormat()" type="text" :value="totalPrice(index)" readonly=""></vue-autonumeric>
                                </td>
                                <td>
                                    <input type="hidden" name="division[]" v-model="detail.division_id" v-if="prType != 'N'">
                                    <input type="text" class="form-control" v-model="detail.division" readonly v-if="prType != 'N'">
                                    <select2 url="{{route('misc.division2')}}" v-model="detail.division" :prevalue="detail.division" class="form-select"
                                             name="division[]" placeholder="Pick Division" v-if="prType == 'N'">
                                        <option>-</option>
                                    </select2>
                                </td>
                                <td>
                                    <input type="hidden" name="warehouse[]" v-model="detail.warehouse_id" v-if="prType != 'N'">
                                    <input type="text" class="form-control" v-model="detail.warehouse" readonly v-if="prType != 'N'">
                                    <select2 url="{{route('misc.warehouse2')}}" v-model="detail.warehouse" :prevalue="detail.warehouse" class="form-select"
                                             name="warehouse[]" placeholder="Pick Warehouse" :data-index="index" v-if="prType == 'N'">
                                        <option>-</option>
                                    </select2>
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
                                    <td class="fw-bold">Grand Total</td>
                                    <td style="width: 10px;"></td>
                                    <td>
                                        <vue-autonumeric :options="getFormat()" name="GrandTotal" class="form-control text-end auto-format" :value="getGrandTotal" readonly></vue-autonumeric>
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
                isLoading: false,
                revCount: 0,
                prType: '{{ old('Type') ?? 'GR' }}',
                reff: '{{ old('ReceivingNumber') ?? '' }}',

                division: '{{ old('DivisionID') ?? '' }}',
                divisionName: '',
                supplier: '{{ old('SupplierID') ?? '' }}',
                supplierName: '',
                currency: '{{ old('CurrencyID') ?? '' }}',
                currencyName: '',
                rate: {!! old('Rate') ?? 1 !!},

                vatType: '{{ old('VAT') ?? 'N' }}',

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

                    return this.autonumericFormat2;
                },

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

                addDetail(){
                    let rev = [];
                    for(var i = 0; i < this.revCount; i++){
                        rev.push('');
                    }
                    this.details.push({
                        part: null,
                        sequence: 0,
                        vat: 0,
                        qty: 1,
                        unitUrl: '{{ route('misc.partunit2') }}?id=0',
                        unit: null,
                        conversion: 1,
                        price: 0,
                        discount1: '',
                        discount1p: '',
                        discount2: '',
                        discount2p: '',
                        totalDiscount: 0,
                        totalPrice: 0,
                        division: null,
                        warehouse: null,
                        rev: rev
                    });

                    $('#detail-table').animate({
                        scrollLeft: 0
                    }, 250);
                },

                changePart(index){
                    this.details[index].unitUrl = '{{ route('misc.partunit2') }}?id=' +  this.details[index].part;
                    this.details[index].unit = null;

                    $(`#unit${index}`).val('').trigger('change');

                    this.getContent(index);
                },

                getConversion(index){
                    let app = this;
                    $.ajax({
                        url: '{{ route('misc.conversion') }}?part_id=' + app.details[index].part + '&unit_id=' + app.details[index].unit,
                        type: 'GET'
                    }).then(function (result){
                        app.details[index].conversion = result.conversion;
                    });
                },

                getContent(index){
                    let app = this;
                    $.ajax({
                        url: '{{ route('misc.part_content') }}?id=' + app.details[index].part,
                        type: 'GET'
                    }).then(function (result){
                        app.details[index].vat = result.vat;
                    });
                },

                totalPrice(index){
                    let price = parseFloat(this.details[index].price);
                    let total = parseFloat(this.details[index].qty) * price;

                    this.details[index].totalPrice = total;

                    return total;
                },

                deleteDetail(item){
                    //this.details.splice(index, 1);
                    this.details = this.details.filter(function (x) { return x !== item; });
                },






                getDetail(){
                    this.isLoading = true;
                    let app = this;

                    var url = '';
                    if(this.prType == 'GR'){
                        url = '{{ route('pr.gr.detail') }}';
                    }
                    else if(this.prType == 'DP'){
                        url = '{{ route('pr.dp.detail') }}';
                    }

                    $.ajax({
                        url: url,
                        type: 'GET',
                        data: {
                            inv: app.reff,
                        }
                    }).then(function (result){
                        if(result.success){
                            app.revCount = result.revCount;
                            app.details = result.data;

                            app.division = result.division;
                            app.divisionName = result.division_name;
                            app.supplier = result.supplier;
                            app.supplierName = result.supplier_name;
                            app.currency = result.currency;
                            app.currencyName = result.currency_name;
                            app.rate = result.rate;
                            app.vatType = result.vat;
                        }
                    }).always(function (){
                        app.isLoading = false;
                    });
                },
            },
            computed: {
                spanCount(){
                    return (this.revCount + 8);
                },
                getGrandTotal(){
                    this.grandTotal = 0;
                    for(var i = 0; i < this.details.length; i++){
                        this.grandTotal += (this.details[i].price * this.details[i].qty);
                    }

                    return this.grandTotal;
                },
            },
            watch: {
                currency(){
                    setTimeout(function (){
                        $('.auto-format').attr('readonly', true);
                    }, 1);
                },
                prType(){
                    this.revCount = 0;
                    this.reff = '';
                    this.details = [];
                    this.division = '';
                    this.divisionName = '';
                    this.supplier = '';
                    this.supplierName = '';
                    this.currency = '';
                    this.currencyName = '';
                    this.rate = 1;
                    this.vatType = 'N';
                }
            },
            mounted() {
                let app = this;

                $('#pr-form').on('change', '.pickInv', function (){
                    app.reff = $(this).val();
                    if(app.reff != null && app.reff != ''){
                        app.getDetail();
                    }
                    else {
                        app.reff = '';
                        app.details = [];
                    }
                });

                $('#pr-form').on('change', '.part', function (){
                    if($(this).val() != null){
                        app.changePart($(this).data('index'));
                    }
                });

                $('#pr-form').on('change', '.unit', function (){
                    if($(this).val() != null){
                        app.getConversion($(this).data('index'));
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

        let rate = new AutoNumeric('#Rate', {
            decimalPlaces: 6,
            minimumValue: 1,
            digitGroupSeparator: '.',
            decimalCharacter: ',',
            allowDecimalPadding: "false",
            modifyValueOnWheel: false,
            unformatOnSubmit: true
        });






    </script>
@endsection
