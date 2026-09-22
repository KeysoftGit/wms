@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Edit Goods Receiving</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <form autocomplete="off" method="post" enctype="multipart/form-data" action="{{ route('gr.update') }}" id="po-form">
            @csrf

            <div class="d-flex flex-row align-items-center mb-5">
                <a href="{{ route('gr') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                <h1 class="h3 fw-bold ms-4 mb-0">
                    Edit Goods Receiving
                </h1>
            </div>

            @if (count($errors->all()) > 0)
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <p class="m-0 fs-6">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <input type="hidden" name="id" value="{{ $gr->TransactionNo }}">
            <input type="hidden" name="rev" v-model="revCount">
            <input type="hidden" name="DetailsJson" :value="detailsJson">
            <div class="block block-rounded">
                <div class="block-content pb-3">
                    <button type="submit" class="btn btn-primary mb-3 fs-6"><i
                            class="fa fa-fw fa-save me-2"></i>Save</button>

                    <div class="d-flex flex-row align-items-center mb-3">
                        <div>
                            <label class="form-label">Transaction No <span class="text-danger">*</span></label>
                            <input type="text" name="TransactionNo" id="TransactionNo" class="form-control"
                                value="{{ old('TransactionNo') ?? $gr->TransactionNo }}" readonly>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-12 pe-lg-5">
                            <div class="row">
                                <div class="col-lg-6 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Transaction Date <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="js-flatpickr form-control js-flatpickr-enabled flatpickr-input active"
                                            id="TransactionDate" name="TransactionDate" placeholder="d/m/Y"
                                            value="{{ old('TransactionDate') ?? date('d/m/Y', strtotime($gr->TransactionDate)) }}"
                                            required readonly>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <div class="mb-3" id="loading-warehouse">
                                        <label class="form-label">Warehouse <span class="text-danger">*</span></label>
                                        <select class="form-select" disabled>
                                            <option id="loading-text">Loading Warehouses.....</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="warehouse-container">
                                        <label class="form-label">Warehouse <span class="text-danger">*</span></label>
                                        <select class="form-select form-select2" name="WarehouseID" id="WarehouseID"
                                            required>
                                            <option value="">-</option>
                                        </select>
                                    </div>
                                </div>
                            </div>


                            <div class="mb-3" id="loading-po">
                                <label class="form-label">Purchase Order <span class="text-danger">*</span></label>
                                <select class="form-select" disabled>
                                    <option id="loading-text">Loading Purchase Orders.....</option>
                                </select>
                            </div>
                            <div class="mb-3" id="po-container">
                                <label class="form-label">Purchase Order <span class="text-danger">*</span></label>
                                <select class="form-select form-select2" name="PONumber" id="PONumber" required>
                                    <option value="">-</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6 col-12 ps-lg-5">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">Currency <span class="text-danger">*</span></label>
                                    <input type="hidden" name="CurrencyID" id="CurrencyID" v-model="currency">
                                    <input type="text" class="form-control" name="CurrencyName" v-model="currencyName"
                                        readonly>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Rate <span class="text-danger">*</span></label>
                                    <vue-autonumeric :options="autonumericFormat2" name="Rate" class="form-control"
                                        v-model="rate" required></vue-autonumeric>
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
                            <button type="button" class="btn btn-primary ms-1" @click="openDetailModal"
                                :disabled="poDetails.length == 0 || !warehouseID"><i class="fa fa-fw fa-list me-1"></i>Choose
                                Part</button>
                            <button type="button" class="btn btn-secondary ms-1" @click="addRev"><i
                                    class="fa fa-fw fa-plus me-1"></i>Add Rev</button>
                            <button type="button" class="btn btn-secondary ms-1" @click="removeRev"><i
                                    class="fa fa-fw fa-minus me-1"></i>Decrease Rev</button>
                        </div>
                    </div>

                    <p class="text-center m-0 p-3" v-if="!isLoading && (po == '' || po == null)">Pick a Purchase Order
                        First</p>
                    <h1 class="text-center" v-if="isLoading"><i class="fa fa-fw fa-circle-notch fa-spin"></i></h1>

                    <div class="table-responsive w-100" v-if="!isLoading && (po != '' && po != null)">
                        <table class="table table-bordered w-100" style="table-layout: fixed; overflow-x: scroll">
                            <thead>
                                <tr>
                                    <th style="width: 150px;">Action</th>
                                    <th style="width: 150px;">Part ID</th>
                                    <th style="width: 300px;">Part Name</th>
                                    <th style="width: 100px;">UnitID</th>
                                    <th style="width: 100px;">UnitID1</th>
                                    <th style="width: 200px;">Qty</th>
                                    <th style="width: 200px;">Receive Qty</th>
                                    <th style="width: 200px;">Batch No</th>
                                    <th v-for="(n, i) in revCount" style="width: 250px;">
                                        <span
                                            v-if="revData[i].name != null && revData[i].name != ''">@{{ revData[i].name }}</span>
                                        <span v-else>Rev @{{ n }}</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="details.length == 0">
                                    <td :colspan="8 + revCount" class="text-center text-muted py-4">
                                        Choose a part from the selected PO to add receiving details.
                                    </td>
                                </tr>
                                <tr v-for="(detail, index) in details">
                                    <td>
                                        <button type="button" class="btn btn-sm btn-secondary me-1" @click="editDetail(index)">
                                            <i class="fa fa-fw fa-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" @click="removeDetail(index)">
                                            <i class="fa fa-fw fa-trash"></i>
                                        </button>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" v-model="detail.PartID" readonly>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" v-model="detail.PartName" readonly>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" v-model="detail.UnitID" readonly>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" v-model="detail.UnitID1" readonly>
                                    </td>
                                    <td>
                                        <vue-autonumeric class="form-control" :options="autonumericFormat2" type="text"
                                            v-model="detail.Qty" readonly></vue-autonumeric>
                                    </td>
                                    <td>
                                        <vue-autonumeric class="form-control" :options="autonumericFormat2" type="text"
                                            v-model="detail.QtyReceive" readonly></vue-autonumeric>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" v-model="detail.BatchNo" readonly>
                                    </td>
                                    <td v-for="(n, i) in revCount">
                                        <input type="text" v-model="detail.rev[i]" readonly class="form-control"
                                            v-if="revData[i].type == 'date'">
                                        <vue-autonumeric :options="autonumericFormat2"
                                            v-model="detail.rev[i]" class="form-control" readonly
                                            v-else-if="revData[i].type == 'numeric'"></vue-autonumeric>
                                        <input type="text" v-model="detail.rev[i]" class="form-control" readonly
                                            v-else-if="revData[i].type == 'select'">
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
                            <textarea name="Notes" class="form-control" rows="3">{{ old('Notes') ?? $gr->Notes }}</textarea>
                        </div>

                        <div class="col-auto" hidden>
                            <table>
                                <tr>
                                    <td class="fw-bold">Grand Total</td>
                                    <td style="width: 10px;"></td>
                                    <td>
                                        <vue-autonumeric :options="getFormat()" name="GrandTotal"
                                            class="form-control text-end auto-format" :value="getGrandTotal"
                                            readonly></vue-autonumeric>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </form>

        <div class="modal fade" id="choose-detail-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Choose PO Detail</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 mb-4">
                                <label class="form-label">Part :</label>
                                <select id="modal-po-detail" class="form-select modal-po-detail-select"></select>
                            </div>
                        </div>

                        <div class="row" v-if="selectedPoDetail">
                            <div class="col-lg-3 col-12 mb-3">
                                <label class="form-label">Part ID</label>
                                <input type="text" class="form-control" :value="selectedPoDetail.PartID" readonly>
                            </div>
                            <div class="col-lg-5 col-12 mb-3">
                                <label class="form-label">Part Name</label>
                                <input type="text" class="form-control" :value="selectedPoDetail.PartName" readonly>
                            </div>
                            <div class="col-lg-2 col-12 mb-3">
                                <label class="form-label">Sequence</label>
                                <input type="text" class="form-control" :value="selectedPoDetail.Sequence" readonly>
                            </div>
                            <div class="col-lg-1 col-12 mb-3">
                                <label class="form-label">Unit</label>
                                <input type="text" class="form-control" :value="selectedPoDetail.Unit" readonly>
                            </div>
                            <div class="col-lg-1 col-12 mb-3">
                                <label class="form-label">UnitID1</label>
                                <input type="text" class="form-control" :value="selectedPoDetail.UnitID1" readonly>
                            </div>
                            <div class="col-lg-6 col-12 mb-3">
                                <label class="form-label">Receive Qty</label>
                                <vue-autonumeric :options="autonumericFormat2" class="form-control"
                                    v-model="modalDetail.QtyReceive"
                                    :readonly="true"></vue-autonumeric>
                            </div>
                            <div class="col-lg-6 col-12 mb-3">
                                <label class="form-label">Batch No</label>
                                <div class="input-group">
                                    <select id="modal-batch-no" class="form-select modal-stock-field"></select>
                                    <button type="button" class="btn btn-alt-secondary" @click="clearStockAttribute('BatchNo')" title="Clear Batch No">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12 mb-3" v-for="(n, i) in revCount">
                                <label class="form-label">
                                    <span v-if="revData[i].name != null && revData[i].name != ''">@{{ revData[i].name }}</span>
                                    <span v-else>Rev @{{ n }}</span>
                                </label>
                                <input type="text" v-model="modalDetail.rev[i]" class="form-control js-modal-flatpickr"
                                    v-if="revData[i].type == 'date'">
                                <vue-autonumeric :options="autonumericFormat2" v-model="modalDetail.rev[i]"
                                    class="form-control" v-else-if="revData[i].type == 'numeric'"></vue-autonumeric>
                                <select2 :url="getRevUrl(n)" v-model="modalDetail.rev[i]" :prevalue="modalDetail.rev[i]"
                                    class="form-select" placeholder="Pick an Item" v-else-if="revData[i].type == 'select'">
                                    <option>-</option>
                                </select2>
                                <input type="text" v-model="modalDetail.rev[i]" class="form-control" v-else>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-alt-secondary" @click="clearAllStockAttributes">
                            <i class="fa fa-fw fa-eraser"></i> Clear Details
                        </button>
                        <div>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" @click="saveSelectedDetail"
                                :disabled="!selectedPoDetail">@{{ modalDetail.editIndex === null ? 'Add Detail' : 'Save Detail' }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

        #choose-detail-modal .modal-po-detail-select,
        #choose-detail-modal .select2-container {
            width: 100% !important;
        }

        #choose-detail-modal .select2-selection--single {
            min-height: 38px;
        }

        #choose-detail-modal .modal-exp-date-picker {
            width: 44px;
            flex: 0 0 44px;
        }

        #choose-detail-modal .input-group .select2-container {
            flex: 1 1 auto;
            min-width: 0;
            width: 1% !important;
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
                firstLoad: false,
                isLoading: false,
                revCount: {{ $gr->RevCount }},
                po: '',
                prevPO: '{{ $gr->qc->PONumber }}',
                warehouseID: '{{ old('WarehouseID') ?? $gr->WarehouseID }}',
                sourceNumber: '{{ old('PONumber') ?? $gr->qc->PONumber }}',
                currency: '',
                currencyName: '',
                poDetails: [],
                details: [],
                modalDetail: {
                    editIndex: null,
                    poIndex: '',
                    QtyReceive: 1,
                    BatchNo: null,
                    SerialNo: null,
                    ExpDate: null,
                    BIN: null,
                    LOC: null,
                    rev: [],
                },
                grandTotal: 0,
                rate: {{ old('Rate') ?? $gr->Rate }},

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
                    decimalPlaces: 2,
                    roundingMethod: 'S',
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
                getDetailKey(detail) {
                    return detail.PartID + '|' + detail.Sequence;
                },
                getAddedQty(detail) {
                    let total = 0;
                    let key = this.getDetailKey(detail);
                    for (var i = 0; i < this.details.length; i++) {
                        if (this.modalDetail.editIndex !== null && i === this.modalDetail.editIndex) {
                            continue;
                        }
                        if (this.getDetailKey(this.details[i]) == key) {
                            total += Number(this.details[i].QtyReceive || 0);
                        }
                    }

                    return total;
                },
                getRemainingForDetail(detail) {
                    return Number(detail.QtyRemaining || 0) - this.getAddedQty(detail);
                },
                resetModalDetail() {
                    this.modalDetail = {
                        editIndex: null,
                        poIndex: '',
                        QtyReceive: 1,
                        BatchNo: null,
                        SerialNo: null,
                        ExpDate: null,
                        BIN: null,
                        LOC: null,
                        rev: Array(this.revCount).fill(''),
                    };
                    this.resetModalSelects();
                },
                resetModalSelects() {
                    $('#modal-po-detail, #modal-batch-no, #modal-exp-date, #modal-bin')
                        .val(null)
                        .trigger('change.select2');
                },
                openDetailModal() {
                    this.resetModalDetail();
                    $('#choose-detail-modal').modal('show');
                    setTimeout(initModalPartSelect, 50);
                },
                editDetail(index) {
                    let row = this.details[index];
                    let rowKey = this.getDetailKey(row);

                    this.modalDetail = {
                        editIndex: index,
                        poIndex: rowKey,
                        QtyReceive: row.QtyReceive,
                        BatchNo: row.BatchNo,
                        SerialNo: row.SerialNo,
                        ExpDate: row.ExpDate,
                        BIN: row.BIN,
                        LOC: row.LOC,
                        rev: JSON.parse(JSON.stringify(row.rev || [])),
                    };

                    while (this.modalDetail.rev.length < this.revCount) {
                        this.modalDetail.rev.push('');
                    }

                    $('#choose-detail-modal').modal('show');
                    setTimeout(function() {
                        initModalPartSelect();
                        setModalSelectValue('#modal-po-detail', rowKey);
                        initModalAttributeSelects();
                        setModalSelectValue('#modal-batch-no', row.BatchNo);
                        setModalSelectValue('#modal-exp-date', row.ExpDate);
                        setModalSelectValue('#modal-bin', row.BIN);
                        app.modalDetail.LOC = row.LOC;
                    }, 100);
                },
                onModalPartChanged() {
                    let detail = this.selectedPoDetail;
                    let remaining = detail ? this.getRemainingForDetail(detail) : 0;
                    this.modalDetail.QtyReceive = detail
                        ? (false ? 1 : remaining)
                        : null;
                    this.modalDetail.BatchNo = null;
                    this.modalDetail.SerialNo = null;
                    this.modalDetail.ExpDate = null;
                    this.modalDetail.BIN = null;
                    this.modalDetail.LOC = null;
                    this.resetModalSelects();
                    setTimeout(function() {
                        initModalAttributeSelects();
                    }, 50);
                },
                clearStockAttribute(field) {
                    const selectors = {
                        BatchNo: '#modal-batch-no',
                        ExpDate: '#modal-exp-date',
                        BIN: '#modal-bin',
                    };

                    this.modalDetail[field] = null;
                    if (selectors[field]) {
                        $(selectors[field]).val(null).trigger('change.select2');
                    }
                    if (field === 'ExpDate') {
                    }
                    if (field === 'BIN') {
                        this.modalDetail.LOC = null;
                    }
                },
                clearAllStockAttributes() {
                    ['BatchNo', 'SerialNo', 'ExpDate', 'BIN', 'LOC'].forEach(field => this.clearStockAttribute(field));
                },
                saveSelectedDetail() {
                    let detail = this.selectedPoDetail;
                    if (!detail) {
                        return;
                    }

                    let remaining = this.getRemainingForDetail(detail);
                    let qtyReceive = Number(remaining || 0);
                    if (false) {
                        qtyReceive = 1;
                    }

                    if (qtyReceive <= 0 || qtyReceive > remaining) {
                        Swal.fire('Invalid Qty', 'Receive Qty must be greater than 0 and must not exceed available qty.', 'warning');
                        return;
                    }

                    let row = JSON.parse(JSON.stringify(detail));
                    row.QtyReceive = qtyReceive;
                    row.BatchNo = this.modalDetail.BatchNo;
                    row.SerialNo = false ? this.modalDetail.SerialNo : null;
                    row.ExpDate = null;
                    row.BIN = null;
                    row.LOC = null;
                    row.rev = JSON.parse(JSON.stringify(this.modalDetail.rev || []));
                    while (row.rev.length < this.revCount) {
                        row.rev.push('');
                    }

                    if (this.modalDetail.editIndex === null) {
                        this.details.push(row);
                    } else {
                        this.details.splice(this.modalDetail.editIndex, 1, row);
                    }
                    $('#choose-detail-modal').modal('hide');
                },
                removeDetail(index) {
                    this.details.splice(index, 1);
                },

                getDetail() {
                    this.isLoading = true;
                    let app = this;
                    $.ajax({
                        url: '{{ route('gr.po.detail') }}',
                        type: 'GET',
                        data: {
                            id: app.po,
                            grID: '{{ $gr->TransactionNo }}',
                            prevPO: app.prevPO,
                            warehouse_id: app.warehouseID,
                        }
                    }).then(function(result) {
                        if (result.status == 'success') {
                            app.poDetails = result.data;
                            if (app.firstLoad) {
                                app.revCount = result.rev;
                                app.rate = result.rate;
                                app.details = [];
                            } else {
                                app.firstLoad = true;
                                app.details = result.selected_data || [];
                            }

                            app.currency = result.currency;
                            app.currencyName = result.currencyName;

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
                    }).always(function() {
                        app.isLoading = false;
                    });
                },
            },
            computed: {
                availablePoDetails() {
                    return this.poDetails.filter((detail) => this.getRemainingForDetail(detail) > 0);
                },
                modalPoDetails() {
                    return this.poDetails.filter((detail) => this.getRemainingForDetail(detail) > 0);
                },
                selectedPoDetail() {
                    if (this.modalDetail.poIndex === '') {
                        return null;
                    }

                    return this.modalPoDetails.find((detail) => this.getDetailKey(detail) == this.modalDetail.poIndex) || null;
                },
                detailsJson() {
                    return JSON.stringify(this.details);
                },
                getGrandTotal() {
                    this.grandTotal = 0;
                    for (var i = 0; i < this.details.length; i++) {
                        this.grandTotal += (this.details[i].UnitPrice * this.details[i].QtyReceive);
                    }

                    return this.grandTotal;
                },
            },
            watch: {
                warehouseID(newWarehouse, oldWarehouse) {
                    if (oldWarehouse && newWarehouse !== oldWarehouse) {
                        this.po = '';
                        this.sourceNumber = '';
                        this.poDetails = [];
                        this.details = [];
                        $('#PONumber').val(null).trigger('change.select2');
                        loadPOOptions(newWarehouse, null);
                    }
                },
                currency() {
                    setTimeout(function() {
                        $('.auto-format').attr('readonly', true);
                    }, 1);
                },
            },
            mounted() {
                let app = this;

                $('#PONumber').on('change', function() {
                    app.po = $(this).val();
                    app.sourceNumber = app.po;
                    if (app.po != null && app.po != '') {
                        app.getDetail();
                    }
                });

                $('#WarehouseID').on('change', function() {
                    app.warehouseID = $(this).val();
                });
            }
        });

        function setModalSelectValue(selector, value) {
            if (value === null || value === undefined || value === '') {
                $(selector).val(null).trigger('change.select2');
                return;
            }

            if ($(selector + ' option[value="' + value + '"]').length == 0) {
                $(selector).append(new Option(value, value, true, true));
            }

            $(selector).val(value).trigger('change.select2');
        }

        function initModalPartSelect() {
            if ($('#modal-po-detail').hasClass('select2-hidden-accessible')) {
                $('#modal-po-detail').select2('destroy');
            }

            $('#modal-po-detail').empty().append(new Option('', '', false, false));
            $('#modal-po-detail').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#choose-detail-modal'),
                placeholder: 'Search PO detail',
                allowClear: true,
                data: app.modalPoDetails.map(function(detail) {
                    const stockInfo = [];
                    if (detail.BatchNo) stockInfo.push('Batch: ' + detail.BatchNo);
                    if (detail.SerialNo) stockInfo.push('Serial: ' + detail.SerialNo);
                    if (detail.ExpDate) stockInfo.push('Exp: ' + detail.ExpDate);
                    if (detail.BIN) stockInfo.push('BIN: ' + detail.BIN);
                    if (detail.LOC) stockInfo.push('LOC: ' + detail.LOC);

                    return {
                        id: app.getDetailKey(detail),
                        text: detail.PartID + ' - ' + detail.PartName + ' | Seq : ' + detail.Sequence + (stockInfo.length ? ' | ' + stockInfo.join(' | ') : '') + ' | Remaining : ' + app.getRemainingForDetail(detail),
                    };
                }),
                matcher: function(params, data) {
                    if ($.trim(params.term) === '') {
                        return data;
                    }

                    const detail = app.modalPoDetails.find(function(row) {
                        return app.getDetailKey(row) === data.id;
                    });
                    const term = params.term.toLowerCase();
                    const haystack = [
                        data.text,
                        detail ? detail.PartID : '',
                        detail ? detail.PartName : '',
                        detail ? detail.Sequence : '',
                        detail ? detail.BatchNo : '',
                        detail ? detail.SerialNo : '',
                        detail ? detail.ExpDate : '',
                        detail ? detail.BIN : '',
                        detail ? detail.LOC : '',
                    ].join(' ').toLowerCase();

                    return haystack.indexOf(term) > -1 ? data : null;
                }
            }).off('change.modal-part').on('change.modal-part', function() {
                app.modalDetail.poIndex = $(this).val() || '';
                app.onModalPartChanged();
            });

            if (app.modalDetail.poIndex) {
                $('#modal-po-detail').val(app.modalDetail.poIndex).trigger('change.select2');
            } else {
                $('#modal-po-detail').val(null).trigger('change.select2');
            }
        }

        function initModalAttributeSelect(selector, url, field, extraOptions = {}) {
            if ($(selector).hasClass('select2-hidden-accessible')) {
                $(selector).select2('destroy');
            }

            const config = {
                theme: 'bootstrap-5',
                dropdownParent: $('#choose-detail-modal'),
                placeholder: extraOptions.placeholder || 'Pick an Item',
                allowClear: true,
                ajax: {
                    url: url,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            part_id: app.selectedPoDetail ? app.selectedPoDetail.PartID : '',
                            warehouse_id: app.warehouseID,
                            filter_warehouse: extraOptions.filterWarehouse ? 1 : 0,
                            search: params.term || '',
                        };
                    },
                    processResults: function(response) {
                        return {
                            results: response
                        };
                    },
                    cache: true
                }
            };

            if (extraOptions.tags) {
                config.tags = true;
                config.createTag = function(params) {
                    const term = $.trim(params.term);
                    if (term === '') {
                        return null;
                    }

                    return {
                        id: term,
                        text: term,
                        newTag: true
                    };
                };
            }

            $(selector).select2(config).off('change.modal-attribute').on('change.modal-attribute', function() {
                app.modalDetail[field] = $(this).val() || null;

                if (field === 'BIN') {
                    const selected = $(this).select2('data')[0];
                    app.modalDetail.LOC = selected && selected.loc ? selected.loc : null;
                }

            });
        }

        function initModalAttributeSelects() {
            initModalAttributeSelect('#modal-batch-no', '{{ route('misc.batch_no') }}', 'BatchNo');
            $('#modal-batch-no').prop('disabled', false);
        }

        $('#choose-detail-modal').on('shown.bs.modal', function() {
            initModalAttributeSelects();
            $('.js-modal-flatpickr').flatpickr({
                dateFormat: "d/m/Y",
            });
        });

        $('.form-select2').select2({
            theme: 'bootstrap-5'
        });

        $('#warehouse-container').hide();
        $('#po-container').hide();

        $('#automatic').on('change', function() {
            if ($('#automatic').is(':checked')) {
                $('#TransactionNo').attr('disabled', true);
            } else {
                $('#TransactionNo').removeAttr('disabled');
            }
        });

        // let rate = new AutoNumeric('#Rate', {
        //     decimalPlaces: 6,
        //     minimumValue: 1,
        //     digitGroupSeparator: '.',
        //     decimalCharacter: ',',
        //     allowDecimalPadding: "false",
        //     modifyValueOnWheel: false,
        //     unformatOnSubmit: true
        // });




        // LOAD WAREHOUSE

        $.ajax({
            url: '{!! route('misc.warehouse', ['parent_only' => true]) !!}',
            type: 'GET',
        }).done(function(data) {
            if (data.status == 'success') {
                var options = '';
                var oldID = '{{ old('WarehouseID') ?? $gr->WarehouseID }}';
                for (var i = 0; i < data.data.length; i++) {
                    options +=
                        `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                }

                $('#WarehouseID').append(options);

                $('#loading-warehouse').hide();
                $('#warehouse-container').show();
            } else {
                $('#loading-text').html('Something went wrong!');
            }
        });

        function loadPOOptions(warehouseID, selectedPO) {
            $('#PONumber').empty().append('<option value="">-</option>');

            $.ajax({
                url: '{!! route('gr.po') !!}',
                type: 'GET',
                data: {
                    inv: selectedPO,
                    warehouse_id: warehouseID,
                    require_warehouse: true,
                }
            }).done(function(data) {
                if (data.status == 'success') {
                    var options = '';
                    for (var i = 0; i < data.data.length; i++) {
                        options +=
                            `<option value="${data.data[i].id}" ${ selectedPO == data.data[i].id ? 'selected' : '' }>${data.data[i].text}</option>`;
                    }

                    $('#PONumber').append(options);

                    $('#loading-po').hide();
                    $('#po-container').show();

                    if (selectedPO) {
                        $('#PONumber').trigger('change');
                    }
                } else {
                    $('#loading-text').html('Something went wrong!');
                }
            });
        }

        // LOAD PO
        loadPOOptions('{{ old('WarehouseID') ?? $gr->WarehouseID }}', '{{ old('PONumber') ?? $gr->qc->PONumber }}');
    </script>
@endsection
