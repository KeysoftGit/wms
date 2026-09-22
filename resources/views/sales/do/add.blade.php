@extends('layouts.admin')

@section('titles')
    <title>Keyonline - Add Advanced Delivery Order</title>
@endsection

@section('content')
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <form autocomplete="off" method="post" enctype="multipart/form-data" action="{{ route('do.store') }}" id="do-form">
            @csrf
            <input type="hidden" name="rev" v-model="revCount">
            <input type="hidden" name="DetailsJson" :value="JSON.stringify(details)">

            <div class="d-flex flex-row align-items-center mb-5">
                <a href="{{ route('do') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                <h1 class="h3 fw-bold ms-4 mb-0">Add Delivery Order</h1>
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

                    <div class="row align-items-center mb-3">
                        <div class="col-lg-2 col-12 pe-lg-0">
                            <label class="form-label">Transaction No <span class="text-danger">*</span></label>
                            <input type="text" name="TransactionNo" id="TransactionNo" class="form-control" value="{{ old('TransactionNo') ?? '' }}" required {{ old('automatic') ? 'disabled' : (count($errors->all()) > 0 ? '' : 'disabled') }}>
                        </div>
                        <div class="col-lg-auto col-12 px-lg-0">
                            <label class="form-label"></label>
                            <div class="form-check ms-lg-4 pt-lg-2">
                                <input class="form-check-input fs-6" type="checkbox" value="1" name="automatic" id="automatic" {{ old('automatic') ? 'checked' : (count($errors->all()) > 0 ? '' : 'checked') }}>
                                <label class="form-check-label fs-6" for="automatic">Automatic</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-12 pe-lg-5">
                            <div class="row mb-3">
                                <div class="col-lg-4 col-12 mb-lg-0 mb-3">
                                    <label class="form-label">Transaction Date <span class="text-danger">*</span></label>
                                    <input type="text" class="js-flatpickr form-control" id="TransactionDate" name="TransactionDate" placeholder="d/m/Y" value="{{ old('TransactionDate') ?? '' }}" readonly required>
                                </div>
                                <div class="col-lg-4 col-12 mb-lg-0 mb-3">
                                    <label class="form-label">Expired <span class="text-danger">*</span></label>
                                    <input type="text" class="js-flatpickr form-control" id="ExpiredDate" name="ExpiredDate" placeholder="d/m/Y" value="{{ old('ExpiredDate') ?? '' }}" readonly required>
                                </div>
                                <div class="col-lg-4 col-12">
                                    <label class="form-label">ETA <span class="text-danger">*</span></label>
                                    <input type="text" class="js-flatpickr form-control" id="ETA" name="ETA" placeholder="d/m/Y" value="{{ old('ETA') ?? '' }}" readonly required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Customer <span class="text-danger">*</span></label>
                                <select2 url="{{ route('misc.customer', ['select2' => true, 'realID' => true]) }}" v-model="CustomerID" :prevalue="CustomerID" @selected_text="CustomerText = $event" class="form-select" id="CustomerID" name="CustomerID" placeholder="Pick Customer" required>
                                    <option>-</option>
                                    <option v-if="CustomerID && CustomerText" :value="CustomerID" selected>@{{ CustomerText }}</option>
                                </select2>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Sales Order <span class="text-danger">*</span></label>
                                <select2 :url="soURL" v-model="ReffNumber" :prevalue="ReffNumber" @selected_text="ReffNumberText = $event" class="form-select" id="ReffNumber" name="ReffNumber" placeholder="Pick Sales Order" :disabled="!CustomerID" required>
                                    <option>-</option>
                                    <option v-if="ReffNumber && ReffNumberText" :value="ReffNumber" selected>@{{ ReffNumberText }}</option>
                                </select2>
                            </div>
                        </div>
                        <div class="col-lg-6 col-12 ps-lg-5">
                            <div class="mb-3">
                                <label class="form-label">Customer's Shipment Address</label>
                                <select2 :url="addressUrl" v-model="Shipment" :prevalue="Shipment" id="Shipment" class="form-select" name="Shipment" placeholder="Pick Address" :disabled="!CustomerID">
                                    <option>-</option>
                                </select2>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Address <span class="text-danger">*</span></label>
                                <textarea name="ShipmentAddress" id="ShipmentAddress" class="form-control" rows="4" v-model="ShipmentAddress" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block block-rounded">
                <div class="block-content">
                    <div class="d-flex flex-row justify-content-between align-items-center mb-3">
                        <h5 class="m-0">Detail</h5>
                        <div class="d-flex flex-row align-items-center">
                            <p class="mb-0 me-2" v-if="isLoading"><i class="fa fa-fw fa-spin fa-circle-notch"></i></p>
                            <button type="button" class="btn btn-primary ms-1" @click="openDetailModal" :disabled="isLoading || !ReffNumber || modalSoDetails.length == 0">
                                <i class="fa fa-fw fa-list me-1"></i>Choose Part
                            </button>
                            <button type="button" class="btn btn-secondary ms-1" @click="addRev" :disabled="details.length == 0"><i class="fa fa-fw fa-plus me-1"></i>Add Rev</button>
                            <button type="button" class="btn btn-secondary ms-1" @click="removeRev" :disabled="details.length == 0"><i class="fa fa-fw fa-minus me-1"></i>Decrease Rev</button>
                        </div>
                    </div>

                    <div class="table-responsive w-100" style="overflow-x: scroll">
                        <table class="table table-bordered w-100" style="table-layout: fixed; overflow-x: scroll">
                            <thead>
                            <tr>
                                <th style="width: 150px;">Action</th>
                                <th style="width: 150px;">Part ID</th>
                                <th style="width: 220px;">Part Name</th>
                                <th style="width: 100px;">UnitID</th>
                                <th style="width: 120px;">Qty</th>
                                <th style="width: 160px;">Remaining Qty</th>
                                <th style="width: 160px;">Deliver Qty</th>
                                <th style="width: 160px;">Warehouse</th>
                                <th style="width: 160px;">Batch No</th>
                                <th style="width: 160px;">Division</th>
                                <th v-for="(n, i) in revCount" style="width: 250px;">
                                    <span v-if="revData[i].name != null && revData[i].name != ''">@{{ revData[i].name }}</span>
                                    <span v-else>Rev @{{ n }}</span>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-if="details.length == 0">
                                <td :colspan="14 + revCount" class="text-center p-3">No Details Added Yet</td>
                            </tr>
                            <tr v-for="(detail, index) in details" :key="index">
                                <td>
                                    <button type="button" class="btn btn-sm btn-secondary me-1" @click="editDetail(index)"><i class="fa fa-fw fa-pencil"></i></button>
                                    <button type="button" class="btn btn-sm btn-danger" @click="removeDetail(index)"><i class="fa fa-fw fa-trash"></i></button>
                                </td>
                                <td><input type="text" class="form-control" v-model="detail.PartID" readonly></td>
                                <td><input type="text" class="form-control" v-model="detail.PartName" readonly></td>
                                <td><input type="text" class="form-control" v-model="detail.UnitID1" readonly></td>
                                <td><vue-autonumeric class="form-control" :options="autonumericFormat2" v-model="detail.Qty" readonly></vue-autonumeric></td>
                                <td><vue-autonumeric class="form-control" :options="autonumericFormat2" v-model="detail.QtyRemaining" readonly></vue-autonumeric></td>
                                <td><vue-autonumeric class="form-control" :options="autonumericFormat2" v-model="detail.QtyDeliver" readonly></vue-autonumeric></td>
                                <td><input type="text" class="form-control" v-model="detail.warehouse" readonly></td>
                                <td><input type="text" class="form-control" :value="displayStockValue(detail.BatchNo)" readonly></td>
                                <td><input type="text" class="form-control" v-model="detail.division" readonly></td>
                                <td v-for="(n, i) in revCount">
                                    <input type="text" v-model="detail.rev[i]" readonly class="form-control" v-if="revData[i].type == 'date'">
                                    <vue-autonumeric :options="autonumericFormat2" v-model="detail.rev[i]" class="form-control" readonly v-else-if="revData[i].type == 'numeric'"></vue-autonumeric>
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
                            <textarea name="Notes" class="form-control" rows="3" v-model="Notes"></textarea>
                        </div>

                        <div class="col-auto" hidden>
                            <vue-autonumeric :options="autonumericFormat2" name="SubTotal" class="form-control text-end" :value="getGrandTotal" readonly></vue-autonumeric>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="modal fade" id="choose-detail-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Choose SO Detail</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 mb-4">
                                <label class="form-label">Part :</label>
                                <select id="modal-so-detail" class="form-select modal-so-detail-select"></select>
                            </div>
                        </div>

                        <div class="row" v-if="selectedSoDetail">
                            <div class="col-lg-3 col-12 mb-3">
                                <label class="form-label">Part ID</label>
                                <input type="text" class="form-control" :value="selectedSoDetail.PartID" readonly>
                            </div>
                            <div class="col-lg-5 col-12 mb-3">
                                <label class="form-label">Part Name</label>
                                <input type="text" class="form-control" :value="selectedSoDetail.PartName" readonly>
                            </div>
                            <div class="col-lg-2 col-12 mb-3">
                                <label class="form-label">Sequence</label>
                                <input type="text" class="form-control" :value="selectedSoDetail.Sequence" readonly>
                            </div>
                            <div class="col-lg-2 col-12 mb-3">
                                <label class="form-label">UnitID</label>
                                <input type="text" class="form-control" :value="selectedSoDetail.UnitID1" readonly>
                            </div>
                            <div class="col-lg-4 col-12 mb-3">
                                <label class="form-label">Warehouse</label>
                                <select id="modal-warehouse" class="form-select modal-stock-field"></select>
                            </div>
                            <div class="col-lg-4 col-12 mb-3">
                                <label class="form-label">Remaining Qty</label>
                                <input type="text" class="form-control" :value="getRemainingForDetail(selectedSoDetail)" readonly>
                            </div>
                            <div class="col-lg-4 col-12 mb-3">
                                <label class="form-label">Deliver Qty</label>
                                <vue-autonumeric :options="autonumericFormat2" class="form-control"
                                    v-model="modalDetail.QtyDeliver"
                                    :readonly="false"></vue-autonumeric>
                            </div>
                            <div class="col-lg-4 col-12 mb-3">
                                <label class="form-label">Batch No</label>
                                <div class="input-group">
                                    <select id="modal-batch-no" class="form-select modal-stock-field"></select>
                                    <button type="button" class="btn btn-alt-secondary" @click="clearStockAttribute('BatchNo')" title="Clear Batch No"><i class="fa fa-times"></i></button>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12 mb-3" v-for="(n, i) in revCount">
                                <label class="form-label">
                                    <span v-if="revData[i].name != null && revData[i].name != ''">@{{ revData[i].name }}</span>
                                    <span v-else>Rev @{{ n }}</span>
                                </label>
                                <input type="text" v-model="modalDetail.rev[i]" class="form-control js-modal-flatpickr" v-if="revData[i].type == 'date'">
                                <vue-autonumeric :options="autonumericFormat2" v-model="modalDetail.rev[i]" class="form-control" v-else-if="revData[i].type == 'numeric'"></vue-autonumeric>
                                <select2 :url="getRevUrl(n)" v-model="modalDetail.rev[i]" :prevalue="modalDetail.rev[i]" class="form-select" placeholder="Pick an Item" v-else-if="revData[i].type == 'select'">
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
                            <button type="button" class="btn btn-primary" @click="saveSelectedDetail" :disabled="!selectedSoDetail">
                                @{{ modalDetail.editIndex === null ? 'Add Detail' : 'Save Detail' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/sweetalert2/sweetalert2.min.css') }}">
    <style>
        th { white-space: nowrap; }
        #choose-detail-modal .modal-so-detail-select,
        #choose-detail-modal .select2-container { width: 100% !important; }
        #choose-detail-modal .select2-selection--single { min-height: 38px; }
        #choose-detail-modal .modal-exp-date-picker { width: 44px; flex: 0 0 44px; }
        #choose-detail-modal .input-group .select2-container {
            flex: 1 1 auto;
            min-width: 0;
            width: 1% !important;
        }
    </style>
@endsection

@section('scripts')
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('js/plugins/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('js/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.5.4"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.13/dist/vue.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.0/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue-autonumeric@1.2.6/dist/vue-autonumeric.min.js"></script>
    <script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.js') }}"></script>

    <script type="text/x-template" id="select2-template">
        <select>
            <slot></slot>
        </select>
    </script>
    <script src="{{ asset('js/vueComponent-select2.js') }}"></script>

    <script>
        const availableStockDetailsUrl = @json(route('helper.available_stock_details'));
        const nullFilterValue = '__NULL__';
        const modalDetailColumnMap = {
            batch_no: 'BatchNo',
            serial_no: 'SerialNo',
            exp_date: 'ExpDate',
            bin: 'BIN',
            loc: 'LOC',
        };

        let app = new Vue({
            el: '#vue-container',
            data: {
                revData: @json($revData),
                revCount: 0,
                isLoading: false,
                isDraftInitializing: false,
                CustomerID: '{{ old('CustomerID') ?? '' }}',
                CustomerText: '',
                ReffNumber: '{{ old('ReffNumber') ?? '' }}',
                ReffNumberText: '',
                Shipment: '{{ old('Shipment') ?? '' }}',
                ShipmentAddress: '{{ old('ShipmentAddress') ?? '' }}',
                Notes: '{{ old('Notes') ?? '' }}',
                addressUrl: '',
                soURL: '',
                modalSoDetails: [],
                details: [],
                modalDetail: {
                    editIndex: null,
                    soIndex: '',
                    QtyDeliver: 1,
                    StockQty: null,
                    warehouse: null,
                    BatchNo: null,
                    SerialNo: null,
                    ExpDate: null,
                    BIN: null,
                    LOC: null,
                    rev: [],
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
            computed: {
                selectedSoDetail() {
                    return this.modalSoDetails.find(row => this.getDetailKey(row) === this.modalDetail.soIndex) || null;
                },
                getGrandTotal() {
                    return this.details.reduce((total, detail) => total + (Number(detail.Price || 0) * Number(detail.QtyDeliver || 0)), 0);
                },
            },
            methods: {
                addRev() {
                    if (this.revCount < 15) {
                        this.revCount++;
                        this.details.forEach(detail => detail.rev.push(''));
                        this.modalDetail.rev.push('');
                        setTimeout(initRevDatepickers, 50);
                    }
                },
                removeRev() {
                    if (this.revCount > 0) {
                        this.revCount--;
                        this.details.forEach(detail => detail.rev.splice(this.revCount, 1));
                        this.modalDetail.rev.splice(this.revCount, 1);
                    }
                },
                getRevUrl(index) {
                    return '{{ route('rev.select') }}?type=SALES&index=' + index;
                },
                getDetailKey(detail) {
                    return detail.PartID + '|' + detail.Sequence;
                },
                getAddedQty(detail) {
                    const key = this.getDetailKey(detail);
                    return this.details.reduce((total, row, index) => {
                        if (this.modalDetail.editIndex !== null && index === this.modalDetail.editIndex) {
                            return total;
                        }

                        return this.getDetailKey(row) === key ? total + Number(row.QtyDeliver || 0) : total;
                    }, 0);
                },
                getRemainingForDetail(detail) {
                    return Number(detail.QtyRemaining || 0) - this.getAddedQty(detail);
                },
                getAddressfromCustomer(shipment) {
                    $.ajax({
                        url: '{{ route('do.so.address') }}',
                        type: 'GET',
                        data: {
                            id: this.CustomerID,
                            shipment: shipment,
                            numId: true,
                        }
                    }).then(result => {
                        this.ShipmentAddress = result.address;
                        this.Shipment = '';
                    });
                },
                getAddress() {
                    $.ajax({
                        url: '{{ route('do.so.address') }}',
                        type: 'GET',
                        data: { id: this.ReffNumber }
                    }).then(result => {
                        this.ShipmentAddress = result.address;
                    });
                },
                getDetail() {
                    this.isLoading = true;
                    $.ajax({
                        url: '{{ route('do.so.detail') }}',
                        type: 'GET',
                        data: { id: this.ReffNumber }
                    }).then(result => {
                        if (result.status === 'success') {
                            this.revCount = result.rev;
                            this.modalSoDetails = result.data || [];
                            this.details = [];
                        }
                    }).always(() => {
                        this.isLoading = false;
                    });
                },
                resetModalDetail() {
                    this.modalDetail = {
                        editIndex: null,
                        soIndex: '',
                        QtyDeliver: 1,
                        StockQty: null,
                        warehouse: null,
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
                    $('#modal-so-detail, #modal-warehouse, #modal-batch-no, #modal-serial-no, #modal-exp-date, #modal-bin, #modal-loc')
                        .val(null)
                        .trigger('change.select2');
                },
                openDetailModal() {
                    this.resetModalDetail();
                    $('#choose-detail-modal').modal('show');
                    setTimeout(initModalPartSelect, 50);
                },
                editDetail(index) {
                    const row = this.details[index];
                    const rowKey = this.getDetailKey(row);

                    this.modalDetail = {
                        editIndex: index,
                        soIndex: rowKey,
                        QtyDeliver: row.QtyDeliver,
                        StockQty: row.StockQty || null,
                        warehouse: row.warehouse,
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
                        setModalSelectValue('#modal-so-detail', rowKey);
                        initModalAttributeSelects();
                        setModalSelectValue('#modal-warehouse', row.warehouse);
                        setModalSelectValue('#modal-batch-no', row.BatchNo);
                        setModalSelectValue('#modal-serial-no', row.SerialNo);
                        setModalSelectValue('#modal-exp-date', row.ExpDate);
                        setModalSelectValue('#modal-bin', row.BIN);
                        setModalSelectValue('#modal-loc', row.LOC);
                        app.fetchModalStockQty();
                    }, 100);
                },
                onModalPartChanged() {
                    const detail = this.selectedSoDetail;
                    this.modalDetail.QtyDeliver = detail && false ? 1 : null;
                    this.modalDetail.StockQty = null;
                    this.modalDetail.warehouse = null;
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
                onStockAttributeChanged(changedField) {
                    this.refreshModalStockQty(changedField);
                },
                refreshModalStockQty(changedField) {
                    const selectors = {
                        BatchNo: '#modal-batch-no',
                        SerialNo: '#modal-serial-no',
                        ExpDate: '#modal-exp-date',
                        BIN: '#modal-bin',
                        LOC: '#modal-loc',
                    };
                    const selector = selectors[changedField];
                    const selected = selector ? ($(selector).select2('data')[0] || null) : null;
                    this.modalDetail.StockQty = selected && selected.total_qty !== undefined ? Number(selected.total_qty || 0) : null;
                    if (this.modalDetail.StockQty === null) {
                        this.fetchModalStockQty();
                    }
                },
                fetchModalStockQty() {
                    const target = modalStockQtyTarget();
                    if (!target || !this.selectedSoDetail || !this.modalDetail.warehouse) {
                        this.modalDetail.StockQty = null;
                        return Promise.resolve(null);
                    }

                    const requestData = stockQtyRequestData(target);
                    const selectedValue = requestData.selectedValue;
                    return $.ajax({
                        url: availableStockDetailsUrl,
                        type: 'GET',
                        data: requestData.data,
                    }).then(response => {
                        const column = modalDetailColumnMap[target];
                        const rows = response && response.data && response.data.results ? response.data.results : [];
                        const row = rows.find(item => normalizeStockOptionValue(item[column]) === selectedValue);
                        this.modalDetail.StockQty = row && row.total_qty !== undefined ? Number(row.total_qty || 0) : null;
                        return this.modalDetail.StockQty;
                    });
                },
                clearStockAttribute(field) {
                    const selectors = {
                        BatchNo: '#modal-batch-no',
                        SerialNo: '#modal-serial-no',
                        ExpDate: '#modal-exp-date',
                        BIN: '#modal-bin',
                        LOC: '#modal-loc',
                    };

                    this.modalDetail[field] = null;
                    if (selectors[field]) {
                        $(selectors[field]).val(null).trigger('change.select2');
                    }
                    if (field === 'ExpDate') {
                    }
                    this.modalDetail.StockQty = null;
                },
                clearAllStockAttributes() {
                    ['BatchNo', 'SerialNo', 'ExpDate', 'BIN', 'LOC'].forEach(field => this.clearStockAttribute(field));
                },
                async saveSelectedDetail() {
                    const detail = this.selectedSoDetail;
                    if (!detail) {
                        return;
                    }

                    const remaining = this.getRemainingForDetail(detail);
                    let qtyDeliver = Number(this.modalDetail.QtyDeliver || 0);
                    if (false) {
                        qtyDeliver = 1;
                    }

                    if (!this.modalDetail.warehouse) {
                        Swal.fire('Warehouse Required', 'Warehouse is required.', 'warning');
                        return;
                    }

                    if (qtyDeliver <= 0 || qtyDeliver > remaining) {
                        Swal.fire('Invalid Qty', 'Deliver Qty must be greater than 0 and must not exceed remaining qty.', 'warning');
                        return;
                    }

                    let stockQty = null;
                    try {
                        stockQty = await this.fetchModalStockQty();
                    } catch (error) {
                        Swal.fire('Stock Check Failed', 'Unable to validate available stock. Please try again.', 'warning');
                        return;
                    }

                    if (stockQty === null) {
                        Swal.fire('Stock Details Required', 'Please choose stock details so available stock can be validated.', 'warning');
                        return;
                    }

                    if (qtyDeliver > Number(stockQty || 0)) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Not Enough Stock',
                            html: notEnoughStockMessage(detail),
                        });
                        return;
                    }

                    let row = JSON.parse(JSON.stringify(detail));
                    row.QtyDeliver = qtyDeliver;
                    row.StockQty = stockQty;
                    row.warehouse = this.modalDetail.warehouse;
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
                displayStockValue(value) {
                    return value === '__NULL__' ? '' : (value || '');
                },
            },
            watch: {
                CustomerID(val, oldVal) {
                    if (this.isDraftInitializing) {
                        return;
                    }

                    this.addressUrl = '{{ route('misc.shipment') }}?id=' + this.CustomerID + '&numId=true';
                    this.soURL = '{!! route('do.so') !!}?customer=' + this.CustomerID;

                    if (oldVal) {
                        this.ReffNumber = '';
                        this.details = [];
                        this.modalSoDetails = [];
                    }
                },
                ReffNumber(val, oldVal) {
                    if (this.isDraftInitializing) {
                        return;
                    }

                    if (oldVal) {
                        this.details = [];
                        this.modalSoDetails = [];
                    }

                    if (this.ReffNumber != '') {
                        this.getAddress();
                        this.getDetail();
                    }
                },
                Shipment(val) {
                    if (this.isDraftInitializing) {
                        return;
                    }

                    if (val != '') {
                        this.getAddressfromCustomer(val);
                    }
                },
            },
            mounted() {
                this.isDraftInitializing = true;
                FormPreserver.initVue(this, 'sales_do_advanced_add', ['revData', 'isLoading', 'isDraftInitializing', 'autonumericFormat2']);
                if (this.CustomerID) {
                    this.addressUrl = '{{ route('misc.shipment') }}?id=' + this.CustomerID + '&numId=true';
                    this.soURL = '{!! route('do.so') !!}?customer=' + this.CustomerID + (this.ReffNumber ? '&inv=' + this.ReffNumber : '');
                }
                this.$nextTick(() => {
                    setTimeout(() => {
                        restoreSelect2Option('#CustomerID', this.CustomerID, this.CustomerText);
                        restoreSelect2Option('#ReffNumber', this.ReffNumber, this.ReffNumberText);
                    }, 100);

                    this.isDraftInitializing = false;
                    if (this.ReffNumber && this.details.length === 0 && this.modalSoDetails.length === 0) {
                        this.getDetail();
                    }
                });
            }
        });

        function setModalSelectValue(selector, value) {
            if (value === null || value === undefined || value === '') {
                $(selector).val(null).trigger('change.select2');
                return;
            }

            if ($(selector + ' option[value="' + value + '"]').length == 0) {
                $(selector).append(new Option(stockDisplayValue(value), value, true, true));
            }

            $(selector).val(value).trigger('change.select2');
        }

        function stockDisplayValue(value) {
            return value === nullFilterValue ? '(Empty)' : (value || '');
        }

        function notEnoughStockMessage(detail) {
            const rows = [
                ['Part', detail.PartID],
                ['Warehouse', app.modalDetail.warehouse],
                ['Batch No', app.modalDetail.BatchNo],
                ['Serial No', app.modalDetail.SerialNo],
                ['Exp Date', app.modalDetail.ExpDate],
                ['BIN', app.modalDetail.BIN],
                ['LOC', app.modalDetail.LOC],
            ];

            return '<div class="text-start">' +
                '<p class="mb-2">Stock is not enough for the selected stock details.</p>' +
                '<ul class="mb-0 ps-3">' +
                rows.map(function(row) {
                    return '<li><strong>' + row[0] + ':</strong> ' + escapeHtml(row[1] || '(Empty)') + '</li>';
                }).join('') +
                '</ul>' +
                '</div>';
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function restoreSelect2Option(selector, value, text) {
            if (!value || !text) {
                return;
            }

            if ($(selector + ' option[value="' + value + '"]').length == 0) {
                $(selector).append(new Option(text, value, true, true));
            }

            $(selector).val(value).trigger('change.select2');
        }

        function initModalPartSelect() {
            if ($('#modal-so-detail').hasClass('select2-hidden-accessible')) {
                $('#modal-so-detail').select2('destroy');
            }

            $('#modal-so-detail').empty().append(new Option('', '', false, false));
            $('#modal-so-detail').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#choose-detail-modal'),
                placeholder: 'Search SO detail',
                allowClear: true,
                data: app.modalSoDetails.map(function(detail) {
                    return {
                        id: app.getDetailKey(detail),
                        text: detail.PartID + ' - ' + detail.PartName + ' | Seq : ' + detail.Sequence + ' | Remaining : ' + app.getRemainingForDetail(detail),
                    };
                }),
                matcher: function(params, data) {
                    if ($.trim(params.term) === '') {
                        return data;
                    }

                    const detail = app.modalSoDetails.find(row => app.getDetailKey(row) === data.id);
                    const term = params.term.toLowerCase();
                    const haystack = [
                        data.text,
                        detail ? detail.PartID : '',
                        detail ? detail.PartName : '',
                        detail ? detail.Sequence : '',
                    ].join(' ').toLowerCase();

                    return haystack.indexOf(term) > -1 ? data : null;
                }
            }).off('change.modal-part').on('change.modal-part', function() {
                app.modalDetail.soIndex = $(this).val() || '';
                app.onModalPartChanged();
            });

            if (app.modalDetail.soIndex) {
                $('#modal-so-detail').val(app.modalDetail.soIndex).trigger('change.select2');
            } else {
                $('#modal-so-detail').val(null).trigger('change.select2');
            }
        }

        function stockRequestData(target, params) {
            const data = {
                part_id: app.selectedSoDetail ? app.selectedSoDetail.PartID : '',
                warehouse_id: app.modalDetail.warehouse || '',
                target: target,
                filter_qty: 1,
                include_children: 1,
                include_child_warehouses: 1,
                page: params.page || 1,
                term: params.term || '',
            };

            Object.entries(selectedModalDetailFilters()).forEach(function(entry) {
                const key = entry[0];
                const value = entry[1];

                if (key !== target) {
                    data[key] = value;
                }
            });

            return data;
        }

        function selectedModalDetailFilters() {
            const filters = {
                batch_no: app.modalDetail.BatchNo,
                serial_no: app.modalDetail.SerialNo,
                exp_date: app.modalDetail.ExpDate,
                bin: app.modalDetail.BIN,
                loc: app.modalDetail.LOC,
            };
            const data = {};

            Object.keys(modalDetailColumnMap).forEach(function(key) {
                if (key === 'serial_no' && (!app.selectedSoDetail || true)) {
                    return;
                }

                if (filters[key]) {
                    data[key] = filters[key];
                }
            });

            return data;
        }

        function exactModalDetailFilters() {
            return {
                batch_no: app.modalDetail.BatchNo || nullFilterValue,
                serial_no: app.modalDetail.SerialNo || nullFilterValue,
                exp_date: app.modalDetail.ExpDate || nullFilterValue,
                bin: app.modalDetail.BIN || nullFilterValue,
                loc: app.modalDetail.LOC || nullFilterValue,
            };
        }

        function stockQtyRequestData(target) {
            const filters = exactModalDetailFilters();
            const selectedValue = filters[target];
            const data = {
                part_id: app.selectedSoDetail ? app.selectedSoDetail.PartID : '',
                warehouse_id: app.modalDetail.warehouse || '',
                target: target,
                filter_qty: 1,
                include_children: 1,
                include_child_warehouses: 1,
                page: 1,
                term: selectedValue,
            };

            Object.entries(filters).forEach(function(entry) {
                const key = entry[0];
                const value = entry[1];

                if (key !== target) {
                    data[key] = value;
                }
            });

            return {
                data: data,
                selectedValue: selectedValue,
            };
        }

        function mapStockResults(response, column) {
            const rows = response && response.data && response.data.results ? response.data.results : [];
            return rows.map(function(row) {
                const value = row[column];
                const optionValue = normalizeStockOptionValue(value);
                return {
                    id: optionValue,
                    text: stockDisplayValue(optionValue),
                    total_qty: row.total_qty,
                };
            });
        }

        function normalizeStockOptionValue(value) {
            return value === null || value === undefined || value === '' ? nullFilterValue : value;
        }

        function modalStockQtyTarget() {
            return 'batch_no';
        }

        function initModalWarehouseSelect() {
            initModalAjaxSelect('#modal-warehouse', '{{ route('misc.warehouse2') }}', 'warehouse', {
                placeholder: 'Pick Warehouse',
                data: function(params) {
                    return { search: params.term || '' };
                },
                processResults: function(response) {
                    return { results: response };
                },
                onChanged: function() {
                    app.onStockAttributeChanged('warehouse');
                }
            });
        }

        function initModalStockSelect(selector, target, field, column) {
            initModalAjaxSelect(selector, availableStockDetailsUrl, field, {
                placeholder: 'Pick an Item',
                data: function(params) {
                    return stockRequestData(target, params);
                },
                processResults: function(response) {
                    return {
                        results: mapStockResults(response, column),
                        pagination: response && response.pagination ? response.pagination : { more: false },
                    };
                },
            });
        }

        function initModalAjaxSelect(selector, url, field, options) {
            if ($(selector).hasClass('select2-hidden-accessible')) {
                $(selector).select2('destroy');
            }

            $(selector).select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#choose-detail-modal'),
                placeholder: options.placeholder || 'Pick an Item',
                allowClear: true,
                ajax: {
                    url: url,
                    dataType: 'json',
                    delay: 250,
                    data: options.data,
                    processResults: options.processResults,
                    cache: true
                }
            }).off('change.modal-attribute').on('change.modal-attribute', function() {
                const value = $(this).val() || null;
                app.modalDetail[field] = value;

                if (typeof options.onChanged === 'function') {
                    options.onChanged();
                } else if (['BatchNo', 'SerialNo', 'ExpDate', 'BIN', 'LOC'].includes(field)) {
                    app.onStockAttributeChanged(field);
                }

                if (field === 'ExpDate') {
                }
            });
        }

        function initModalAttributeSelects() {
            initModalWarehouseSelect();
            initModalStockSelect('#modal-batch-no', 'batch_no', 'BatchNo', 'BatchNo');
            initModalStockSelect('#modal-serial-no', 'serial_no', 'SerialNo', 'SerialNo');
            initModalStockSelect('#modal-exp-date', 'exp_date', 'ExpDate', 'ExpDate');
            initModalStockSelect('#modal-bin', 'bin', 'BIN', 'BIN');
            initModalStockSelect('#modal-loc', 'loc', 'LOC', 'LOC');
        }

        function setModalExpDatePickerValue(value) {
            const input = $('#modal-exp-date-picker');
            if (!input.length) {
                return;
            }

            if (input[0]._flatpickr) {
                input[0]._flatpickr.setDate(value || null, false, 'Y-m-d');
                return;
            }

            input.data('date-value', value || '');
        }

        function initModalExpDatePicker() {
            const input = $('#modal-exp-date-picker');
            if (!input.length) {
                return;
            }

            if (!input[0]._flatpickr) {
                input.flatpickr({
                    dateFormat: 'Y-m-d',
                    allowInput: false,
                    onChange: function(selectedDates, dateStr) {
                        app.modalDetail.ExpDate = dateStr || null;
                        setModalSelectValue('#modal-exp-date', dateStr);
                        app.fetchModalStockQty();
                    }
                });
            }
        }

        function initRevDatepickers() {
            $('.js-modal-flatpickr, .js-flatpickr').flatpickr({
                dateFormat: 'd/m/Y',
            });
            $('.js-flatpickr:visible').on('focus', function() {
                $(this).blur();
            });
            $('.js-flatpickr:visible').prop('readonly', false);
        }

        $('#choose-detail-modal').on('shown.bs.modal', function() {
            initModalAttributeSelects();
            initRevDatepickers();
        });

        $('.form-select2').select2({ theme: 'bootstrap-5' });

        $('#automatic').on('change', function() {
            if ($('#automatic').is(':checked')) {
                $('#TransactionNo').attr('disabled', true);
            } else {
                $('#TransactionNo').removeAttr('disabled');
            }
        });

        $('.js-flatpickr').flatpickr({
            dateFormat: 'd/m/Y',
            defaultDate: 'today'
        });
        $('.js-flatpickr:visible').on('focus', function() {
            $(this).blur();
        });
        $('.js-flatpickr:visible').prop('readonly', false);
    </script>
@endsection
