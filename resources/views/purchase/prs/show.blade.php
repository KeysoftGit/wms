@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Purchase Request Settle - {{ $prs->TransactionNo }}</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <div class="d-flex flex-row align-items-center mb-5">
            <a href="{{ route('prs') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
            <h1 class="h3 fw-bold ms-4 mb-0">{{ $prs->TransactionNo }}</h1>
        </div>


        <div class="row justify-content-between mb-3 align-items-end">
            <div class="col-auto d-flex flex-row">
                @if ($prs->Closed == 0)
                    @if (
                        !request()->has('br') &&
                            $prs->Editable == 1 &&
                            auth()->user()->hasAnyPermission(['admin', 'prs.edit']))
                        <a href="{{ route('prs.edit', $prs->id) }}" class="btn btn-primary fs-6"><i
                                class="fa fa-fw fa-edit"></i> Edit</a>
                    @endif
                    @if (
                        !request()->has('br') &&
                            $prs->Status == null &&
                            auth()->user()->hasAnyPermission(['admin', 'prs.close']))
                        <button class="btn btn-secondary ms-2 fs-6" @click="closePrs"><i class="fa fa-fw fa-x"></i>
                            Close</button>
                    @endif
                @endif
            </div>

            <div class="col-auto">
                <form autocomplete="off" action="{{ config('app.report_url') }}/print/index">
                    <input type="hidden" name="transactionCode" value="{{ $prs->TransactionNo }}">
                    <input type="hidden" name="dbGuid" value="{{ session('guid') }}">
                    <div class="d-flex flex-row align-items-end">
                        <div>
                            <label>Print Type</label>
                            <select class="form-select" name="code" style="width: 100px;">
                                @foreach ($options as $option)
                                    <option value="{{ $option->Code }}">{{ $option->Type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-success ms-2"><i
                                class="fa fa-fw fa-print me-2"></i>Print</button>
                    </div>
                </form>
            </div>
        </div>


        @if (count($errors->all()) > 0)
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <p class="m-0 fs-6">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="block block-rounded">
            <div class="block-content p-4">
                <div class="row">
                    <div class="col-lg-6 col-12 pe-lg-5">
                        <div class="mb-3">
                            <label class="form-label">Transaction No</label>
                            <input type="text" name="TransactionNo" id="TransactionNo" class="form-control"
                                value="{{ $prs->TransactionNo }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Request No</label>
                            <input type="text" name="PREQ_TransactionNo" id="PREQ_TransactionNo" class="form-control"
                                value="{{ $prs->RequestNo }}" readonly>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Transaction Date</label>
                                <input type="text" class="form-control"
                                    value="{{ old('TransactionDate') ?? ($prs->TransactionDate != null ? date('d/m/Y', strtotime($prs->TransactionDate)) : '') }}"
                                    readonly>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Need Date</label>
                                <input type="text" class="form-control"
                                    value="{{ old('NeedDate') ?? ($prs->NeedDate != null ? date('d/m/Y', strtotime($prs->NeedDate)) : '') }}"
                                    readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Supplier</label>
                            <input type="text" class="form-control"
                                value="{{ $prs->SupplierID . ($prs->supplier->SupplierName ? ' - ' . $prs->supplier->SupplierName : '') }}"
                                readonly>
                        </div>
                    </div>

                    <div class="col-lg-6 col-12 ps-lg-5">
                        <div class="row mb-3">
                            <div class="col-6" style="width: 100px">
                                <label class="form-label">Status</label>
                                <input type="text" class="form-control"
                                    value="{{ $prs->Closed == 1 ? 'Closed' : 'Open' }}" readonly>
                            </div>
                            @if ($prs->Status != null)
                                <div class="col-6">
                                    <label class="form-label">Approval Status</label>
                                    <input type="text" class="form-control" value="{{ $prs->Status }}" readonly>
                                </div>
                            @endif
                        </div>

                        @if ($prs->ClosingReason)
                            <div class="mb-3">
                                <label class="form-label">Closing Reason</label>
                                <input type="text" class="form-control" value="{{ $prs->ClosingReason }}" readonly>
                            </div>
                        @endif
                        <div class="mb-3" id="loading-currency">
                            <label class="form-label">Currency</label>
                            <input type="text" class="form-control"
                                value="{{ $prs->CurrencyID . ($prs->currency->CurrencyName ? ' - ' . $prs->currency->CurrencyName : '') }}"
                                readonly>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Rate</label>
                                <input type="text" class="form-control number-input" value="{{ $prs->Rate }}"
                                    readonly>
                            </div>
                        </div>

                        <div class="mb-3" hidden>
                            <label class="form-label">VAT</label>
                            @if ($prs->VAT == 'N')
                                <input type="text" class="form-control" value="None" readonly>
                            @elseif($prs->VAT == 'E')
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
                                @if (
                                    $prs->Closed == 0 &&
                                        !request()->has('br') &&
                                        $prs->Status == null &&
                                        auth()->user()->hasAnyPermission(['admin', 'prs.close']))
                                    <th style="width: 100px;">Action</th>
                                @else
                                    <th style="width: 100px;">Status</th>
                                @endif
                                <th style="width: 300px;">Part</th>
                                <th style="width: 200px;">Qty Settle</th>
                                <th style="width: 200px;">Qty Ordered</th>
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
                                <th style="width: 165px;">Closing Reason</th>
                                <th style="width: 100px;">Image</th>
                                <th v-for="(n, i) in revCount" style="width: 250px;">
                                    <span
                                        v-if="revData[i].name != null && revData[i].name != ''">@{{ revData[i].name }}</span>
                                    <span v-else>Rev @{{ n }}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(detail, index) in details">
                                @if (
                                    $prs->Closed == 0 &&
                                        !request()->has('br') &&
                                        $prs->Status == null &&
                                        auth()->user()->hasAnyPermission(['admin', 'prs.close']))
                                    <td v-if="detail.closed == 0">
                                        <button type="button" class="btn btn-secondary"
                                            @click="closeDetail(detail)"></i>Close</button>
                                    </td>
                                    <td v-if="detail.closed == 1">
                                        <button type="button" class="btn btn-success"
                                            @click="openDetail(detail)"></i>Open</button>
                                    </td>
                                @else
                                    <td>
                                        <input class="form-control" v-model="detail.status" readonly>
                                    </td>
                                @endif
                                <td>
                                    <input class="form-control" v-model="detail.part" readonly>
                                </td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat2" name="qty[]" class="form-control"
                                        v-model="detail.qty" :prevalue="detail.qty" readonly></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat2" name="qtyOrdered[]" class="form-control"
                                        v-model="detail.qtyOrdered" :prevalue="detail.qtyOrdered" readonly></vue-autonumeric>
                                </td>
                                <td>
                                    <input class="form-control" v-model="detail.unit" readonly>
                                </td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat" class="form-control"
                                        v-model="detail.conversion" readonly></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat2" v-model="detail.price"
                                        class="form-control" readonly></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat2" v-model="detail.discount1p"
                                        class="form-control" readonly></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat2" v-model="detail.discount1"
                                        class="form-control" readonly></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat2" v-model="detail.discount2p"
                                        class="form-control" readonly></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat2" v-model="detail.discount2"
                                        class="form-control" readonly></vue-autonumeric>
                                </td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat2" class="form-control"
                                        :value="totalPrice(index)" readonly></vue-autonumeric>
                                </td>
                                <td>
                                    <input class="form-control" v-model="detail.division" readonly>
                                </td>
                                <td>
                                    <input type="text" v-model="detail.ata" class="form-control" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" v-model="detail.ata_week" readonly>
                                </td>
                                <td>
                                    <input class="form-control" v-model="detail.warehouse" readonly>
                                </td>
                                <td class="text-center">
                                    <button v-if="detail.closed == 1" class="btn btn-secondary"
                                        @click="showClosingDetailReason(detail)">
                                        Show Reason
                                    </button>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-alt-secondary" v-if="detail.image != ''"
                                        @click="viewImage(index)"><i class="fa fa-fw fa-eye"></i></button>
                                    <button type="button" class="btn btn-alt-secondary" disabled v-else><i
                                            class="fa fa-fw fa-eye"></i></button>
                                </td>
                                <td v-for="(n, i) in revCount">
                                    <vue-autonumeric :options="autonumericFormat2" v-model="detail.rev[i]"
                                        class="form-control" readonly
                                        v-if="revData[i].type == 'numeric'"></vue-autonumeric>
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
                    <div class="col-lg-4 col-12 mb-lg-0 mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="Notes" class="form-control" rows="3" readonly>{{ $prs->Notes }}</textarea>
                    </div>

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
                                            <vue-autonumeric :options="getFormat()" name="SubTotal"
                                                class="form-control text-end" :value="getSubTotal"
                                                readonly></vue-autonumeric>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Tax Service</td>
                                        <td class="px-2">
                                            <div class="d-flex flex-row align-items-center">
                                                <vue-autonumeric :options="autonumericFormat2" type="text"
                                                    name="ServiceTax" v-model="pph" class="form-control number-input"
                                                    style="width: 50px" readonly></vue-autonumeric>
                                                <p class="mb-0 ms-1 fs-6">%</p>
                                            </div>
                                        </td>
                                        <td>
                                            <vue-autonumeric :options="getFormat()" class="form-control text-end"
                                                v-model="serviceTax" readonly></vue-autonumeric>
                                        </td>
                                    </tr>
                                    {{--                                    <tr> --}}
                                    {{--                                        <td class="fw-bold">PPh 22</td> --}}
                                    {{--                                        <td class="px-2"> --}}
                                    {{--                                            <div class="d-flex flex-row align-items-center"> --}}
                                    {{--                                                <vue-autonumeric :options="autonumericFormat2" type="text" name="PercentagePPH" v-model="pph22Percentage" class="form-control number-input" style="width: 50px" readonly></vue-autonumeric> --}}
                                    {{--                                                <p class="mb-0 ms-1 fs-6">%</p> --}}
                                    {{--                                            </div> --}}
                                    {{--                                        </td> --}}
                                    {{--                                        <td> --}}
                                    {{--                                            <vue-autonumeric :options="autonumericFormat" name="PPH22" class="form-control text-end" v-model="pph22" readonly></vue-autonumeric> --}}
                                    {{--                                        </td> --}}
                                    {{--                                    </tr> --}}
                                    <tr>
                                        <td class="fw-bold">VAT</td>
                                        <td></td>
                                        <td>
                                            <vue-autonumeric :options="autonumericFormat" name="VATValue"
                                                class="form-control text-end" :value="getVAT"
                                                readonly></vue-autonumeric>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Freight Charge</td>
                                        <td></td>
                                        <td>
                                            <vue-autonumeric :options="autonumericFormat" name="Freight"
                                                class="form-control text-end" v-model="freight"
                                                readonly=""></vue-autonumeric>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Grand Total</td>
                                        <td></td>
                                        <td>
                                            <vue-autonumeric :options="getFormat()" name="GrandTotal"
                                                class="form-control text-end" :value="getGrandTotal"
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
        @include('purchase.prs.close_modal')
        @include('purchase.prs.close_detail_modal')
        @include('purchase.prs.closing_reason_detail_modal')
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
                revCount: {{ $prs->RevCount }},
                selectedImg: '',
                subTotal: 0,
                serviceTax: '{{ $prs->ServiceTax }}',
                pph: '{{ $prs->PercentagePPH }}',
                {{-- //pph22: '{{ old('PPH22') ?? $prs->PPH22 }}', --}}
                pph22: 0,
                {{-- pph22Percentage: '{{ $prs->PPH22 / $prs->SubTotal * 100 }}', --}}
                pph22Percentage: 0,
                freight: {{ $prs->Freight }},
                vatType: '{{ $prs->VAT }}',
                currency: '{{ $prs->CurrencyID }}',
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
                getFormat() {
                    if (this.currency == "IDR") {
                        return this.autonumericFormat;
                    }

                    return this.autonumericFormat2
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

                    this.details[index].totalDiscount = discount;
                    this.details[index].totalPrice = parseFloat(this.details[index].qty) * total;

                    return totalRaw;
                },

                viewImage(index) {
                    this.selectedImg = this.details[index].image;
                    $('#imgModal').modal('show');
                },

                closePrs() {
                    $('#closeModal').modal('show');
                },

                closeDetail(item) {
                    document.querySelector('#closeDetailModal input[name="id"]').value = item.id;

                    $('#closeDetailModal').modal('show');
                },

                openDetail(item) {
                    if (confirm('Are you sure you want to open this detail?')) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `/prs/open-dt/${item.id}`;

                        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_token';
                        csrfInput.value = csrfToken;
                        form.appendChild(csrfInput);

                        document.body.appendChild(form);
                        form.submit();
                    }
                },

                showClosingDetailReason(item) {
                    document.getElementById('closingReasonDetailText').textContent = item.closingReason || '-';
                    const modal = new bootstrap.Modal(document.getElementById('closingReasonDetailModal'));
                    modal.show();
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
                pph() {
                    this.serviceTax = this.pph / 100 * this.subTotal;
                },
                serviceTax() {
                    this.pph = this.serviceTax / this.subTotal * 100;
                },
                pph22() {
                    this.pph22Percentage = this.pph22 / this.subTotal * 100;
                },
                pph22Percentage() {
                    this.pph22 = this.pph22Percentage / 100 * this.subTotal;
                }
            },
            mounted() {}
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
