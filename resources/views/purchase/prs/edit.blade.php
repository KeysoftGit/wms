@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Edit Purchase Request Settle</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class=" w-100 px-lg-5 py-lg-3 p-3" id="vue-container" style="overflow-x: hidden">

        <form autocomplete="off" method="post" enctype="multipart/form-data" action="{{ route('prs.update') }}" id="prs-form">
            @csrf

            <div class="d-flex flex-row align-items-center mb-5">
                <a href="{{ route('prs') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                <h1 class="h3 fw-bold ms-4 mb-0">
                    Edit Purchase Request Settle
                </h1>
            </div>

            @if (count($errors->all()) > 0)
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <p class="m-0 fs-6">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <input type="hidden" name="id" value="{{ $prs->TransactionNo }}">
            <input type="hidden" name="rev" v-model="revCount">

            <div class="block block-rounded">
                <div class="block-content pb-3">
                    <button type="submit" class="btn btn-primary mb-3 fs-6"><i
                            class="fa fa-fw fa-save me-2"></i>Save</button>

                    <div class="row">
                        <div class="col-lg-6 col-12 pe-lg-5">
                            <div class="d-flex flex-row align-items-center mb-3">
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label">Transaction No <span class="text-danger">*</span></label>
                                        <input type="text" name="TransactionNo" id="TransactionNo" class="form-control"
                                            value="{{ $prs->TransactionNo }}" readonly>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Request Transaction No <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="PREQ_TransactionNo" id="PREQ_TransactionNo"
                                            class="form-control" value="{{ $prs->RequestNo }}" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">Transaction Date</label>
                                    <input type="text" class="form-control" name="TransactionDate"
                                        value="{{ old('TransactionDate') ?? ($prs->TransactionDate != null ? date('d/m/Y', strtotime($prs->TransactionDate)) : '') }}"
                                        readonly>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Need Date</label>
                                    <input type="text" class="form-control" name="NeedDate"
                                        value="{{ old('NeedDate') ?? ($prs->NeedDate != null ? date('d/m/Y', strtotime($prs->NeedDate)) : '') }}"
                                        readonly>
                                </div>
                            </div>

                            <div class="mb-3" id="loading-supplier">
                                <label class="form-label">Supplier <span class="text-danger">*</span></label>
                                <select class="form-select" disabled>
                                    <option id="loading-text">Loading Suppliers.....</option>
                                </select>
                            </div>
                            <div class="mb-3" id="supplier-container">
                                <label class="form-label">Supplier <span class="text-danger">*</span></label>
                                <select class="form-select form-select2" name="SupplierID" id="SupplierID" required>
                                    <option value="">-</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-6 col-12 ps-lg-5">
                            <div class="mb-3" id="currency-container">
                                <label class="form-label">Currency <span class="text-danger">*</span></label>
                                <select2 url="{{ route('misc.currency', ['select2' => true]) }}" v-model="currency"
                                    :prevalue="currency" class="form-select" name="CurrencyID" placeholder="Pick Currency"
                                    required>
                                    <option>-</option>
                                </select2>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">Rate <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="Rate" id="Rate"
                                        value="{{ old('Rate') ?? $prs->Rate }}">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">VAT <span class="text-danger">*</span></label>
                                <div class="space-y-2">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="VATNone" name="VAT"
                                            value="N" v-model="vatType">
                                        <label class="form-check-label" for="VATNone">None</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="VATExclude" name="VAT"
                                            value="E" v-model="vatType">
                                        <label class="form-check-label" for="VATExclude">Exclude</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="VATInclude" name="VAT"
                                            value="I" v-model="vatType">
                                        <label class="form-check-label" for="VATInclude">Include</label>
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
                        <h5 class="m-0">Purchase Detail</h5>
                    </div>

                    <div id="detail-table" class="table-responsive w-100" style="overflow-x: scroll">
                        <table class="table table-bordered nowrap w-100" style="table-layout: fixed; overflow-x: scroll">
                            <thead>
                                <tr>
                                    <th style="width: 65px"></th>
                                    <th style="width: 300px;">Part</th>
                                    <th style="width: 200px;">Qty Settle</th>
                                    <th style="width: 200px;">Unit</th>
                                    <th style="width: 200px;">Conversion</th>
                                    <th style="width: 200px;">Price</th>
                                    <th style="width: 200px;">Discount 1 %</th>
                                    <th style="width: 200px;">Discount 1</th>
                                    <th style="width: 200px;">Discount 2 %</th>
                                    <th style="width: 200px;">Discount 2</th>
                                    <th style="width: 200px;">Total Price</th>
                                    <th style="width: 200px;">Division</th>
                                    <th style="width: 150px;">ATA Request</th>
                                    <th style="width: 200px;">ATA Request In Weeks</th>
                                    <th style="width: 200px;">Warehouse</th>
                                    <th style="width: 100px;">Image</th>
                                    <th v-for="(n, i) in revCount" style="width: 250px;">
                                        <span
                                            v-if="revData[i].name != null && revData[i].name != ''">@{{ revData[i].name }}</span>
                                        <span v-else>Rev @{{ n }}</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td :colspan="spanCount" class="text-center p-3" v-if="details.length == 0">No
                                        Details Added Yet</td>
                                </tr>
                                <tr v-for="(detail, index) in details" :key="detail.requestDtID">
                                    <td>
                                        <button type="button" class="btn btn-danger" @click="deleteDetail(detail)"><i
                                                class="fa fa-fw fa-trash-can"></i></button>
                                    </td>
                                    <td hidden>
                                        <input class="form-control" v-model="detail.requestDtID" name="requestDtID[]"
                                            readonly>
                                    </td>
                                    <td>
                                        <select2 url="{{ route('misc.part') }}" v-model="detail.part"
                                            :prevalue="detail.part" class="form-select part" name="part[]"
                                            placeholder="Pick Part" :data-index="index" required>
                                            <option>-</option>
                                        </select2>
                                    </td>
                                    <td>
                                        <vue-autonumeric :options="autonumericFormat2" name="qty[]" class="form-control"
                                            v-model="detail.qty" :prevalue="detail.qty" required></vue-autonumeric>
                                    </td>
                                    <td>
                                        <select2 :url="detail.unitUrl" v-model="detail.unit" :prevalue="detail.unit"
                                            class="form-control unit" name="unit[]" :id="'unit' + index"
                                            :disabled="detail.part == null" placeholder="Pick Unit"
                                            :data-index="index" required>
                                        </select2>
                                    </td>
                                    <td>
                                        <vue-autonumeric :options="autonumericFormat" name="conversion[]"
                                            class="form-control" v-model="detail.conversion" readonly></vue-autonumeric>
                                    </td>
                                    <td>
                                        <vue-autonumeric :options="autonumericFormat2" name="price[]"
                                            v-model="detail.price" class="form-control" required></vue-autonumeric>
                                    </td>
                                    <td>
                                        <vue-autonumeric :options="autonumericFormat2" name="discount1p[]"
                                            v-model="detail.discount1p" class="form-control"></vue-autonumeric>
                                    </td>
                                    <td>
                                        <vue-autonumeric :options="autonumericFormat2" name="discount1[]"
                                            v-model="detail.discount1" class="form-control"></vue-autonumeric>
                                    </td>
                                    <td>
                                        <vue-autonumeric :options="autonumericFormat2" name="discount2p[]"
                                            v-model="detail.discount2p" class="form-control"></vue-autonumeric>
                                    </td>
                                    <td>
                                        <vue-autonumeric :options="autonumericFormat2" name="discount2[]"
                                            v-model="detail.discount2" class="form-control"></vue-autonumeric>
                                    </td>
                                    <td>
                                        <input type="hidden" name="discount[]" v-model="detail.totalDiscount">
                                        <vue-autonumeric :options="autonumericFormat2" name="total_price[]"
                                            class="form-control" :value="totalPrice(index)" readonly></vue-autonumeric>
                                    </td>
                                    <td>
                                        <select2 url="{{ route('misc.division2') }}" v-model="detail.division"
                                            :prevalue="detail.division" class="form-select" name="division[]"
                                            placeholder="Pick Division" required>
                                            <option>-</option>
                                        </select2>
                                    </td>
                                    <td>
                                        <input type="text" name="ata[]" v-model="detail.ata"
                                            class="form-control js-flatpickr" @change="changeATA(index)"
                                            :data-index="index" required>
                                    </td>
                                    <td>
                                        <input type="text" name="ata_week[]" class="form-control"
                                            v-model="detail.ata_week" readonly>
                                    </td>
                                    <td>
                                        <select2 url="{{ route('misc.warehouse2') }}" v-model="detail.warehouse"
                                            :prevalue="detail.warehouse" class="form-select" name="warehouse[]"
                                            placeholder="Pick Warehouse" :data-index="index" required>
                                            <option>-</option>
                                        </select2>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-alt-secondary" v-if="detail.image != ''"
                                            @click="viewImage(index)"><i class="fa fa-fw fa-eye"></i></button>
                                        <button type="button" class="btn btn-alt-secondary" disabled v-else><i
                                                class="fa fa-fw fa-eye"></i></button>
                                    </td>
                                    <td v-for="(n, i) in revCount">
                                        <input type="text" :name="'rev' + n + '[]'" v-model="detail.rev[i]"
                                            class="form-control js-flatpickr" v-if="revData[i].type == 'date'">
                                        <vue-autonumeric :options="autonumericFormat2" :name="'rev' + n + '[]'"
                                            v-model="detail.rev[i]" class="form-control"
                                            v-else-if="revData[i].type == 'numeric'"></vue-autonumeric>
                                        <select2 :url="getRevUrl(n)" v-model="detail.rev[i]" :prevalue="detail.rev[i]"
                                            class="form-select" :name="'rev' + n + '[]'" placeholder="Pick an Item"
                                            v-else-if="revData[i].type == 'select'">
                                            <option>-</option>
                                        </select2>
                                        <input type="text" :name="'rev' + n + '[]'" v-model="detail.rev[i]"
                                            class="form-control" v-else>
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
                            <textarea name="Notes" class="form-control" rows="3">{{ old('Notes') ?? $prs->Notes }}</textarea>
                        </div>

                        <div class="col-auto">
                            <div class="row align-items-start">
                                <div class="col-auto d-flex flex-row align-items-center">
                                    <label class="form-label m-0">Total Qty</label>
                                    <input class="form-control ms-2" style="width: 50px;" :value="totalQty"
                                        readonly>
                                </div>
                                <div class="col-auto">
                                    <table>
                                        <tr>
                                            <td class="fw-bold">Sub Total</td>
                                            <td></td>
                                            <td>
                                                <input type="hidden" name="SubTotal" :value="getSubTotal">
                                                <vue-autonumeric :options="getFormat()"
                                                    class="form-control text-end auto-format" :value="getSubTotal"
                                                    readonly></vue-autonumeric>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Tax Service</td>
                                            <td class="px-2">
                                                <div class="d-flex flex-row align-items-center">
                                                    <vue-autonumeric :options="autonumericFormat2" name="PercentagePPH"
                                                        data-type="pph" v-model="pph" class="form-control sync-edit"
                                                        style="width: 50px"></vue-autonumeric>
                                                    <p class="mb-0 ms-1 fs-6">%</p>
                                                </div>
                                            </td>
                                            <td>
                                                <vue-autonumeric :options="getFormat()"
                                                    class="form-control text-end sync-edit" data-type="serviceTax"
                                                    name="ServiceTax" v-model="serviceTax"></vue-autonumeric>
                                            </td>
                                        </tr>
                                        {{--                                        <tr> --}}
                                        {{--                                            <td class="fw-bold">PPh 22</td> --}}
                                        {{--                                            <td class="px-2"> --}}
                                        {{--                                                <div class="d-flex flex-row align-items-center"> --}}
                                        {{--                                                    <vue-autonumeric :options="autonumericFormat2" v-model="pph22Percentage" data-type="pph22Percentage" name="pph22Percentage" class="form-control sync-edit" style="width: 50px"></vue-autonumeric> --}}
                                        {{--                                                    <p class="mb-0 ms-1 fs-6">%</p> --}}
                                        {{--                                                </div> --}}
                                        {{--                                            </td> --}}
                                        {{--                                            <td> --}}
                                        {{--                                                <vue-autonumeric :options="autonumericFormat" name="PPH22" data-type="pph22" class="form-control text-end sync-edit" v-model="pph22"></vue-autonumeric> --}}
                                        {{--                                            </td> --}}
                                        {{--                                        </tr> --}}
                                        <tr>
                                            <td class="fw-bold">VAT</td>
                                            <td></td>
                                            <td>
                                                <input type="hidden" name="VATValue" :value="getVAT">
                                                <vue-autonumeric :options="autonumericFormat"
                                                    class="form-control text-end" :value="getVAT"
                                                    readonly></vue-autonumeric>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Freight Charge</td>
                                            <td></td>
                                            <td>
                                                <vue-autonumeric :options="autonumericFormat" name="Freight"
                                                    class="form-control text-end" v-model="freight"></vue-autonumeric>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Grand Total</td>
                                            <td></td>
                                            <td>
                                                <input type="hidden" name="GrandTotal" :value="getGrandTotal">
                                                <vue-autonumeric :options="getFormat()"
                                                    class="form-control text-end auto-format" :value="getGrandTotal"
                                                    readonly></vue-autonumeric>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @include('purchase.prs.img_modal')
        </form>


    </div>
    <!-- END Hero -->

@endsection

@section('styles')
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
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
        Date.prototype.getWeekNumber = function() {
            var d = new Date(Date.UTC(this.getFullYear(), this.getMonth(), this.getDate()));
            var dayNum = d.getUTCDay() || 7;
            d.setUTCDate(d.getUTCDate() + 4 - dayNum);
            var yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
            return Math.ceil((((d - yearStart) / 86400000) + 1) / 7)
        };

        let app = new Vue({
            el: '#vue-container',
            data: {
                revData: @json($revData),
                details: @json($details),
                revCount: {{ $prs->RevCount }},
                selectedImg: '',
                subTotal: 0,
                serviceTax: '{{ old('ServiceTax') ?? $prs->ServiceTax }}',
                pph: '{{ old('PercentagePPH') ?? $prs->PercentagePPH }}',
                {{-- pph22: '{{ old('PPH22') ?? $prs->PPH22 }}', --}}
                pph22: 0,
                pph22Percentage: {{ old('PercentagePPH22') ?? '0' }},
                freight: '{{ old('Freight') ?? $prs->Freight }}',
                vatType: '{{ old('VAT') ?? $prs->VAT }}',

                init: false,
                currency: '{{ old('CurrencyID') ?? $prs->CurrencyID }}',
                editType: '',

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
                getFormat() {
                    if (this.currency == "IDR") {
                        return this.autonumericFormat;
                    }

                    return this.autonumericFormat2
                },

                addRev() {
                    if (this.revCount < 15) {
                        this.revCount++;
                        for (var i = 0; i < this.details.length; i++) {
                            this.details[i].rev.push('');
                        }

                        setTimeout(function() {
                            $('.js-flatpickr').flatpickr({
                                dateFormat: "d/m/Y",
                            });
                            $('.js-flatpickr:visible').on('focus', function() {
                                $(this).blur()
                            });
                            $('.js-flatpickr:visible').prop('readonly', false);
                        }, 50);
                    }
                },
                removeRev() {
                    if (this.revCount > 0) {
                        this.revCount--;
                        if (this.revCount == 0) {
                            for (var i = 0; i < this.details.length; i++) {
                                this.details[i].rev.splice(this.revCount - 1, 1);
                            }
                        }
                    }
                },
                getRevUrl(index) {
                    return '{{ route('rev.select') }}?type=PURCHASING&index=' + index;
                },

                addDetail() {
                    let rev = [];
                    for (var i = 0; i < this.revCount; i++) {
                        rev.push('');
                    }

                    var date = new Date();

                    this.details.push({
                        id: makeid(10),
                        part: null,
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
                        ata: '{{ date('d/m/Y') }}',
                        ata_week: date.getWeekNumber(),
                        image: '',
                        rev: rev,
                        initiated: true,
                    });

                    $('#detail-table').animate({
                        scrollLeft: 0
                    }, 250);

                    setTimeout(function() {
                        $('.js-flatpickr').flatpickr({
                            dateFormat: "d/m/Y",
                        });
                        $('.js-flatpickr:visible').on('focus', function() {
                            $(this).blur()
                        });
                        $('.js-flatpickr:visible').prop('readonly', false);
                    }, 50);
                },

                changePart(index) {
                    if (this.details[index].initiated) {
                        this.details[index].unitUrl = '{{ route('misc.partunit2') }}?id=' + this.details[index]
                            .part;
                        this.details[index].unit = null;

                        $(`#unit${index}`).val('').trigger('change');
                    } else {
                        this.details[index].initiated = true;
                    }

                    this.getContent(index);
                },

                getConversion(index) {
                    let app = this;
                    $.ajax({
                        url: '{{ route('misc.conversion') }}?part_id=' + app.details[index].part +
                            '&unit_id=' + app.details[index].unit,
                        type: 'GET'
                    }).then(function(result) {
                        app.details[index].conversion = result.conversion;
                    });
                },

                getContent(index) {
                    let app = this;
                    $.ajax({
                        url: '{{ route('misc.part_content') }}?id=' + app.details[index].part,
                        type: 'GET'
                    }).then(function(result) {
                        app.details[index].image = result.image;
                        app.details[index].vat = result.vat;
                    });
                },

                totalPrice(index) {
                    var price = parseFloat(this.details[index].price);
                    var total = price;
                    var discount = 0;

                    if (this.details[index].discount1p != '' && this.details[index].discount1p != '0.00') {
                        let temp = total * parseFloat(this.details[index].discount1p) / 100;
                        discount += temp;
                        total = total - temp;
                    }

                    if (this.details[index].discount1 != '' && this.details[index].discount1 != '0.00') {
                        let temp = parseFloat(this.details[index].discount1);
                        discount += temp;
                        total = total - temp;
                    }

                    if (this.details[index].discount2p != '' && this.details[index].discount2p != '0.00') {
                        let temp = (total * parseFloat(this.details[index].discount2p) / 100);
                        discount += temp;
                        total = total - temp;
                    }

                    if (this.details[index].discount2 != '' && this.details[index].discount2 != '0.00') {
                        let temp = parseFloat(this.details[index].discount2);
                        discount += temp;
                        total = total - temp;
                    }

                    let totalRaw = parseFloat(this.details[index].qty) * total;

                    if (this.vatType == 'I') {
                        let temp = total / (1 + (this.details[index].vat / 100));
                        let tax = temp * this.details[index].vat / 100;
                        total = total - tax;
                    }

                    total = parseFloat(this.details[index].qty) * total;

                    this.details[index].totalDiscount = discount;
                    this.details[index].totalPrice = total;

                    return totalRaw;
                },


                changeATA(index) {
                    let app = this;
                    setTimeout(function() {
                        var dates = app.details[index].ata.split('/');
                        var date = new Date(dates[2], dates[1] - 1, dates[0]);
                        // var firstWeekday = date.getDay();
                        // var offsetDate = date.getDate() + firstWeekday - 1;
                        // app.details[index].ata_week = Math.ceil(offsetDate / 7);
                        app.details[index].ata_week = date.getWeekNumber();
                    }, 50);
                },

                viewImage(index) {
                    this.selectedImg = this.details[index].image;
                    $('#imgModal').modal('show');
                },



                deleteDetail(item) {
                    //this.details.splice(index, 1);
                    this.details = this.details.filter(function(x) {
                        return x !== item;
                    });
                }
            },
            computed: {
                spanCount() {
                    return (this.revCount + 16);
                },
                totalQty() {
                    var total = 0;
                    for (var i = 0; i < this.details.length; i++) {
                        total += parseFloat(this.details[i].qty);
                    }

                    return total;
                },
                getSubTotal() {
                    this.subTotal = 0;
                    for (var i = 0; i < this.details.length; i++) {
                        this.subTotal += this.details[i].totalPrice;
                    }

                    return this.subTotal;
                },
                getServiceTax() {
                    if (this.serviceTax != '') {
                        return this.subTotal * parseFloat(this.serviceTax) / 200;
                    }

                    return 0;
                },
                getPPH() {
                    if (this.pph != '') {
                        return this.subTotal * parseFloat(this.pph) / 200;
                    }

                    return 0;
                },
                getVAT() {
                    var vat = 0;
                    if (this.vatType != 'N') {
                        for (var i = 0; i < this.details.length; i++) {
                            if (this.vatType == 'E') {
                                vat += (this.details[i].totalPrice * this.details[i].vat / 100);
                            } else if (this.vatType == 'I') {
                                vat += (this.details[i].totalPrice * this.details[i].vat / 100);
                            }
                        }
                    }

                    return vat;
                },
                getGrandTotal() {
                    return this.subTotal - this.serviceTax + this.pph22 + this.getVAT + this.freight;
                },
            },
            watch: {
                currency() {
                    setTimeout(function() {
                        $('.auto-format').attr('readonly', true);
                    }, 1);
                },
                subTotal() {
                    if (this.init) {
                        this.serviceTax = this.pph / 100 * this.subTotal;
                        this.pph22 = this.pph22Percentage / 100 * this.subTotal;
                    }
                },
                pph() {
                    if (this.editType == 'pph') {
                        this.serviceTax = this.pph / 100 * this.subTotal;
                    }
                },
                serviceTax() {
                    if (this.editType == 'serviceTax') {
                        this.pph = this.serviceTax / this.subTotal * 100;
                    }
                },
                pph22() {
                    if (this.editType == 'pph22') {
                        this.pph22Percentage = this.pph22 / this.subTotal * 100;
                    }
                },
                pph22Percentage() {
                    if (this.editType == 'pph22Percentage') {
                        this.pph22 = this.pph22Percentage / 100 * this.subTotal;
                    }
                }
            },
            mounted() {
                {{-- this.pph22 = {!! old('PPH22') ?? $prs->PPH22 !!}; --}}
                {{-- $('#pph22').trigger('change'); --}}

                let app = this;
                $('#prs-form').on('change', '.part', function() {
                    if ($(this).val() != null) {
                        app.changePart($(this).data('index'));
                    }
                });

                $('#prs-form').on('change', '.unit', function() {
                    if ($(this).val() != null) {
                        app.getConversion($(this).data('index'));
                    }
                });

                $('.sync-edit').focus(function() {
                    app.editType = $(this).data('type');
                });

                setTimeout(function() {
                    // app.pph22Percentage = app.pph22 / app.subTotal * 100;
                    app.init = true;
                }, 100);
            }
        });


        $('.form-select2').select2({
            theme: 'bootstrap-5'
        });

        $('#division-container').hide();
        $('#supplier-container').hide();
        //$('#currency-container').hide();

        $('#automatic').on('change', function() {
            if ($('#automatic').is(':checked')) {
                $('#TransactionNo').attr('disabled', true);
            } else {
                $('#TransactionNo').removeAttr('disabled');
            }
        });

        $('.js-flatpickr').flatpickr({
            dateFormat: "d/m/Y",
        });
        $('.js-flatpickr:visible').on('focus', function() {
            $(this).blur()
        });
        $('.js-flatpickr:visible').prop('readonly', false);

        let rate = new AutoNumeric('#Rate', {
            decimalPlaces: 6,
            minimumValue: 1,
            allowDecimalPadding: "false",
            modifyValueOnWheel: false,
            digitGroupSeparator: '.',
            decimalCharacter: ',',
            unformatOnSubmit: true
        });

        let numeric = new AutoNumeric.multiple('.number-input', {
            decimalPlaces: 0,
            minimumValue: 0,
            allowDecimalPadding: "false",
            modifyValueOnWheel: false,
            digitGroupSeparator: '.',
            decimalCharacter: ',',
            unformatOnSubmit: true
        });



        // LOAD DIVISION

        $.ajax({
            url: '{!! route('misc.division') !!}',
            type: 'GET',
        }).done(function(data) {
            if (data.status == 'success') {
                var options = '';
                var oldID = '{{ old('DivisionID') ?? $prs->DivisionID }}';
                for (var i = 0; i < data.data.length; i++) {
                    options +=
                        `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                }

                $('#DivisionID').append(options);

                $('#loading-division').hide();
                $('#division-container').show();
            } else {
                $('#loading-text').html('Something went wrong!');
            }
        });


        // LOAD SUPPLIER

        $.ajax({
            url: '{!! route('misc.supplier') !!}',
            type: 'GET',
        }).done(function(data) {
            if (data.status == 'success') {
                var options = '';
                var oldID = '{{ old('SupplierID') ?? $prs->SupplierID }}';
                for (var i = 0; i < data.data.length; i++) {
                    options +=
                        `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                }

                $('#SupplierID').append(options);

                $('#loading-supplier').hide();
                $('#supplier-container').show();
            } else {
                $('#loading-text').html('Something went wrong!');
            }
        });


        // LOAD CURRENCY

        {{-- $.ajax({ --}}
        {{--    url: '{!! route('misc.currency') !!}', --}}
        {{--    type: 'GET', --}}
        {{-- }).done(function (data)  { --}}
        {{--    if (data.status == 'success'){ --}}
        {{--        var options = ''; --}}
        {{--        var oldID = '{{ old("CurrencyID") ?? $prs->CurrencyID }}'; --}}
        {{--        for(var i = 0; i < data.data.length; i++){ --}}
        {{--            options += `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`; --}}
        {{--        } --}}

        {{--        $('#CurrencyID').append(options); --}}

        {{--        $('#loading-currency').hide(); --}}
        {{--        $('#currency-container').show(); --}}
        {{--    } --}}
        {{--    else { --}}
        {{--        $('#loading-text').html('Something went wrong!'); --}}
        {{--    } --}}
        {{-- }); --}}
    </script>
@endsection
