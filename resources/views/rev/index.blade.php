@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Rev Configuration</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2">
            <div class="flex-grow-1 mb-1 mb-md-0">

                <h1 class="h3 fw-bold mb-5">
                    Rev Configuration
                </h1>

                <form method="post" action="{{ route('rev.update') }}">
                    @csrf

                    <div class="block block-rounded">
                        <div class="block-content">
                            <div class="row mb-3 align-items-end">
                                <div class="col-lg-4 col-12">
                                    <label>Module</label>
                                    <select class="form-select" name="TransactionType" v-model="module" :disabled="isLoading">
                                        <option value="">-</option>
{{--                                        <option value="PURCHASE_ORDER">Purchase Order</option>--}}
{{--                                        <option value="GOODS_RECEIVING">Goods Receiving</option>--}}
{{--                                        <option value="PURCHASE_INVOICE">Purchase Invoice</option>--}}
{{--                                        <option value="DIRECT_PURCHASE">Direct Purchase</option>--}}
{{--                                        <option value="PURCHASE_RETURN">Purchase Return</option>--}}
{{--                                        <option value="SALES_ORDER">Sales Order</option>--}}
{{--                                        <option value="DELIVERY_ORDER">Delivery Order</option>--}}
{{--                                        <option value="SALES_INVOICE">Sales Invoice</option>--}}
{{--                                        <option value="DIRECT_SALES">Direct Sales</option>--}}
{{--                                        <option value="SALES_RETURN">Sales Return</option>--}}
                                        <option value="PURCHASING">Purchasing</option>
                                        <option value="SALES">Sales</option>
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
                            <button type="submit" class="btn btn-primary mb-3 fs-6" :disabled="isLoading || module == ''"><i class="fa fa-fw fa-save me-2"></i>Save</button>

                            <div class="row mb-2">
                                <div class="col-3">
                                    <label class="form-label">Rev</label>
                                </div>
                                <div class="col-3">
                                    <label class="form-label">Alias</label>
                                </div>
                                <div class="col-4">
                                    <label class="form-label">Form Type</label>
                                </div>
{{--                                <div class="col-2">--}}
{{--                                    Select From--}}
{{--                                </div>--}}
                            </div>

                            <div class="row mb-3" v-for="(item, index) in revs">
                                <div class="col-3">
                                    <input type="text" class="form-control" :value="'Rev ' + (index+1)" readonly>
                                </div>
                                <div class="col-3">
                                    <input type="text" :name="'rev'+(index+1)" class="form-control" v-model="item.name" :disabled="isLoading || module == ''">
                                </div>
                                <div class="col-4">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" :name="'revType'+(index+1)" value="text" v-model="item.type" :disabled="isLoading || module == ''">
                                        <label class="form-check-label">Text</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" :name="'revType'+(index+1)" value="numeric" v-model="item.type" :disabled="isLoading || module == ''">
                                        <label class="form-check-label">Numeric</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" :name="'revType'+(index+1)" value="date" v-model="item.type" :disabled="isLoading || module == ''">
                                        <label class="form-check-label">Date</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" :name="'revType'+(index+1)" value="select" v-model="item.type" :disabled="isLoading || module == ''">
                                        <label class="form-check-label">Select</label>
                                    </div>
                                </div>
{{--                                <div class="col-2">--}}
{{--                                    <select class="form-select" :disabled="item.type != 'select' || module == ''">--}}
{{--                                        <option>Supplier</option>--}}
{{--                                        <option>Supplier</option>--}}
{{--                                        <option>Customer</option>--}}
{{--                                    </select>--}}
{{--                                </div>--}}
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </div>

    </div>
    <!-- END Hero -->


@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('js/plugins/flatpickr/flatpickr.min.css') }}">
    <style>

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
                revs: [
                    {
                        name: '',
                        type: 'text',
                    },
                    {
                        name: '',
                        type: 'text',
                    },
                    {
                        name: '',
                        type: 'text',
                    },
                    {
                        name: '',
                        type: 'text',
                    },
                    {
                        name: '',
                        type: 'text',
                    },
                    {
                        name: '',
                        type: 'text',
                    },
                    {
                        name: '',
                        type: 'text',
                    },
                    {
                        name: '',
                        type: 'text',
                    },
                    {
                        name: '',
                        type: 'text',
                    },
                    {
                        name: '',
                        type: 'text',
                    },
                    {
                        name: '',
                        type: 'text',
                    },
                    {
                        name: '',
                        type: 'text',
                    },
                    {
                        name: '',
                        type: 'text',
                    },
                    {
                        name: '',
                        type: 'text',
                    },
                    {
                        name: '',
                        type: 'text',
                    },
                ]
            },
            methods: {
                getDetail(){
                    this.isLoading = true;
                    let app = this;
                    $.ajax({
                        url: '{{ route('rev.detail') }}',
                        type: 'GET',
                        data: {
                            type: app.module,
                        }
                    }).then(function (result){
                        app.revs = result.data;
                    }).always(function (){
                        app.isLoading = false;
                    });
                },
            },
            watch: {
                module(){
                    if(this.module != ''){
                        this.getDetail();
                    }
                },
            },
            mounted() {

            }
        });

        @if(session()->has('type'))
        One.helpers('jq-notify', {
            type: '{{ session('type') }}',
            icon: '{{ session('icon') }}',
            message: '{{ session('message') }}',
        });
        @endif
    </script>
@endsection
