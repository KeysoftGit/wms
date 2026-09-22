@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Report</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2">
            <div class="flex-grow-1 mb-1 mb-md-0">

                <h1 class="h3 fw-bold mb-5">
                    View Report
                </h1>

                <div class="block block-rounded">
                    <div class="block-content">
                        <div class="row mb-3 align-items-end">
                            <div class="col-lg-4 col-12">
                                <label>Module</label>
                                <select class="form-select" v-model="module">
                                    <option value="">-</option>
                                    @if(auth()->user()->hasAnyPermission(['admin', 'report.purchasing']))
                                        <option value="PURCHASING-PO">Purchase Order</option>
                                        <option value="PURCHASING-GR">Goods Receiving</option>
                                        <option value="PURCHASING-DP-PI">Direct Purchase & Purchase Invoice</option>
                                        <option value="PURCHASING-PRETURN">Purchase Return</option>
                                        <option value="PURCHASING-DVP">Vendor Payment</option>
                                    @endif
                                    @if(auth()->user()->hasAnyPermission(['admin', 'report.sales']))
                                        <option value="SALES-DS">Direct Sales</option>
                                        <option value="SALES-SO">Sales Order</option>
                                        <option value="SALES-DO">Delivery Order</option>
                                        <option value="SALES-SI">Sales Invoice</option>
                                        <option value="SALES-SA">Sales Analysis</option>
                                        <option value="SALES-SR">Sales Return</option>
                                        <option value="SALES-CR">Customer Received</option>
                                    @endif
                                    @if(auth()->user()->hasAnyPermission(['admin', 'report.finance']))
                                        <option value="FINANCE-BB">Bank Book</option>
                                        <option value="FINANCE-AR">Account Receivable</option>
                                        <option value="FINANCE-ARA">Account Receivable Aging</option>
                                        <option value="FINANCE-DPC">Down Payment by Customer</option>
                                        <option value="FINANCE-APA">Account Payable Aging</option>
                                        <option value="FINANCE-DPS">Down Payment by Supplier</option>
                                        <option value="FINANCE-OAR">Outstanding Account Receivable</option>
                                        <option value="FINANCE-OAP">Outstanding Account Payable</option>
                                        <option value="FINANCE-CS">Customer Statement</option>
                                        <option value="FINANCE-SS">Supplier Statement</option>
                                    @endif
                                    @if(auth()->user()->hasAnyPermission(['admin', 'report.inventory']))
                                        <option value="INVENTORY-IB">Inventory Balance</option>
                                        <option value="INVENTORY-SC">Stock Card</option>
                                        <option value="INVENTORY-DT">Direct Item Transfer</option>
                                        <option value="INVENTORY-IA">Inventory Adjustment</option>
                                    @endif
                                    @if(auth()->user()->hasAnyPermission(['admin', 'report.accounting']))
                                        <option value="ACCOUNTING-AS">Account History</option>
                                        <option value="ACCOUNTING-GLARJ">General Ledger</option>
                                        <option value="ACCOUNTING-TB">Trial Balance</option>
                                        <option value="ACCOUNTING-PLS">Profit Loss Statement</option>
                                        <option value="ACCOUNTING-BS">Balance Sheet</option>
                                        <option value="ACCOUNTING-DDFR">Drill Down Financial Report</option>
                                        <option value="ACCOUNTING-KEY">Dashboard</option>
                                        <option value="ACCOUNTING-FS">Financial Statement</option>
                                        <option value="ACCOUNTING-CF">Cash Flow</option>
                                        <option value="ACCOUNTING-SC">Stock Card</option>
                                        <option value="ACCOUNTING-AG">Alk Graphic</option>
                                        <option value="ACCOUNTING-JLFC">Journal List For Consolidation</option>
                                        <option value="MaterialCost">Cost of Inventory</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-lg-4 col-12">
                                <label>Type</label>
                                <select class="form-select" id="reportType" v-model="type" :disabled="isLoading || module == ''">
                                    <option value="">-</option>
                                    <option v-for="(item, index) in typeData" :value="item.code" :key="item.code">@{{ item.name }}</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <h5 class="fw-bold mb-1" v-if="isLoading"><i class="fa fa-fw fa-spin fa-circle-notch"></i></h5>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="block block-rounded">
                    <div class="block-content">
                        <form autocomplete="off" action="{{ config('app.report_url') }}/report/index">
                            <p class="mb-3 p-3 text-center" v-if="type == ''">Pick a type to be able to view report</p>
                            <input type="hidden" name="code" v-model="type">
                            <input type="hidden" name="dbGuid" v-model="guid">
                            <div class="row">
                                <div class="col-lg-2 col-md-6 col-12 mb-3" v-for="form in forms">
                                    <label>@{{ form.label }}</label>
                                    <input type="text" class="js-flatpickr form-control js-flatpickr-enabled flatpickr-input active" v-model="form.value" placeholder="d/m/Y" readonly="readonly" required>
                                </div>
                            </div>

                            <input type="hidden" name="parameters" v-for="form in forms" :value="getValue(form)">

                            <div class="d-flex flex-row mb-3" v-if="type != ''">
                                <button type="submit" class="btn btn-primary">View Report</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>

        @include('import.modal')
        @include('import.error')
    </div>
    <!-- END Hero -->


@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('js/plugins/flatpickr/flatpickr.min.css') }}">
    <style>
        input[readonly]
        {
            background:white !important;
        }
    </style>
@endsection

@section('scripts')
    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.13/dist/vue.js"></script>
    <script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.js') }}"></script>
    <script src="{{ asset('js/plugins/flatpickr/flatpickr.min.js') }}"></script>

    <!-- Page JS Plugins -->

    <!-- Page JS Code -->
    <script>
        let app = new Vue({
            el: '#vue-container',
            data: {
                guid: '{{ session('guid') }}',
                isLoading: false,
                module: '',
                type: '',
                typeData: [],
                forms: [],
            },
            methods: {
                getDetail(){
                    this.isLoading = true;
                    let app = this;
                    $.ajax({
                        url: '{{ route('report.type') }}',
                        type: 'GET',
                        data: {
                            module: app.module,
                        }
                    }).then(function (result){
                        app.type = '';
                        app.typeData = result.data;
                    }).always(function (){
                        app.isLoading = false;
                    });
                },

                getValue(item){
                    return item.name + '#' + item.value;
                },
            },
            watch: {
                module(){
                    if(this.module != ''){
                        this.getDetail();
                    }
                },
                type(){
                    this.forms = [];
                    if(this.type != ''){
                        var index = 0;
                        for(var i = 0; i < this.typeData.length; i++){
                            if(this.typeData[i].code == this.type){
                                index = i;
                                break;
                            }
                        }
                        let form = this.typeData[index].params.split("#");
                        for(var i = 0; i < form.length; i++){
                            let field = form[i].split(",");
                            this.forms.push({
                                name: field[1],
                                label: field[0],
                                value: '{{ date('Y-m-d') }}'
                            });
                        }

                        console.log(form);

                        setTimeout(function (){
                            $('.js-flatpickr').flatpickr({
                                altFormat: "d/m/Y",
                                dateFormat: "Y-m-d",
                                defaultDate: "today"
                            });
                            $('.js-flatpickr:visible').on('focus', function () {
                                $(this).blur()
                            });
                            $('.js-flatpickr:visible').prop('readonly', false);
                        }, 10);
                    }
                }
            },
            mounted() {

            }
        });
    </script>
@endsection
