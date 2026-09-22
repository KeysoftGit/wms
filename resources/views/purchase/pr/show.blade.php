@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Purchase Return - {{ $pr->TransactionNo }}</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <div class="d-flex flex-row align-items-center mb-5">
            <a href="{{ route('pr') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
            <h1 class="h3 fw-bold ms-4 mb-0">
                {{ $pr->TransactionNo }}
            </h1>
        </div>

        <div class="row justify-content-between mb-3 align-items-end">
            <div class="col-auto d-flex flex-row">
                @if($pr->Editable == 1 && auth()->user()->hasAnyPermission(['admin', 'pr.edit']))
                    <a href="{{ route('pr.edit', $pr->id) }}" class="btn btn-primary fs-6"><i class="fa fa-fw fa-edit"></i> Edit</a>
                @endif
            </div>

            <div class="col-auto">
                <form autocomplete="off" action="{{ config('app.report_url') }}/print/index">
                    <input type="hidden" name="transactionCode" value="{{ $pr->TransactionNo }}">
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
                <div class="row align-items-center mb-3">
                    <div class="col-lg-2 col-12 mb-lg-0 mb-3">
                        <label class="form-label">Transaction No</label>
                        <input type="text" name="TransactionNo" id="TransactionNo" class="form-control" value="{{ $pr->TransactionNo }}" disabled>
                    </div>
                    <div class="col-lg-2 col-12">
                        <label class="form-label">Transaction Date</label>
                        <input type="text" class="form-control" id="TransactionDate" name="TransactionDate" placeholder="d/m/Y" value="{{ date('d/m/Y', strtotime($pr->TransactionDate)) }}" readonly="readonly">
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 col-12 pe-lg-5">
                        <div class="mb-3">
                            <label class="form-label">Division</label>
                            <input type="text" class="form-control" value="{{ $pr->DivisionID . ($pr->division->DivisionName ? ' - ' . $pr->division->DivisionName : '') }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Supplier</label>
                            <input type="text" class="form-control" value="{{ $pr->SupplierID . ($pr->supplier->SupplierName ? ' - ' . $pr->supplier->SupplierName : '') }}" readonly>
                        </div>
                        @if($pr->ReceivingNumber != null)
                            <div class="mb-3">
                                <label class="form-label">Receiving Number</label>
                                <input type="text" class="form-control" value="{{ $pr->ReceivingNumber }}" readonly>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">VAT</label>
                            @if($pr->VAT == 'N')
                                <input type="text" class="form-control" value="None" readonly>
                            @elseif($pr->VAT == 'E')
                                <input type="text" class="form-control" value="Exclude" readonly>
                            @else
                                <input type="text" class="form-control" value="Include" readonly>
                            @endif
                        </div>
                    </div>
                    <div class="col-lg-6 col-12 ps-lg-5">
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Currency</label>
                                <input type="text" class="form-control" value="{{ $pr->CurrencyID . ($pr->currency->CurrencyName ? ' - ' . $pr->currency->CurrencyName : '') }}" readonly>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Rate</label>
                                <input type="text" class="form-control" name="Rate" id="Rate" value="{{ $pr->Rate }}" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Based On</label>
                            @if($pr->BasedOnGoodsReceiving == 1)
                                <input type="text" class="form-control" value="Goods Receiving" readonly>
                            @elseif($pr->BasedOnDirectPurchase == 1)
                                <input type="text" class="form-control" value="Direct Purchase" readonly>
                            @else
                                <input type="text" class="form-control" value="None" readonly>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-content">
                <div class="d-flex flex-row justify-content-between align-items-center mb-3">
                    <h5 class="mb-3">Detail</h5>
                </div>

                <h1 class="text-center" v-if="isLoading"><i class="fa fa-fw fa-circle-notch fa-spin"></i></h1>

                <div class="table-responsive w-100" style="overflow-x: scroll" v-if="!isLoading && ((reff != '' && reff != null) || prType == 'N')">
                    <table class="table table-bordered nowrap w-100" style="table-layout: fixed; overflow-x: scroll">
                        <thead>
                        <tr>
                            <th style="width: 200px;">Part</th>
                            <th style="width: 200px;">UnitID</th>
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
                            <td>
                                <input class="form-control" v-model="detail.part" readonly>
                            </td>
                            <td>
                                <input class="form-control" v-model="detail.unit" readonly>
                            </td>
                            <td>
                                <vue-autonumeric class="form-control" :options="autonumericFormat2" type="text" name="qty[]" v-model="detail.qty" readonly=""></vue-autonumeric>
                            </td>
                            <td>
                                <vue-autonumeric class="form-control" :options="getFormat()" type="text" name="price[]" v-model="detail.price" readonly></vue-autonumeric>
                            </td>
                            <td>
                                <vue-autonumeric class="form-control" :options="getFormat()" type="text" :value="totalPrice(index)" readonly=""></vue-autonumeric>
                            </td>
                            <td>
                                <input class="form-control" v-model="detail.division" readonly>
                            </td>
                            <td>
                                <input class="form-control" v-model="detail.warehouse" readonly>
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
                <div class="row justify-content-between mb-3">
                    <div class="col-4">
                        <label class="form-label">Notes</label>
                        <textarea name="Notes" class="form-control" rows="3" readonly>{{ $pr->Notes }}</textarea>
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
                revCount: {!! $pr->RevCount !!},
                prType: '{{ $type }}',
                reff: '{{ $pr->ReceivingNumber ?? '' }}',
                currency: '{{ old('CurrencyID') ?? $pr->CurrencyID }}',
                details: @json($details),
                grandTotal: 0,

                autonumericFormat: {
                    minimumValue: '-9999999999999',
                    maximumValue: '9999999999999',
                    decimalPlaces: 0,
                    digitGroupSeparator: '.',
                    decimalCharacter: ',',
                    modifyValueOnWheel: false,
                    allowDecimalPadding: false,
                    unformatOnSubmit: true
                },

                autonumericFormat2: {
                    minimumValue: '-9999999999999',
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

                totalPrice(index){
                    let price = parseFloat(this.details[index].price);
                    let total = parseFloat(this.details[index].qty) * price;

                    this.details[index].totalPrice = total;

                    return total;
                },


                getDetail(){
                    this.isLoading = true;
                    let app = this;

                    var url = '';
                    @if($pr->BasedOnGoodsReceiving == 1)
                        url = '{{ route('pr.gr.detail') }}';
                    @elseif($pr->BasedOnDirectPurchase == 1)
                        url = '{{ route('pr.dp.detail') }}';
                    @endif

                    $.ajax({
                        url: url,
                        type: 'GET',
                        data: {
                            inv: app.reff,
                            edit: '{{ $pr->TransactionNo }}',
                            show: true,
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
            },
            mounted() {
                @if($pr->ReceivingNumber != null)
                    this.getDetail();
                @endif
            }
        });


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
