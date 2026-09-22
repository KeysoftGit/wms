@extends('layouts.admin')

@section('titles')
    <title>Keyonline - Add Item Transfer Request</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <form autocomplete="off" method="post" enctype="multipart/form-data" action="{{ route('transfer_request.store') }}" id="transfer-form">
            @csrf

            <div class="d-flex flex-row align-items-center mb-5">
                <a href="{{ route('transfer_request') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                <h1 class="h3 fw-bold ms-4 mb-0">
                    Add Item Transfer Request
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
                        <div class="col-lg-8 col-12">
                            <div class="row align-items-center mb-3">
                                <div class="col-lg-3 col-12 mb-lg-0 mb-3">
                                    <label class="form-label">Transaction No <span class="text-danger">*</span></label>
                                    <input type="text" name="TransactionNo" id="TransactionNo" class="form-control" v-model="transactionNo" :disabled="automatic" required>
                                </div>
                                <div class="col-lg-auto col-12 mb-lg-0 mb-3">
                                    <label class="form-label"></label>
                                    <div class="form-check pt-lg-2">
                                        <input class="form-check-input fs-6" type="checkbox" value="1" name="automatic" id="automatic" v-model="automatic">
                                        <label class="form-check-label fs-6" for="automatic">
                                            Automatic
                                        </label>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-12">
                                    <label class="form-label">Transaction Date <span class="text-danger">*</span></label>
                                    <input type="text" class="js-flatpickr form-control" id="TransactionDate" name="TransactionDate" placeholder="d/m/Y" v-model="transactionDate" readonly="readonly" required>
                                </div>
                                <div class="col-lg-3 col-12">
                                    <label class="form-label">Expire Date</label>
                                    <input type="text" class="js-flatpickr form-control" id="ExpiredDate" name="ExpiredDate" placeholder="d/m/Y" v-model="expireDate" readonly="readonly">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-12 pe-lg-5">
                            <div class="position-relative mb-3 pt-2">
                                <p class="position-absolute bg-white px-1 form-label" style="top: 0; left: 10px;">Transfer From</p>
                                <div class="border border-light rounded p-3">
                                    <div class="mb-3">
                                        <label class="form-label">Warehouse <span class="text-danger">*</span></label>
                                        <select2 url="{{ route('misc.warehouse2', ['transfer_from' => true]) }}" v-model="fromId" :prevalue="fromId" class="form-select"
                                                 name="WarehouseIDFrom" id="WarehouseIDFrom" required>
                                            <option value="">-</option>
                                        </select2>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Staff In Charge <span class="text-danger">*</span></label>
                                        <select2 url="{{ route('misc.employee', ['select2' => true]) }}" class="form-select"  v-model="staffFrom" :prevalue="staffFrom"
                                                 name="StaffInChargeIDFrom" id="StaffInChargeIDFrom" required>
                                            <option value="">-</option>
                                        </select2>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="col-lg-6 col-12 ps-lg-5">
                            <div class="position-relative mb-3 pt-2">
                                <p class="position-absolute bg-white px-1 form-label" style="top: 0; left: 10px;">Transfer To</p>
                                <div class="border border-light rounded p-3">
                                    <div class="mb-3">
                                        <label class="form-label">Warehouse <span class="text-danger">*</span></label>
                                        <select2 url="{{ route('misc.warehouse2') }}" v-model="toId" :prevalue="toId" class="form-select"
                                                 name="WarehouseIDTo" id="WarehouseIDTo" required>
                                            <option value="">-</option>
                                        </select2>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Staff In Charge <span class="text-danger">*</span></label>
                                        <select2 url="{{ route('misc.employee', ['select2' => true]) }}" class="form-select"  v-model="staffTo" :prevalue="staffTo"
                                                 name="StaffInChargeIDTo" id="StaffInChargeIDTo" required>
                                            <option value="">-</option>
                                        </select2>
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
                        <h5 class="mb-3">Items</h5>
                        <div class="d-flex flex-row">
                            <button type="button" class="btn btn-primary" @click="addDetail"><i class="fa fa-fw fa-plus me-1"></i>Add Detail</button>
                        </div>
                    </div>

                    <div class="table-responsive w-100" style="overflow-x: scroll">
                        <table class="table table-bordered nowrap w-100" style="table-layout: fixed;">
                            <thead>
                            <tr>
                                <th style="width: 65px"></th>
                                <th style="width: 300px;">Part</th>
                                <th style="width: 200px;">Unit</th>
                                <th style="width: 150px;">Conversion</th>
                                <th style="width: 200px;">Qty</th>
                                <th style="width: 150px;">Stock</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-if="details.length == 0">
                                <td colspan="6" class="text-center p-3">No Item Added Yet</td>
                            </tr>
                            <tr v-for="(detail, index) in details" :key="detail.id">
                                <td>
                                    <button type="button" class="btn btn-danger" @click="deleteDetail(detail)"><i class="fa fa-fw fa-trash-can"></i></button>
                                </td>
                                <td>
                                    <select2 url="{{route('misc.part')}}" v-model="detail.part" :prevalue="detail.part" class="form-select part" name="part[]" placeholder="Pick Part" :data-index="index" required>
                                        <option>-</option>
                                    </select2>
                                </td>
                                <td>
                                    <select2 :url="detail.unitUrl" v-model="detail.unit" :prevalue="detail.unit" class="form-control unit" name="unit[]" :id="'unit'+index"
                                             :disabled="detail.part == null" placeholder="Pick Unit" :data-index="index" required>
                                    </select2>
                                </td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat" name="conversion[]" class="form-control" v-model="detail.conversion" readonly></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat2" name="qty[]" class="form-control" v-model="detail.qty" :prevalue="detail.qty" required></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat2" name="stock[]" class="form-control" :value="detail.stock" readonly></vue-autonumeric>
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
                            <label class="form-label">Need For</label>
                            <textarea name="NeedFor" class="form-control" rows="3" v-model="needFor"></textarea>
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
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.js') }}"></script>
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

    <script>
        let app = new Vue({
            el: '#vue-container',
            data: {
                isLoading: false,
                transactionNo: '{{ old('TransactionNo') ?? '' }}',
                automatic: {{ old('automatic') || count($errors->all()) == 0 ? 'true' : 'false' }},
                transactionDate: '{{ old('TransactionDate') ?? date('d/m/Y') }}',
                expireDate: '{{ old('ExpiredDate') ?? '' }}',
                fromId: '{{ old('WarehouseIDFrom') ?? '' }}',
                staffFrom: '{{ old('StaffInChargeIDFrom') ?? '' }}',
                toId: '{{ old('WarehouseIDTo') ?? '' }}',
                staffTo: '{{ old('StaffInChargeIDTo') ?? '' }}',
                needFor: '{{ old('NeedFor') ?? '' }}',
                details: [],
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
                addDetail(){
                    this.details.push({
                        id: Math.random().toString(36).substr(2, 9),
                        part: null,
                        qty: 1,
                        unitUrl: '{{ route('misc.partunit2') }}?id=0',
                        unit: null,
                        conversion: 1,
                        stock: 0,
                    });
                },
                changePart(index){
                    let app = this;
                    this.details[index].unitUrl = '{{ route('misc.partunit2') }}?id=' +  this.details[index].part;
                    this.details[index].unit = null;
                    this.details[index].conversion = 1;
                    this.details[index].stock = 0;

                    // Reset Unit Select2 via jQuery
                    $(`#unit${index}`).val(null).trigger('change');

                    this.getStock(index);
                },
                getConversion(index){
                    let app = this;
                    let item = app.details[index];
                    if(item.part && item.unit){
                        $.ajax({
                            url: '{{ route('misc.conversion') }}?part_id=' + item.part + '&unit_id=' + item.unit,
                            type: 'GET'
                        }).then(function (result){
                            app.$set(item, 'conversion', result.conversion);
                        });
                    }
                },
                getStock(index){
                    let app = this;
                    let item = app.details[index];
                    if(item.part && app.fromId){
                        $.ajax({
                            url: '{{ route('misc.stock_by_control_panel') }}',
                            type: 'GET',
                            data: {
                                part: item.part,
                                warehouse: app.fromId,
                            }
                        }).then(function (result){
                            app.$set(item, 'stock', result.stock);
                        });
                    } else {
                        app.$set(item, 'stock', 0);
                    }
                },
                deleteDetail(item){
                    this.details = this.details.filter(x => x !== item);
                },
            },
            computed: {

            },
            watch: {
                fromId(){
                    for(var i = 0; i < this.details.length; i++){
                        this.getStock(i);
                    }
                },
            },
            mounted() {
                let app = this;

                FormPreserver.initVue(app, 'transfer_request_form_data', ['isLoading']);

                // Watch for changes in select2 elements and sync with Vue model
                $('#transfer-form').on('select2:select', '#WarehouseIDFrom', function (e){
                    let data = e.params.data;
                    if(data.staff_id){
                        let option = new Option(data.staff_name, data.staff_id, true, true);
                        $('#StaffInChargeIDFrom').empty().append(option).trigger('change');
                    }
                });

                $('#transfer-form').on('select2:select', '#WarehouseIDTo', function (e){
                    let data = e.params.data;
                    if(data.staff_id){
                        let option = new Option(data.staff_name, data.staff_id, true, true);
                        $('#StaffInChargeIDTo').empty().append(option).trigger('change');
                    }
                });

                $('#transfer-form').on('select2:select', '#WarehouseIDFrom', function (){
                    let val = $(this).val();
                    app.fromId = val;
                    if(!val){
                        app.staffFrom = null;
                        $('#StaffInChargeIDFrom').val(null).trigger('change');
                    }
                });

                $('#transfer-form').on('select2:select', '#StaffInChargeIDFrom', function (){
                    app.staffFrom = $(this).val();
                });

                $('#transfer-form').on('select2:select', '#WarehouseIDTo', function (){
                    let val = $(this).val();
                    app.toId = val;
                    if(!val){
                        app.staffTo = null;
                        $('#StaffInChargeIDTo').val(null).trigger('change');
                    }
                });

                $('#transfer-form').on('select2:select', '#StaffInChargeIDTo', function (){
                    app.staffTo = $(this).val();
                });

                $('#transfer-form').on('select2:select', '.part', function (){
                    let index = $(this).data('index');
                    let val = $(this).val();
                    if(val != null && val != ''){
                        app.details[index].part = val;
                        app.changePart(index);
                    }
                });

                $('#transfer-form').on('select2:select', '.unit', function (){
                    let index = $(this).data('index');
                    let val = $(this).val();
                    if(val != null && val != ''){
                        app.details[index].unit = val;
                        app.getConversion(index);
                    }
                });

                $('.js-flatpickr').flatpickr({
                    dateFormat: "d/m/Y",
                    onReady: function(selectedDates, dateStr, instance) {
                        if (instance.element.id === 'TransactionDate' && app.transactionDate) {
                            instance.setDate(app.transactionDate);
                        }
                        if (instance.element.id === 'ExpiredDate' && app.expireDate) {
                            instance.setDate(app.expireDate);
                        }
                    },
                    onChange: function(selectedDates, dateStr, instance) {
                        if (instance.element.id === 'TransactionDate') {
                            app.transactionDate = dateStr;
                        }
                        if (instance.element.id === 'ExpiredDate') {
                            app.expireDate = dateStr;
                        }
                    }
                });

                $('.js-flatpickr:visible').on('focus', function () {
                    $(this).blur()
                });
                $('.js-flatpickr:visible').prop('readonly', false);
            }
        });
    </script>
@endsection
