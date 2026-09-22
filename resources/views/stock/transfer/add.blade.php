@extends('layouts.admin')

@section('titles')
    <title>Keyonline - Add Item Transfer</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <form autocomplete="off" method="post" enctype="multipart/form-data" action="{{ route('transfer.store') }}" id="transfer-form">
            @csrf

            <div class="d-flex flex-row align-items-center mb-5">
                <a href="{{ route('transfer') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                <h1 class="h3 fw-bold ms-4 mb-0">
                    Add Item Transfer
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
                        <div class="col-lg-6 col-12">
                            <div class="row align-items-center mb-3">
                                <div class="col-lg-3 col-12 mb-lg-0 mb-3">
                                    <label class="form-label">Transaction No <span class="text-danger">*</span></label>
                                    <input type="text" name="TransactionNo" id="TransactionNo" class="form-control" value="{{ old('TransactionNo') ?? '' }}" required {{ old('automatic') ? 'disabled' : (count($errors->all()) > 0 ? '' : 'disabled') }}>
                                </div>
                                <div class="col-lg-auto col-12 mb-lg-0 mb-3">
                                    <label class="form-label"></label>
                                    <div class="form-check pt-lg-2">
                                        <input class="form-check-input fs-6" type="checkbox" value="1" name="automatic" id="automatic" {{ old('automatic') ? 'checked' : (count($errors->all()) > 0 ? '' : 'checked') }}>
                                        <label class="form-check-label fs-6" for="automatic">
                                            Automatic
                                        </label>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-12">
                                    <label class="form-label">Transaction Date <span class="text-danger">*</span></label>
                                    <input type="text" class="js-flatpickr form-control js-flatpickr-enabled flatpickr-input active" id="TransactionDate" name="TransactionDate" placeholder="d/m/Y" value="{{ old('TransactionDate') ?? '' }}" readonly="readonly" required>
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
                                        <select2 url="{{ route('misc.warehouse2') }}" v-model="fromId" :prevalue="fromId" class="form-select"
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

                    <div class="table-responsive w-100">
                        <table class="table table-bordered table-vcenter w-100">
                            <thead>
                            <tr>
                                <th style="width: 95px" class="text-center">Action</th>
                                <th style="width: 180px;">Part ID</th>
                                <th>Part Name</th>
                                <th style="width: 140px;">Qty</th>
                                <th style="width: 140px;">Unit</th>
                                <th style="width: 120px;">Conversion</th>
                                <th style="width: 120px;">Stock</th>
                                <th style="width: 160px;">Batch No</th>
                                <th style="width: 220px;">Notes</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td colspan="9" class="text-center p-3" v-if="details.length == 0">No Item Added Yet</td>
                            </tr>
                            <tr v-for="(detail, index) in details" :key="detail.id">
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-secondary btn-sm" @click="openDetailModal(index)">
                                            <i class="fa fa-fw fa-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" @click="deleteDetail(detail)">
                                            <i class="fa fa-fw fa-trash-can"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" name="part[]" v-model="detail.part">
                                    <input type="hidden" name="unit[]" v-model="detail.unit">
                                    <input type="hidden" name="conversion[]" v-model="detail.conversion">
                                    <input type="hidden" name="qty[]" v-model="detail.qty">
                                    <input type="hidden" name="qty_actual[]" :value="qtyActual(detail)">
                                    <input type="hidden" name="qty2_actual[]" :value="qty2Actual(detail)">
                                    <input type="hidden" name="unit1[]" :value="detail.unit1 || detail.unit">
                                    <input type="hidden" name="unit2[]" :value="detail.unit2 || detail.unit">
                                    <input type="hidden" name="stock[]" :value="stockInSelectedUnit(detail)">
                                    <input type="hidden" name="cartoon[]" v-model="detail.cartoon">
                                    <input type="hidden" name="dimension[]" v-model="detail.dimension">
                                    <input type="hidden" name="notes[]" v-model="detail.notes">
                                </td>
                                <td>@{{ detail.part || '-' }}</td>
                                <td>@{{ detail.part_name || '-' }}</td>
                                <td class="text-end">@{{ trimDecimal(detail.qty) }}</td>
                                <td>@{{ detail.unit || '-' }}</td>
                                <td class="text-end">@{{ trimDecimal(detail.conversion) }}</td>
                                <td class="text-end">@{{ trimDecimal(stockInSelectedUnit(detail)) }}</td>
                                <td>@{{ detail.cartoon || '-' }}</td>
                                <td>@{{ detail.notes || '-' }}</td>
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
                    </div>
                </div>
            </div>

            <div class="modal fade" id="transfer-detail-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Item Transfer Detail</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-lg-8 col-12 mb-3" :class="{ 'transfer-detail-invalid': detailModalErrors.part }">
                                    <label class="form-label">Part <span class="text-danger">*</span></label>
                                    <select2 url="{{ route('misc.part') }}" v-model="detailModal.part" :prevalue="detailModal.part" class="form-select transfer-modal-part" placeholder="Pick Part">
                                        <option>-</option>
                                    </select2>
                                    <div class="invalid-feedback d-block" v-if="detailModalErrors.part">@{{ detailModalErrors.part }}</div>
                                </div>
                                <div class="col-lg-4 col-12 mb-3" :class="{ 'transfer-detail-invalid': detailModalErrors.unit }">
                                    <label class="form-label">Unit <span class="text-danger">*</span></label>
                                    <select2 :url="detailModal.unitUrl" v-model="detailModal.unit" :prevalue="detailModal.unit" class="form-select transfer-modal-unit" :disabled="detailModal.part == null" placeholder="Pick Unit">
                                        <option>-</option>
                                    </select2>
                                    <div class="invalid-feedback d-block" v-if="detailModalErrors.unit">@{{ detailModalErrors.unit }}</div>
                                </div>
                                <div class="col-lg-3 col-12 mb-3">
                                    <label class="form-label">Warehouse</label>
                                    <input type="text" class="form-control" :value="fromId || '-'" readonly>
                                </div>
                                <div class="col-lg-3 col-12 mb-3">
                                    <label class="form-label">Batch No</label>
                                    <select id="transfer-modal-batch" class="form-select" v-model="detailModal.cartoon" :disabled="!detailModal.part || !fromId || !detailModal.stock_options_loaded" @change="selectStockOption(detailModal)">
                                        <option value=""></option>
                                        <option v-for="option in batchSelectOptions(detailModal)" :value="option.id">@{{ option.text }}</option>
                                    </select>
                                </div>
                                <div class="col-lg-3 col-12 mb-3">
                                    <label class="form-label">Stock</label>
                                    <input type="text" class="form-control" :value="trimDecimal(stockInSelectedUnit(detailModal))" readonly>
                                </div>
                                <div class="col-lg-3 col-12 mb-3">
                                    <label class="form-label">Conversion</label>
                                    <input type="number" class="form-control" v-model="detailModal.conversion" readonly>
                                </div>
                                <div class="col-lg-3 col-12 mb-3" :class="{ 'transfer-detail-invalid': detailModalErrors.qty }">
                                    <label class="form-label">Qty <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" v-model="detailModal.qty" step="0.000001" @blur="trimQty(detailModal)" @change="trimQty(detailModal)">
                                    <div class="invalid-feedback d-block" v-if="detailModalErrors.qty">@{{ detailModalErrors.qty }}</div>
                                </div>
                                <div class="col-lg-3 col-12 mb-3">
                                    <label class="form-label">Qty Actual</label>
                                    <input type="text" class="form-control" :value="trimDecimal(qtyActual(detailModal))" readonly>
                                </div>
                                <div class="col-lg-12 col-12 mb-3">
                                    <label class="form-label">Notes</label>
                                    <input type="text" class="form-control" v-model="detailModal.notes">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top pt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-success" @click="saveDetailModal">
                                <i class="fa fa-fw fa-save me-1"></i>@{{ detailModal.index === null ? 'Add Detail' : 'Save Detail' }}
                            </button>
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

        .transfer-detail-invalid .form-control,
        .transfer-detail-invalid .select2-container--bootstrap-5 .select2-selection {
            border-color: #dc3545;
        }
    </style>
@endsection

@section('scripts')
    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>

    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.js') }}"></script>
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
                isLoading: false,

                fromId: '{{ old('WarehouseIDFrom') ?? '' }}',
                staffFrom: '{{ old('StaffInChargeIDFrom') ?? '' }}',
                toId: '{{ old('WarehouseIDTo') ?? '' }}',
                staffTo: '{{ old('StaffInChargeIDTo') ?? '' }}',

                details: [],
                detailModal: {},
                detailModalErrors: {},
                isPopulatingDetailModal: false,

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
                blankDetail(index = null) {
                    return this.prepareDetail({
                        index: index,
                        id: makeid(10),
                        part: null,
                        part_name: '',
                        qty: 1,
                        unitUrl: '{{ route('misc.partunit2') }}?id=0',
                        unit: null,
                        conversion: 1,
                        stock: 0,
                        cartoon: '',
                        dimension: '',
                        notes: ''
                    });
                },
                addDetail(){
                    this.isPopulatingDetailModal = true;
                    this.detailModal = this.blankDetail(null);
                    this.detailModalErrors = {};
                    $('#transfer-detail-modal').modal('show');
                    this.$nextTick(() => {
                        this.initializeBatchSelect(this.detailModal);
                        this.isPopulatingDetailModal = false;
                    });
                },
                openDetailModal(index) {
                    this.isPopulatingDetailModal = true;
                    this.detailModal = this.prepareDetail(JSON.parse(JSON.stringify(this.details[index])));
                    this.detailModal.index = index;
                    this.detailModalErrors = {};
                    $('#transfer-detail-modal').modal('show');
                    this.$nextTick(() => {
                        let request = this.refreshStockOptions(this.detailModal, true);
                        if (request && request.always) {
                            request.always(() => {
                                this.isPopulatingDetailModal = false;
                            });
                        } else {
                            this.$nextTick(() => {
                                this.isPopulatingDetailModal = false;
                            });
                        }
                    });
                },
                closeDetailModal() {
                    const modalEl = document.getElementById('transfer-detail-modal');

                    if (window.bootstrap && bootstrap.Modal) {
                        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                        modal.hide();
                    } else {
                        $('#transfer-detail-modal').modal('hide');
                    }
                },
                saveDetailModal() {
                    if (!this.validateDetailModal()) return;

                    let row = this.prepareDetail(JSON.parse(JSON.stringify(this.detailModal)));
                    let index = row.index;
                    let isNewDetail = index === null || index === undefined;
                    delete row.index;

                    if (isNewDetail) {
                        this.details.push(row);
                        this.detailModal = this.blankDetail(null);
                        this.detailModalErrors = {};
                        this.$nextTick(() => {
                            $('#transfer-detail-modal .transfer-modal-part').val('').trigger({ type: 'change', params: { quiet: true } });
                            $('#transfer-detail-modal .transfer-modal-unit').val('').trigger({ type: 'change', params: { quiet: true } });
                            this.initializeBatchSelect(this.detailModal);
                        });
                    } else {
                        this.$set(this.details, index, row);
                        this.closeDetailModal();
                    }
                },
                validateDetailModal() {
                    this.syncModalSelectValues();
                    this.trimQty(this.detailModal);
                    this.selectStockOption(this.detailModal);

                    let errors = {};
                    if (!this.detailModal.part) errors.part = 'Part is required.';
                    if (!this.detailModal.unit) errors.unit = 'Unit is required.';
                    if (Number(this.detailModal.qty || 0) <= 0) errors.qty = 'Qty must be greater than 0.';
                    if (this.toNumber(this.qtyActual(this.detailModal)) > this.toNumber(this.detailModal.stock) + 0.000001) {
                        errors.qty = 'Qty actual must not exceed stock (' + this.trimDecimal(this.detailModal.stock) + ').';
                    }

                    this.detailModalErrors = errors;
                    return Object.keys(errors).length === 0;
                },
                prepareDetail(detail) {
                    return Object.assign({
                        id: makeid(10),
                        part: null,
                        part_name: '',
                        qty: 1,
                        unitUrl: '{{ route('misc.partunit2') }}?id=0',
                        unit: null,
                        conversion: 1,
                        qty_actual: 1,
                        qty2_actual: 1,
                        unit1: null,
                        unit2: null,
                        stock: 0,
                        stock_options: [],
                        stock_options_loaded: false,
                        stock_request_id: 0,
                        stock_key: '',
                        cartoon: '',
                        dimension: '',
                        notes: ''
                    }, detail || {});
                },
                shouldIgnoreModalSelectEvent(event) {
                    return this.isPopulatingDetailModal || (event && event.params && event.params.quiet);
                },
                getModalSelectValue(selector) {
                    let value = $(selector).val();
                    return value && value != '-1' ? value : null;
                },
                syncModalSelectValues() {
                    this.detailModal.part = this.getModalSelectValue('#transfer-detail-modal .transfer-modal-part') || this.detailModal.part;
                    this.detailModal.unit = this.getModalSelectValue('#transfer-detail-modal .transfer-modal-unit') || this.detailModal.unit;
                    this.detailModal.cartoon = $('#transfer-modal-batch').val() || '';
                },
                setModalPartName() {
                    let selectedText = $('#transfer-detail-modal .transfer-modal-part option:selected').text();
                    if (!selectedText || selectedText == '-' || selectedText == this.detailModal.part) return;

                    let prefix = this.detailModal.part + ' - ';
                    this.detailModal.part_name = selectedText.indexOf(prefix) === 0 ? selectedText.substring(prefix.length) : selectedText;
                },
                trimDecimal(value) {
                    if (value === null || value === undefined || value === '') return '';
                    let stringValue = String(value);
                    if (stringValue.indexOf('.') === -1) return stringValue;
                    return stringValue.replace(/(\.\d*?[1-9])0+$/, '$1').replace(/\.0+$/, '').replace(/\.$/, '');
                },
                trimQty(detail) {
                    detail.qty = this.trimDecimal(detail.qty);
                },
                toNumber(value) {
                    return Number(String(value || 0).replace(/,/g, '')) || 0;
                },
                stockInSelectedUnit(detail) {
                    let conversion = this.toNumber(detail.conversion || 1);
                    if (conversion <= 0) return 0;
                    return this.toNumber(detail.stock) / conversion;
                },
                qtyActual(detail) {
                    return this.trimDecimal(this.toNumber(detail.qty) * this.toNumber(detail.conversion || 1));
                },
                qty2Actual(detail) {
                    let matchingOptions = this.matchingStockOptions(detail);
                    if (matchingOptions.length === 0 && detail.qty2_actual !== null && detail.qty2_actual !== undefined && detail.qty2_actual !== '') {
                        return this.trimDecimal(detail.qty2_actual);
                    }
                    let baseUnitQty = matchingOptions.reduce(
                        (total, option) => total + this.toNumber(option.BaseUnitQty),
                        0
                    );
                    let baseRatioQty2 = matchingOptions.reduce(
                        (total, option) => total + this.toNumber(option.BaseRatioQty2),
                        0
                    );

                    if (baseUnitQty > 0.000001 && baseRatioQty2 > 0.000001) {
                        return this.trimDecimal(this.toNumber(detail.qty) / (baseUnitQty / baseRatioQty2));
                    }

                    return this.trimDecimal(detail.qty);
                },
                refreshStockOptions(detail, preserveSelection = false) {
                    detail = this.prepareDetail(detail);
                    this.detailModal = detail;
                    if (!detail.part || !this.fromId) {
                        this.initializeBatchSelect(detail);
                        return;
                    }

                    let key = detail.part + '|' + this.fromId;
                    if (detail.stock_key === key && (detail.stock_options_loaded || detail.stock_request_id)) {
                        this.selectStockOption(detail);
                        return;
                    }

                    detail.stock_key = key;
                    detail.stock_request_id = (detail.stock_request_id || 0) + 1;
                    const requestId = detail.stock_request_id;
                    let batch = preserveSelection ? detail.cartoon : '';
                    const element = $('#transfer-modal-batch');
                    if (element.hasClass('select2-hidden-accessible')) element.select2('destroy');

                    detail.cartoon = batch;
                    detail.stock_options = [];
                    detail.stock_options_loaded = false;
                    detail.stock = 0;

                    let app = this;
                    return $.get('{{ route('transfer.stock_options') }}', {
                        part: detail.part,
                        warehouse: this.fromId,
                        unit: detail.unit,
                        transaction_date: $('#TransactionDate').val()
                    }).then(function(result) {
                        if (requestId !== detail.stock_request_id) return;
                        detail.stock_options = result.options || [];
                        detail.cartoon = batch;
                        app.$nextTick(function() {
                            detail.stock_options_loaded = true;
                            app.$nextTick(function() {
                                app.initializeBatchSelect(detail);
                                app.selectStockOption(detail);
                            });
                        });
                    }).fail(function() {
                        detail.stock_options = [];
                        detail.stock_options_loaded = true;
                        detail.stock = 0;
                        app.$nextTick(function() {
                            app.initializeBatchSelect(detail);
                        });
                    });
                },
                initializeBatchSelect(detail) {
                    const element = $('#transfer-modal-batch');
                    if (!element.length) return;
                    if (element.hasClass('select2-hidden-accessible')) element.select2('destroy');
                    element.prop('disabled', !detail.stock_options_loaded);
                    element.select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        allowClear: true,
                        dropdownParent: $('#transfer-detail-modal')
                    })
                        .val(detail.cartoon || '')
                        .trigger('change.select2')
                        .off('.transfer-stock')
                        .on('select2:select.transfer-stock select2:clear.transfer-stock', () => {
                            detail.cartoon = element.val() || '';
                            this.selectStockOption(detail);
                        });
                },
                selectStockOption(detail) {
                    let matchingOptions = this.matchingStockOptions(detail);
                    if (matchingOptions.length === 0) {
                        detail.stock = 0;
                        return;
                    }
                    detail.conversion = this.resolveStockConversion(detail, matchingOptions);
                    detail.unit1 = this.resolveUnit1(detail, matchingOptions);
                    detail.unit2 = this.resolveUnit2(detail, matchingOptions);
                    this.updateActualQuantities(detail, matchingOptions);
                    detail.stock = matchingOptions.reduce(
                        (total, option) => total + this.toNumber(option.Qty),
                        0
                    );
                },
                matchingStockOptions(detail) {
                    return (detail.stock_options || []).filter(option =>
                        (!detail.cartoon || (option.BatchNo || '') === detail.cartoon)
                    );
                },
                resolveStockConversion(detail, matchingOptions) {
                    let baseUnitQty = matchingOptions.reduce(
                        (total, option) => total + this.toNumber(option.BaseUnitQty),
                        0
                    );
                    if (baseUnitQty > 0.000001) {
                        return 1;
                    }

                    let ratioQty = matchingOptions.reduce(
                        (total, option) => total + this.toNumber(option.RatioQty),
                        0
                    );
                    let ratioQty2 = matchingOptions.reduce(
                        (total, option) => total + this.toNumber(option.RatioQty2),
                        0
                    );

                    if (ratioQty2 > 0.000001) {
                        return this.trimDecimal(ratioQty / ratioQty2);
                    }

                    let optionWithConversion = matchingOptions.find(option => Number(option.Conversion || 0) > 0);
                    if (optionWithConversion) {
                        return this.trimDecimal(optionWithConversion.Conversion);
                    }

                    return this.trimDecimal(detail.conversion || 1);
                },
                resolveUnit1(detail, matchingOptions) {
                    let baseUnitQty = matchingOptions.reduce(
                        (total, option) => total + this.toNumber(option.BaseUnitQty),
                        0
                    );
                    if (baseUnitQty > 0.000001) {
                        return detail.unit;
                    }

                    let ratioOption = matchingOptions.find(option => option.RatioUnitID);
                    return ratioOption ? ratioOption.RatioUnitID : detail.unit;
                },
                resolveUnit2(detail, matchingOptions) {
                    let baseUnitQty = matchingOptions.reduce(
                        (total, option) => total + this.toNumber(option.BaseUnitQty),
                        0
                    );
                    if (baseUnitQty > 0.000001) {
                        let baseOption = matchingOptions.find(option => option.BaseUnitID2);
                        return baseOption ? baseOption.BaseUnitID2 : detail.unit;
                    }

                    return detail.unit;
                },
                updateActualQuantities(detail, matchingOptions = null) {
                    matchingOptions = matchingOptions || this.matchingStockOptions(detail);
                    let qty = this.toNumber(detail.qty);
                    let conversion = this.toNumber(detail.conversion || 1);
                    detail.qty_actual = this.trimDecimal(qty * conversion);

                    let baseUnitQty = matchingOptions.reduce(
                        (total, option) => total + this.toNumber(option.BaseUnitQty),
                        0
                    );
                    let baseRatioQty2 = matchingOptions.reduce(
                        (total, option) => total + this.toNumber(option.BaseRatioQty2),
                        0
                    );

                    if (baseUnitQty > 0.000001 && baseRatioQty2 > 0.000001) {
                        detail.qty2_actual = this.trimDecimal(qty / (baseUnitQty / baseRatioQty2));
                    } else {
                        detail.qty2_actual = this.trimDecimal(qty);
                    }
                },
                uniqueStockOptions(options, field, textFormatter = value => value) {
                    let seen = {};
                    return options.filter(option => option[field])
                        .map(option => ({id: option[field], text: textFormatter(option[field])}))
                        .filter(option => seen[option.id] ? false : (seen[option.id] = true));
                },
                batchSelectOptions(detail) {
                    return this.uniqueStockOptions((detail && detail.stock_options) || [], 'BatchNo');
                },

                changePart(index){
                    this.details[index].price = 0;
                    this.details[index].unitUrl = '{{ route('misc.partunit2') }}?id=' +  this.details[index].part;
                    this.details[index].unit = null;

                    $(`#unit${index}`).val('').trigger('change');

                    this.getStock(index);
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
                getConversionModal(){
                    let app = this;
                    if (!app.detailModal.part || !app.detailModal.unit) return;
                    $.ajax({
                        url: '{{ route('misc.conversion') }}?part_id=' + app.detailModal.part + '&unit_id=' + app.detailModal.unit,
                        type: 'GET'
                    }).then(function (result){
                        app.detailModal.conversion = result.conversion || 1;
                    });
                },

                getStock(index){
                    let item = this.details[index];
                    let app = this;
                    $.ajax({
                        url: '{{ route('transfer.stock') }}',
                        type: 'GET',
                        data: {
                            part: item.part,
                            warehouse: app.fromId,
                        }
                    }).then(function (result){
                        item.stock = result.stock;
                    });
                },
                getStockModal(){
                    let item = this.detailModal;
                    let app = this;
                    if (!item.part || !app.fromId) return;
                    $.ajax({
                        url: '{{ route('transfer.stock') }}',
                        type: 'GET',
                        data: {
                            part: item.part,
                            warehouse: app.fromId,
                        }
                    }).then(function (result){
                        item.stock = result.stock;
                    });
                },

                deleteDetail(item){
                    this.details = this.details.filter(function (x) { return x !== item; });
                }
            },
            computed: {

            },
            watch: {
                fromId(){
                    for(var i = 0; i < this.details.length; i++){
                        this.details[i].stock_key = '';
                        this.details[i].cartoon = '';
                        this.getStock(i);
                    }
                    if (this.detailModal && this.detailModal.part) {
                        this.detailModal.stock_key = '';
                        this.detailModal.cartoon = '';
                        this.refreshStockOptions(this.detailModal);
                    }
                }
            },
            mounted() {
                let app = this;

                FormPreserver.initVue(app, 'stock_transfer_add', ['isLoading']);

                $('#transfer-detail-modal').on('select2:select select2:clear', '.transfer-modal-part', function (e){
                    if (app.shouldIgnoreModalSelectEvent(e)) return;
                    if($(this).val() != null){
                        app.detailModal.part = $(this).val();
                        app.setModalPartName();
                        app.detailModal.unitUrl = '{{ route('misc.partunit2') }}?id=' + app.detailModal.part;
                        app.detailModal.unit = null;
                        app.detailModal.conversion = 1;
                        app.detailModal.stock = 0;
                        app.detailModal.stock_key = '';
                        app.detailModal.cartoon = '';
                        $('#transfer-detail-modal .transfer-modal-unit').val('').trigger({ type: 'change', params: { quiet: true } });
                        app.refreshStockOptions(app.detailModal);
                        app.$delete(app.detailModalErrors, 'part');
                    }
                });

                $('#transfer-detail-modal').on('select2:select select2:clear', '.transfer-modal-unit', function (e){
                    if (app.shouldIgnoreModalSelectEvent(e)) return;
                    if($(this).val() != null){
                        app.detailModal.unit = $(this).val();
                        app.detailModal.stock_key = '';
                        app.detailModal.cartoon = '';
                        app.detailModal.stock = 0;
                        app.refreshStockOptions(app.detailModal);
                        app.$delete(app.detailModalErrors, 'unit');
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






    </script>
@endsection
