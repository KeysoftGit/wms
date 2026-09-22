@extends('layouts.admin')

@php
    $details = old('DetailsJson') ? json_decode(old('DetailsJson'), true) : $details;
    $initialCheckers = [];
    if (old('employee')) {
        foreach (old('employee') as $i => $employee) {
            $initialCheckers[] = ['id' => generateRandomString(10), 'employee' => $employee, 'status' => old('status.' . $i)];
        }
    } else {
        $initialCheckers = $checkers ?? [];
    }
@endphp

@section('titles')
    <title>Keysoft NLA WMS - Edit Advanced Stock Adjustment</title>
@endsection

@section('content')
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <form autocomplete="off" method="post" action="{{ route('adjust.update') }}" id="adjust-form">
            @csrf
            <input type="hidden" name="id" value="{{ $adjust->TransactionNo }}">
            <input type="hidden" name="DetailsJson" :value="JSON.stringify(details)">

            <div class="d-flex flex-row align-items-center mb-5">
                <a href="{{ route('adjust') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                <h1 class="h3 fw-bold ms-4 mb-0">Edit Stock Adjustment</h1>
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
                            <div class="row mb-3">
                                <div class="col-lg-4 col-12 mb-lg-0 mb-3">
                                    <label class="form-label">Transaction No</label>
                                    <input type="text" name="TransactionNo" class="form-control" value="{{ $adjust->TransactionNo }}" readonly>
                                </div>
                                <div class="col-lg-4 col-12 mb-lg-0 mb-3">
                                    <label class="form-label">Transaction Date <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="TransactionDate" value="{{ old('TransactionDate') ?? date('d/m/Y', strtotime($adjust->TransactionDate)) }}" readonly required>
                                </div>
                                <div class="col-lg-4 col-12">
                                    <label class="form-label">Expired Date <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="ExpiredDate" value="{{ old('ExpiredDate') ?? date('d/m/Y', strtotime($adjust->ExpiredDate)) }}" readonly required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Division <span class="text-danger">*</span></label>
                                <select2 url="{{ route('misc.division2') }}" v-model="division" :prevalue="division" class="form-select" name="DivisionID" required>
                                    <option value="">-</option>
                                </select2>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Based On <span class="text-danger">*</span></label>
                                <div class="space-y-2">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="Type" id="tOpname" value="opname" v-model="type">
                                        <label class="form-check-label" for="tOpname">Stock Opname</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="Type" id="tManual" value="manual" v-model="type">
                                        <label class="form-check-label" for="tManual">Manual</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 col-12 pe-lg-5">
                            <div v-if="type === 'manual'">
                                <div class="mb-3">
                                    <label class="form-label">Warehouse <span class="text-danger">*</span></label>
                                    <select2 url="{{ route('misc.warehouse2') }}" v-model="warehouse" :prevalue="warehouse" class="form-select" name="WarehouseID" required>
                                        <option value="">-</option>
                                    </select2>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Inventory Type</label>
                                    <select2 url="{{ route('misc.type', ['select2' => true, 'all' => true]) }}" v-model="invType" :prevalue="invType" class="form-select" name="InventoryTypeID">
                                        <option value="">All</option>
                                    </select2>
                                </div>
                            </div>
                            <div v-if="type === 'opname'">
                                <div class="mb-3">
                                    <label class="form-label">Stock Opname <span class="text-danger">*</span></label>
                                    <select2 url="{{ route('adjust.opname', ['edit' => $adjust->StockOpnameNo]) }}" v-model="opname" :prevalue="opname" class="form-select" name="StockOpnameNo" required>
                                        <option value="">-</option>
                                    </select2>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Warehouse <span class="text-danger">*</span></label>
                                    <input type="hidden" name="WarehouseID" v-model="warehouse">
                                    <input type="text" class="form-control" id="WarehouseName" value="{{ $adjust->warehouse->WarehouseName ?? $adjust->WarehouseID }}" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block block-rounded">
                <div class="block-content">
                    <div class="d-flex flex-row justify-content-between align-items-center mb-3">
                        <h5 class="m-0">Stock</h5>
                        <div class="d-flex flex-row align-items-center">
                            <button type="button" class="btn btn-primary ms-1" @click="openDetailModal(null)" :disabled="type !== 'manual' || !warehouse">
                                <i class="fa fa-fw fa-list me-1"></i>Choose Part
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive w-100" style="overflow-x: auto">
                        <table class="table table-bordered nowrap w-100" style="table-layout: fixed; min-width: 1350px">
                            <thead>
                            <tr>
                                <th style="width: 150px" v-if="type === 'manual'">Action</th>
                                <th style="width: 260px">Part</th>
                                <th style="width: 110px">Unit</th>
                                @canany(['admin', 'stock_adj.show_stock'])
                                    <th style="width: 140px">Qty Stock</th>
                                @endcanany
                                <th style="width: 140px">Qty Opname</th>
                                @canany(['admin', 'stock_adj.show_stock'])
                                    <th style="width: 140px">Difference</th>
                                @endcanany
                                <th style="width: 160px">Qty 2</th>
                                <th style="width: 160px">Batch No</th>
                                <th style="width: 160px">Coil No</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-if="details.length === 0">
                                <td :colspan="type === 'manual' ? {{ auth()->user()->hasAnyPermission(['admin', 'stock_adj.show_stock']) ? 11 : 9 }} : {{ auth()->user()->hasAnyPermission(['admin', 'stock_adj.show_stock']) ? 10 : 8 }}" class="text-center p-3">No Part added yet</td>
                            </tr>
                            <tr v-for="(detail, index) in details" :key="detail.id">
                                <td v-if="type === 'manual'">
                                    <div class="d-flex flex-row flex-nowrap gap-1">
                                    <button type="button" class="btn btn-sm btn-secondary me-1" @click="openDetailModal(index)"><i class="fa fa-fw fa-pencil"></i></button>
                                    <button type="button" class="btn btn-sm btn-danger" @click="deleteDetail(index)"><i class="fa fa-fw fa-trash"></i></button>
                                    </div>
                                </td>
                                <td><input type="text" class="form-control" :value="partText(detail)" readonly></td>
                                <td><input type="text" class="form-control" v-model="detail.UnitID" readonly></td>
                                @canany(['admin', 'stock_adj.show_stock'])
                                    <td><vue-autonumeric :options="autonumericFormat" class="form-control" v-model="detail.QtyStock" readonly></vue-autonumeric></td>
                                @endcanany
                                <td><vue-autonumeric :options="autonumericFormat2" class="form-control" v-model="detail.QtyOpname" readonly></vue-autonumeric></td>
                                @canany(['admin', 'stock_adj.show_stock'])
                                    <td><vue-autonumeric :options="autonumericFormat2" class="form-control" :value="Number(detail.QtyOpname || 0) - Number(detail.QtyStock || 0)" readonly></vue-autonumeric></td>
                                @endcanany
                                <td><input type="text" class="form-control" :value="displayQty2(detail)" readonly></td>
                                <td><input type="text" class="form-control" :value="displayEmpty(detail.BatchNo)" readonly></td>
                                <td><input type="text" class="form-control" :value="displayDash(detail.CoilNo)" readonly></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="block block-rounded">
                <div class="block-content">
                    <div class="d-flex flex-row justify-content-between align-items-center mb-3">
                        <h5 class="mb-3">Checkers</h5>
                        <button type="button" class="btn btn-primary" @click="addChecker"><i class="fa fa-fw fa-plus me-1"></i>Add Checker</button>
                    </div>
                    <div class="table-responsive w-100">
                        <table class="table table-bordered nowrap w-100" style="table-layout: fixed">
                            <thead><tr><th style="width: 65px"></th><th>Employee</th><th>Status</th></tr></thead>
                            <tbody>
                            <tr v-if="checkers.length === 0"><td colspan="3" class="text-center p-3">No Checker added Yet</td></tr>
                            <tr v-for="(detail, index) in checkers" :key="detail.id">
                                <td><button type="button" class="btn btn-sm btn-danger" @click="deleteChecker(index)"><i class="fa fa-fw fa-trash"></i></button></td>
                                <td>
                                    <select2 url="{{route('misc.employee', ['select2' => true])}}" v-model="detail.employee" :prevalue="detail.employee" class="form-select" name="employee[]" placeholder="Pick Employee" required>
                                        <option>-</option>
                                    </select2>
                                </td>
                                <td><select class="form-select" name="status[]" v-model="detail.status"><option value="SUPERVISOR">Supervisor</option><option value="STAFF">Staff</option></select></td>
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
                            <textarea name="Notes" class="form-control" rows="3">{{ old('Notes') ?? $adjust->Notes }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Choose Stock Detail</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 mb-4">
                                <label class="form-label">Part :</label>
                                <select id="ModalPart" class="form-select modal-part-select"></select>
                            </div>
                        </div>

                        <div class="row" v-show="modal.PartID">
                            <div class="col-lg-3 col-12 mb-3">
                                <label class="form-label">Part ID</label>
                                <input type="text" class="form-control" v-model="modal.PartID" readonly>
                            </div>
                            <div class="col-lg-5 col-12 mb-3">
                                <label class="form-label">Part Name</label>
                                <input type="text" class="form-control" v-model="modal.PartName" readonly>
                            </div>
                            <div class="col-lg-4 col-12 mb-3">
                                <label class="form-label">Batch No</label>
                                <div class="input-group">
                                    <select id="ModalBatchNo" class="form-select modal-stock-field"></select>
                                    <button class="btn btn-alt-secondary" type="button" @click="clearSelect('#ModalBatchNo')" title="Clear Batch No"><i class="fa fa-times"></i></button>
                                </div>
                            </div>
                            <div class="col-lg-4 col-12 mb-3">
                                <label class="form-label">Coil No</label>
                                <input type="text" class="form-control" :value="displayDash(modal.CoilNo)" readonly>
                            </div>
                            <div class="col-lg-3 col-12 mb-3">
                                <label class="form-label">Input Unit</label>
                                <select class="form-select" v-model="modal.InputUnit">
                                    <option :value="modal.UnitID" v-if="modal.UnitID">@{{ modal.UnitID }}</option>
                                    <option :value="modal.UnitID2" v-if="modal.UnitID2 && modal.UnitID2 !== modal.UnitID">@{{ modal.UnitID2 }}</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-12 mb-3">
                                <label class="form-label">Input Qty <span class="text-danger">*</span></label>
                                <input type="number" min="0" step="0.000001" class="form-control" v-model.number="modal.InputQty">
                            </div>
                            <div class="col-lg-3 col-12 mb-3">
                                <label class="form-label">Qty 1</label>
                                <input type="text" class="form-control" :value="displayQty1(modal)" readonly>
                            </div>
                            <div class="col-lg-3 col-12 mb-3">
                                <label class="form-label">Qty 2</label>
                                <input type="text" class="form-control" :value="displayQty2(modal)" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-alt-secondary" @click="clearModalDetails"><i class="fa fa-fw fa-eraser"></i> Clear Details</button>
                        <div>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" @click="saveModalDetail" :disabled="modalLoading">
                                @{{ editingIndex === null ? 'Add Detail' : 'Save Detail' }}
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
    <link rel="stylesheet" href="{{ asset('js/plugins/sweetalert2/sweetalert2.min.css') }}">
    <style>
        th { white-space: nowrap; }
        #detailModal .modal-part-select,
        #detailModal .select2-container { width: 100% !important; }
        #detailModal .select2-selection--single { min-height: 38px; }
        #detailModal .input-group .select2-container {
            flex: 1 1 auto;
            min-width: 0;
            width: 1% !important;
        }
    </style>
@endsection

@section('scripts')
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.js') }}"></script>
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('js/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.5.4"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.13/dist/vue.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.0/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue-autonumeric@1.2.6/dist/vue-autonumeric.min.js"></script>
    <script type="text/x-template" id="select2-template"><select><slot></slot></select></script>
    <script src="{{ asset('js/vueComponent-select2.js') }}"></script>
    <script>
        const initialDetails = @json($details ?: []);
        const initialCheckers = @json($initialCheckers ?: []);
        let app = new Vue({
            el: '#vue-container',
            data: {
                isInitializing: true,
                type: '{{ old('Type') ?? ($adjust->StockOpnameNo ? 'opname' : 'manual') }}',
                opname: '{{ old('StockOpnameNo') ?? $adjust->StockOpnameNo }}',
                warehouse: '{{ old('WarehouseID') ?? $adjust->WarehouseID }}',
                division: '{{ old('DivisionID') ?? $adjust->DivisionID }}',
                invType: '{{ old('InventoryTypeID') ?? $adjust->InventoryTypeID }}',
                details: initialDetails,
                checkers: initialCheckers,
                editingIndex: null,
                modalLoading: false,
                modal: thisEmptyModal(),
                autonumericFormat: { minimumValue: '-9999999999999', maximumValue: '9999999999999', decimalPlaces: 0, digitGroupSeparator: '.', decimalCharacter: ',', modifyValueOnWheel: false, allowDecimalPadding: false, unformatOnSubmit: true },
                autonumericFormat2: { decimalPlaces: 6, digitGroupSeparator: '.', decimalCharacter: ',', modifyValueOnWheel: false, allowDecimalPadding: false, unformatOnSubmit: true },
            },
            methods: {
                getOpname() {
                    let vm = this;
                    $.get('{{ route('adjust.opname.detail') }}', { id: vm.opname }).then(function (result) {
                        $('#WarehouseName').val(result.warehouse_name);
                        vm.warehouse = result.warehouse;
                        vm.details = result.details;
                        vm.checkers = result.checkers;
                    });
                },
                openDetailModal(index) {
                    this.editingIndex = index;
                    this.modal = index === null ? thisEmptyModal() : JSON.parse(JSON.stringify(this.details[index]));
                    syncAdjustmentInput(this.modal);
                    $('#detailModal').modal('show');
                    this.$nextTick(() => {
                        initModalSelects(this);
                        this.syncModalSelects();
                    });
                },
                syncModalSelects() {
                    setSelectValue('#ModalPart', this.modal.PartID, this.partText(this.modal));
                    setSelectValue('#ModalBatchNo', this.modal.BatchNo, this.modal.BatchNo);
                    setSelectValue('#ModalSerialNo', this.modal.SerialNo, this.modal.SerialNo);
                    setSelectValue('#ModalExpDate', this.modal.ExpDate, this.modal.ExpDate);
                    setSelectValue('#ModalBIN', this.modal.BIN, this.modal.BIN);
                    setSelectValue('#ModalLOC', this.modal.LOC, this.modal.LOC);
                    setSerialSelectState(this);
                },
                saveModalDetail() {
                    if (!this.modal.PartID) return Swal.fire('Required', 'Part is required.', 'warning');
                    if (false && ![0, 1].includes(Number(this.modal.QtyOpname))) return Swal.fire('Invalid Qty', 'Qty Opname must be 0 or 1 for serial-controlled part.', 'warning');
                    let vm = this;
                    vm.modalLoading = true;
                    $.get('{{ route('adjust.stock.detail') }}', {
                        part_id: vm.modal.PartID, warehouse_id: vm.warehouse, BatchNo: vm.modal.BatchNo, SerialNo: vm.modal.SerialNo, ExpDate: vm.modal.ExpDate, BIN: vm.modal.BIN, LOC: vm.modal.LOC,
                    }).then(function (result) {
                        let row = Object.assign(result.detail, { InputUnit: vm.modal.InputUnit, InputQty: vm.modal.InputQty });
                        syncAdjustmentInput(row);
                        row.QtyOpname = calculateAdjustmentPrimaryQty(row);
                        let duplicate = vm.details.some((detail, idx) => idx !== vm.editingIndex && vm.detailKey(detail) === vm.detailKey(row));
                        if (duplicate) return Swal.fire('Duplicate Detail', 'The selected stock detail already exists.', 'warning');
                        if (vm.editingIndex === null) vm.details.push(row); else vm.$set(vm.details, vm.editingIndex, row);
                        $('#detailModal').modal('hide');
                    }).fail(xhr => Swal.fire('Failed', xhr.responseJSON?.message || 'Unable to fetch stock detail.', 'error')).always(() => vm.modalLoading = false);
                },
                detailKey(detail) { return [detail.PartID, detail.UnitID, detail.BatchNo || '', detail.SerialNo || '', detail.ExpDate || '', detail.BIN || '', detail.LOC || ''].join('|'); },
                deleteDetail(index) { this.details.splice(index, 1); },
                addChecker() { this.checkers.push({ id: makeid(10), employee: '', status: 'SUPERVISOR' }); },
                deleteChecker(index) { this.checkers.splice(index, 1); },
                partText(detail) { return detail && detail.PartID ? detail.PartID + (detail.PartName ? ' - ' + detail.PartName : '') : ''; },
                displayEmpty(value) { return value === null || value === undefined || value === '' ? '(Empty)' : value; },
                displayDash(value) { return value === null || value === undefined || value === '' ? '-' : value; },
                displayQty2(detail) { return formatQty2(calculateAdjustmentQty2(detail), detail.UnitID2); },
                displayQty1(detail) {
                    const qty = calculateAdjustmentPrimaryQty(detail);
                    if (qty === null || Number.isNaN(qty)) return '-';
                    const formatted = Number(qty).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 6 });
                    return formatted + (detail.UnitID ? ' ' + detail.UnitID : '');
                },
                clearSelect(selector) { $(selector).val(null).trigger('change'); },
                clearModalDetails() {
                    let part = { PartID: this.modal.PartID, PartName: this.modal.PartName, WithSerialNo: this.modal.WithSerialNo, UnitID: this.modal.UnitID, QtyOpname: this.modal.QtyOpname, InputUnit: this.modal.InputUnit, InputQty: this.modal.InputQty };
                    this.modal = Object.assign(thisEmptyModal(), part);
                    this.syncModalSelects();
                }
            },
            watch: {
                type() {
                    if (this.isInitializing) return;
                    this.opname = '';
                    this.warehouse = '';
                    this.invType = '';
                    $('#WarehouseName').val('');
                    this.details = [];
                    this.checkers = [];
                },
                opname() { if (!this.isInitializing && this.type === 'opname' && this.opname) this.getOpname(); },
                warehouse() { if (!this.isInitializing && this.type === 'manual') this.details = []; }
            },
            mounted() {
                this.$nextTick(() => this.isInitializing = false);
            }
        });
        function thisEmptyModal() { return { id: makeid(10), PartID: '', PartName: '', WithSerialNo: 0, UnitID: '', QtyStock: 0, QtyOpname: null, InputUnit: null, InputQty: null, QtyStock2: null, UnitID2: null, BatchNo: null, CoilNo: null, SerialNo: null, ExpDate: null, BIN: null, LOC: null }; }
        function setSelectValue(selector, value, text) {
            let el = $(selector);
            if (!value) {
                el.val(null).trigger('change.select2');
                return;
            }
            if (el.find("option[value='" + value + "']").length === 0) {
                el.append(new Option(text || value, value, true, true));
            }
            el.val(value).trigger('change.select2');
        }
        function resetModalSelect2(selector) {
            let el = $(selector);
            if (el.hasClass('select2-hidden-accessible')) {
                el.select2('destroy');
            }
            el.off('.adjustModal');
            return el;
        }
        function setSerialSelectState(vm) {
            $('#ModalSerialNo').prop('disabled', true).trigger('change.select2');
        }
        function initModalSelects(vm) {
            resetModalSelect2('#ModalPart').select2({ theme: 'bootstrap-5', dropdownParent: $('#detailModal'), placeholder: 'Part', ajax: { url: '{{ route('misc.part') }}', data: params => ({ search: params.term }), processResults: data => ({ results: data }) } })
                .on('select2:select.adjustModal', function (e) { let data = e.params.data; vm.modal.PartID = data.id; vm.modal.PartName = (data.text || '').replace(data.id + ' - ', ''); vm.modal.WithSerialNo = Number(data.WithSerialNo || 0); if (vm.modal.WithSerialNo !== 1) vm.modal.SerialNo = null; setSerialSelectState(vm); $.get('{{ route('adjust.stock.detail') }}', { part_id: data.id, warehouse_id: vm.warehouse, preview_part: true }).done(result => { vm.modal.UnitID = result.detail.UnitID; vm.modal.QtyStock = result.detail.QtyStock; vm.modal.QtyStock2 = result.detail.QtyStock2; vm.modal.UnitID2 = result.detail.UnitID2; vm.modal.QtyOpname = result.detail.QtyStock; vm.modal.InputQty = result.detail.QtyStock; syncAdjustmentInput(vm.modal); }); $('#ModalBatchNo,#ModalSerialNo,#ModalExpDate,#ModalBIN,#ModalLOC').val(null).trigger('change.select2'); vm.modal.BatchNo = null; vm.modal.CoilNo = null; vm.modal.SerialNo = null; vm.modal.ExpDate = null; vm.modal.BIN = null; vm.modal.LOC = null; });
            initCascadingStockSelect(vm, '#ModalBatchNo', 'batch_no', 'BatchNo');
            initCascadingStockSelect(vm, '#ModalSerialNo', 'serial_no', 'SerialNo');
            setSerialSelectState(vm);
            initCascadingStockSelect(vm, '#ModalExpDate', 'exp_date', 'ExpDate');
            initCascadingStockSelect(vm, '#ModalBIN', 'bin', 'BIN');
            initCascadingStockSelect(vm, '#ModalLOC', 'loc', 'LOC');
        }

        const availableStockDetailsUrl = @json(route('helper.available_stock_details'));
        const stockDetailColumnMap = { batch_no: 'BatchNo', serial_no: 'SerialNo', exp_date: 'ExpDate', bin: 'BIN', loc: 'LOC' };

        function selectedStockFilters(vm, excludeTarget) {
            const values = { batch_no: vm.modal.BatchNo, serial_no: false ? vm.modal.SerialNo : null, exp_date: vm.modal.ExpDate, bin: vm.modal.BIN, loc: vm.modal.LOC };
            const data = {};
            Object.keys(stockDetailColumnMap).forEach(function (key) {
                if (key !== excludeTarget && values[key]) data[key] = values[key];
            });
            return data;
        }

        function initCascadingStockSelect(vm, selector, target, field) {
            resetModalSelect2(selector).select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#detailModal'),
                placeholder: field,
                allowClear: true,
                ajax: {
                    url: availableStockDetailsUrl,
                    dataType: 'json',
                    delay: 250,
                    data: params => Object.assign({ part_id: vm.modal.PartID, warehouse_id: vm.warehouse, target: target, term: params.term || '' }, selectedStockFilters(vm, target)),
                    processResults: response => {
                        const column = stockDetailColumnMap[target];
                        const rows = (response && response.data && response.data.results) || [];
	                        return { results: rows.map(function (row) { return { id: row[column], text: row[column], coil_no: row.coil_no || row.CoilNo || null, total_qty: row.total_qty, total_qty2: row.total_qty2, unit_id2: row.unit_id2 || row.UnitID2 || null }; }) };
                    },
                    cache: true
                }
	            }).on('change.adjustModal', function () { vm.modal[field] = $(this).val() || null; if (field === 'BatchNo') { const selected = $(this).select2('data')[0] || null; vm.modal.CoilNo = selected && selected.coil_no ? selected.coil_no : null; vm.modal.QtyStock = selected && selected.total_qty !== undefined ? Number(selected.total_qty || 0) : vm.modal.QtyStock; vm.modal.QtyStock2 = selected && selected.total_qty2 !== undefined ? Number(selected.total_qty2 || 0) : vm.modal.QtyStock2; vm.modal.UnitID2 = selected && selected.unit_id2 ? selected.unit_id2 : vm.modal.UnitID2; syncAdjustmentInput(vm.modal); } });
        }
        function makeid(length) { let result = '', chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'; for (let i = 0; i < length; i++) result += chars.charAt(Math.floor(Math.random() * chars.length)); return result; }
        function syncAdjustmentInput(detail) {
            if (!detail) return;
            if (!detail.InputUnit || (detail.InputUnit !== detail.UnitID && detail.InputUnit !== detail.UnitID2)) {
                detail.InputUnit = detail.UnitID || detail.UnitID2 || null;
            }
            if (detail.InputQty === null || detail.InputQty === undefined || detail.InputQty === '') {
                detail.InputQty = detail.QtyOpname ?? detail.QtyStock ?? 0;
            }
        }
        function calculateAdjustmentPrimaryQty(detail) {
            if (!detail) return null;
            const inputQty = Number(detail.InputQty !== undefined && detail.InputQty !== null ? detail.InputQty : detail.QtyOpname || 0);
            if (detail.InputUnit && detail.UnitID2 && detail.InputUnit === detail.UnitID2) {
                const stockQty = Math.abs(Number(detail.QtyStock || 0));
                const stockQty2 = Math.abs(Number(detail.QtyStock2 || 0));
                if (!stockQty || !stockQty2) return null;
                return inputQty / stockQty2 * stockQty;
            }
            return inputQty;
        }
        function calculateAdjustmentQty2(detail) {
            const qtyStock = Math.abs(Number(detail && detail.QtyStock ? detail.QtyStock : 0));
            const qtyStock2 = Math.abs(Number(detail && detail.QtyStock2 ? detail.QtyStock2 : 0));
            const qtyOpname = Number(calculateAdjustmentPrimaryQty(detail) || 0);
            const difference = Math.abs(qtyOpname - Number(detail && detail.QtyStock ? detail.QtyStock : 0));
            if (!qtyStock || !qtyStock2 || !difference) return null;
            return difference / qtyStock * qtyStock2;
        }
        function formatQty2(qty2, unitId2) {
            if (qty2 === null || qty2 === undefined || Number.isNaN(Number(qty2))) return '-';
            const formatted = Number(qty2).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
            return formatted + (unitId2 ? ' ' + unitId2 : '');
        }
    </script>
@endsection
