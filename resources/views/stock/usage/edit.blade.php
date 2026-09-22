@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Edit Advanced Part Usage</title>
@endsection

@section('content')
    @php
        $title = 'Edit Part Usage';
        $formAction = route('usage.update');
        $backRoute = route('usage');
        $details = old('DetailsJson') ? json_decode(old('DetailsJson'), true) : $details;
        $mode = 'edit';
    @endphp
@php
    $isEdit = $mode === 'edit';
@endphp

<div class="px-lg-5 py-lg-3 p-3" id="vue-container">
    <form autocomplete="off" method="post" action="{{ $formAction }}" id="usage-form">
        @csrf
        <input type="hidden" name="DetailsJson" :value="JSON.stringify(details)">

        <div class="d-flex flex-row align-items-center mb-5">
            <a href="{{ $backRoute }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
            <h1 class="h3 fw-bold ms-4 mb-0">
                {{ $title }}
            </h1>
        </div>

        @if (count($errors->all()) > 0)
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <p class="m-0 fs-6">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="block block-rounded">
            <div class="block-content pb-3">
                <button type="submit" class="btn btn-primary mb-3 fs-6">
                    <i class="fa fa-fw fa-save me-2"></i>Save
                </button>

                <div class="row">
                    <div class="col-lg-6 col-12 pe-lg-5">
                        <div class="row align-items-center mb-3">
                            <div class="col-lg-5 col-12 mb-lg-0 mb-3">
                                <label class="form-label">Transaction No <span class="text-danger">*</span></label>
                                @if ($isEdit)
                                    <input type="text" class="form-control" value="{{ old('TransactionNo', $usage->TransactionNo) }}" disabled>
                                    <input type="hidden" name="TransactionNo" value="{{ old('TransactionNo', $usage->TransactionNo) }}">
                                @else
                                    <input type="text" name="TransactionNo" id="TransactionNo" class="form-control"
                                        value="{{ old('TransactionNo') ?? '' }}"
                                        {{ old('automatic') ? 'disabled' : (count($errors->all()) > 0 ? '' : 'disabled') }}>
                                @endif
                            </div>
                            @if (!$isEdit)
                                <div class="col-lg-auto col-12 mb-lg-0 mb-3">
                                    <label class="form-label"></label>
                                    <div class="form-check pt-lg-2">
                                        <input class="form-check-input fs-6" type="checkbox" value="1" name="automatic" id="automatic"
                                            {{ old('automatic') ? 'checked' : (count($errors->all()) > 0 ? '' : 'checked') }}>
                                        <label class="form-check-label fs-6" for="automatic">Automatic</label>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label">WO Number</label>
                            <input type="text" name="WONumber" class="form-control" v-model="header.WONumber">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Division <span class="text-danger">*</span></label>
                            <select id="DivisionID" name="DivisionID" class="form-select" required></select>
                        </div>
                    </div>

                    <div class="col-lg-6 col-12 ps-lg-5">
                        <div class="mb-3">
                            <label class="form-label">Transaction Date <span class="text-danger">*</span></label>
                            <input type="text" class="js-flatpickr form-control" name="TransactionDate" id="TransactionDate"
                                placeholder="d/m/Y" v-model="header.TransactionDate" readonly required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Expired Date</label>
                            <div class="input-group">
                                <input type="text" class="js-flatpickr form-control" name="ExpiredDate" id="ExpiredDate"
                                    placeholder="d/m/Y" v-model="header.ExpiredDate" readonly>
                                <button type="button" class="btn btn-alt-secondary" @click="header.ExpiredDate = ''; $('#ExpiredDate').val('')">
                                    Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-content">
                <div class="d-flex flex-row justify-content-between align-items-center mb-3">
                    <h5 class="m-0">Part Usage Detail</h5>
                    <button type="button" class="btn btn-primary" @click="openDetailModal">
                        <i class="fa fa-fw fa-list me-1"></i>Choose Part
                    </button>
                </div>

                <div class="table-responsive w-100" style="overflow-x: auto">
                    <table class="table table-bordered nowrap w-100" style="table-layout: fixed;">
                        <thead>
                            <tr>
                                <th style="width: 105px;">Action</th>
                                <th style="width: 220px;">Part</th>
                                <th style="width: 140px;">Unit</th>
                                <th style="width: 130px;">Qty Actual</th>
                                <th style="width: 160px;">Qty 2</th>
                                <th style="width: 180px;">Warehouse</th>
                                <th style="width: 160px;">Batch No</th>
                                <th style="width: 160px;">Coil No</th>
                                <th style="width: 200px;">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="details.length === 0">
                                <td colspan="12" class="text-center p-3">No Details Added Yet</td>
                            </tr>
                            <tr v-for="(detail, index) in details" :key="index">
                                <td>
                                    <div class="d-flex flex-row flex-nowrap gap-1">
                                    <button type="button" class="btn btn-sm btn-secondary me-1" @click="editDetail(index)">
                                        <i class="fa fa-fw fa-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" @click="deleteDetail(index)">
                                        <i class="fa fa-fw fa-trash"></i>
                                    </button>
                                    </div>
                                </td>
                                <td><input type="text" class="form-control" :value="displayPart(detail)" readonly></td>
                                <td><input type="text" class="form-control" v-model="detail.UnitID" readonly></td>
                                <td><input type="number" class="form-control" v-model="detail.Qty" readonly></td>
                                <td><input type="text" class="form-control" :value="displayQty2(detail)" readonly></td>
                                <td><input type="text" class="form-control" :value="displayDash(detail.WarehouseName)" readonly></td>
                                <td><input type="text" class="form-control" :value="displayStockValue(detail.BatchNo)" readonly></td>
                                <td><input type="text" class="form-control" :value="displayDash(detail.CoilNo)" readonly></td>
                                <td><input type="text" class="form-control" v-model="detail.Notes" readonly></td>
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
                        <textarea name="Notes" class="form-control" rows="3" v-model="header.Notes"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="modal fade" id="usage-detail-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Part Usage Detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-6 col-12 mb-3">
                            <label class="form-label">Part</label>
                            <select id="modal-part" class="form-select"></select>
                        </div>
                        <div class="col-lg-3 col-12 mb-3">
                            <label class="form-label">Warehouse</label>
                            <select id="modal-warehouse" class="form-select modal-stock-field"></select>
                        </div>
                        <div class="col-lg-3 col-12 mb-3">
                            <label class="form-label">Batch No</label>
                            <div class="input-group">
                                <select id="modal-batch-no" class="form-select modal-stock-field"></select>
                                <button type="button" class="btn btn-alt-secondary" @click="clearStockAttribute('BatchNo')" title="Clear Batch No"><i class="fa fa-times"></i></button>
                            </div>
                        </div>                        <div class="col-lg-3 col-12 mb-3">
                            <label class="form-label">Stock</label>
                            <input type="text" class="form-control" :value="displayStockQty(modalDetail)" readonly>
                        </div>
                        <div class="col-lg-3 col-12 mb-3">
                            <label class="form-label">Conversion</label>
                            <input type="text" class="form-control" :value="displayConversion(modalDetail)" readonly>
                        </div>
                        <div class="col-lg-3 col-12 mb-3">
                            <label class="form-label">Coil No</label>
                            <input type="text" class="form-control" :value="displayDash(modalDetail.CoilNo)" readonly>
                        </div>
                        <div class="col-lg-3 col-12 mb-3">
                            <label class="form-label">Unit</label>
                            <select class="form-select" v-model="modalDetail.InputUnit">
                                <option :value="modalDetail.UnitID" v-if="modalDetail.UnitID">@{{ modalDetail.UnitID }}</option>
                                <option :value="modalDetail.UnitID2" v-if="modalDetail.UnitID2">@{{ modalDetail.UnitID2 }}</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-12 mb-3">
                            <label class="form-label">Qty</label>
                            <vue-autonumeric :options="autonumericFormat2" class="form-control"
                                v-model="modalDetail.InputQty"
                                :readonly="false"></vue-autonumeric>
                        </div>
                        <div class="col-lg-3 col-12 mb-3">
                            <label class="form-label">Qty Actual</label>
                            <input type="text" class="form-control" :value="displayQty1(modalDetail)" readonly>
                        </div>
                        <div class="col-lg-3 col-12 mb-3">
                            <label class="form-label">Qty 2</label>
                            <input type="text" class="form-control" :value="displayQty2(modalDetail)" readonly>
                        </div>
                        <div class="col-lg-6 col-12 mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" rows="3" v-model="modalDetail.Notes"></textarea>
                        </div>
                    </div>

                    <hr>

                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-alt-secondary" @click="clearAllStockAttributes">
                        <i class="fa fa-fw fa-eraser"></i> Clear Details
                    </button>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="saveModalDetail">
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
        #usage-detail-modal .select2-container { width: 100% !important; }
        #usage-detail-modal .select2-selection--single { min-height: 38px; }
        #usage-detail-modal .modal-exp-date-picker { width: 44px; flex: 0 0 44px; }
        #usage-detail-modal .input-group .select2-container {
            flex: 1 1 auto;
            min-width: 0;
            width: 1% !important;
        }
        #modal-exp-date-picker {
            width: 44px;
            flex: 0 0 44px;
        }
    </style>
@endsection

@section('scripts')
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('js/plugins/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('js/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.5.4"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.13/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue-autonumeric@1.2.6/dist/vue-autonumeric.min.js"></script>

    <script>
        const nullFilterValue = '__NULL__';
        const availableStockDetailsUrl = @json(route('helper.available_stock_details'));
        const detailColumnMap = {
            batch_no: 'BatchNo',
            serial_no: 'SerialNo',
            exp_date: 'ExpDate',
            bin: 'BIN',
            loc: 'LOC',
        };
        const isEditMode = @json($isEdit);

        let app = new Vue({
            el: '#vue-container',
            data: {
                header: {
                    WONumber: @json(old('WONumber', $usage->WONumber ?? '')),
                    DivisionID: @json(old('DivisionID', $usage->DivisionID ?? '')),
                    TransactionDate: @json(old('TransactionDate', isset($usage) ? \Carbon\Carbon::parse($usage->TransactionDate)->format('d/m/Y') : date('d/m/Y'))),
                    ExpiredDate: @json(old('ExpiredDate', isset($usage) && $usage->ExpiredDate ? \Carbon\Carbon::parse($usage->ExpiredDate)->format('d/m/Y') : '')),
                    Notes: @json(old('Notes', $usage->Notes ?? '')),
                },
                details: @json($details ?: []),
                modalDetail: freshModalDetail(),
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
            mounted() {
                initHeaderControls(this);
            },
            methods: {
                openDetailModal() {
                    this.modalDetail = freshModalDetail();
                    resetModalSelects();
                    $('#usage-detail-modal').modal('show');
                    setTimeout(initModalControls, 80);
                },
                editDetail(index) {
                    const row = JSON.parse(JSON.stringify(this.details[index]));
                    row.editIndex = index;
                    this.modalDetail = Object.assign(freshModalDetail(), row);
                    syncInputUnit(this.modalDetail);
                    $('#usage-detail-modal').modal('show');
                        setTimeout(function() {
                            initModalControls();
                            setSelectValue('#modal-part', row.PartID, app.displayPart(row));
                            setSelectValue('#modal-warehouse', row.WarehouseID, row.WarehouseName || row.WarehouseID);
                            setSelectValue('#modal-batch-no', row.BatchNo, stockDisplayValue(row.BatchNo));
                        setSelectValue('#modal-serial-no', row.SerialNo, stockDisplayValue(row.SerialNo));
                        setSelectValue('#modal-exp-date', row.ExpDate, stockDisplayValue(row.ExpDate));
                        setSelectValue('#modal-bin', row.BIN, stockDisplayValue(row.BIN));
                        setSelectValue('#modal-loc', row.LOC, stockDisplayValue(row.LOC));
                    }, 100);
                },
                deleteDetail(index) {
                    this.details.splice(index, 1);
                },
                async saveModalDetail() {
                    if (!this.modalDetail.PartID || !this.modalDetail.UnitID || !this.modalDetail.WarehouseID) {
                        Swal.fire('Required', 'Part, unit, and warehouse are required.', 'warning');
                        return;
                    }

                    if (false) {
                        this.modalDetail.Qty = 1;
                    }

                    if (Number(this.modalDetail.InputQty || 0) <= 0) {
                        Swal.fire('Invalid Qty', 'Qty must be greater than 0.', 'warning');
                        return;
                    }

                    if (!isEditMode) {
                        let availableStock = null;
                        try {
                            availableStock = await fetchModalStockQty();
                        } catch (error) {
                            Swal.fire('Stock Check Failed', 'Unable to validate available stock. Please try again.', 'warning');
                            return;
                        }

                        if (availableStock === null) {
                            Swal.fire('Stock Details Required', 'Please choose stock details so available stock can be validated.', 'warning');
                            return;
                        }

                        this.modalDetail.Conversion = resolveConversion(this.modalDetail);
                        this.modalDetail.Qty = calculatePrimaryInputQty(this.modalDetail);
                        const requiredQty = Number(this.modalDetail.Qty || 0);
                        if (requiredQty > Number(availableStock || 0)) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Not Enough Stock',
                                html: stockNotEnoughMessage(),
                            });
                            return;
                        }
                    }

                    this.modalDetail.Conversion = resolveConversion(this.modalDetail);
                    this.modalDetail.Qty = calculatePrimaryInputQty(this.modalDetail);
                    if (this.modalDetail.Qty === null || Number.isNaN(Number(this.modalDetail.Qty))) {
                        Swal.fire('Conversion Unavailable', 'The selected unit cannot be converted because the current stock does not have a valid Qty 1 and Qty 2 ratio.', 'warning');
                        return;
                    }
                    const row = JSON.parse(JSON.stringify(this.modalDetail));
                    if (row.editIndex === null) {
                        this.details.push(row);
                    } else {
                        this.details.splice(row.editIndex, 1, row);
                    }

                    $('#usage-detail-modal').modal('hide');
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
                    if (field === 'BatchNo') {
                        this.modalDetail.CoilNo = null;
                        this.modalDetail.StockQty2 = null;
                        this.modalDetail.UnitID2 = null;
                        syncInputUnit(this.modalDetail);
                    }
                    this.modalDetail.StockQty = null;
                },
                clearAllStockAttributes() {
                    ['BatchNo', 'SerialNo', 'ExpDate', 'BIN', 'LOC'].forEach(field => this.clearStockAttribute(field));
                },
                displayPart(detail) {
                    return detail.PartID ? detail.PartID + (detail.PartName ? ' - ' + detail.PartName : '') : '';
                },
                displayStockValue(value) {
                    return value === nullFilterValue ? '' : (value || '');
                },
                displayDash(value) {
                    return value === null || value === undefined || value === '' ? '-' : value;
                },
                displayQty2(detail) {
                    return formatQty2(calculateQty2(detail), detail.UnitID2);
                },
                displayStockQty(detail) {
                    const stock = Number(detail && detail.StockQty ? detail.StockQty : 0);
                    if (!stock) return '-';
                    const formatted = Number(stock).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 6 });
                    return formatted + (detail.UnitID ? ' ' + detail.UnitID : '');
                },
                displayConversion(detail) {
                    const conversion = resolveConversion(detail);
                    if (conversion === null || Number.isNaN(Number(conversion))) return '-';
                    return Number(conversion).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 6 });
                },
                displayQty1(detail) {
                    const qty = calculatePrimaryInputQty(detail);
                    if (qty === null || Number.isNaN(qty)) return '-';
                    const formatted = Number(qty).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 6 });
                    return formatted + (detail.UnitID ? ' ' + detail.UnitID : '');
                },
            },
        });

        function freshModalDetail() {
            return {
                editIndex: null,
                PartID: null,
                PartName: '',
                WithSerialNo: 0,
                UnitID: null,
                Conversion: 1,
                Qty: 1,
                InputQty: 1,
                InputUnit: null,
                StockQty: null,
                StockQty2: null,
                UnitID2: null,
                WarehouseID: null,
                WarehouseName: null,
                BatchNo: null,
                CoilNo: null,
                SerialNo: null,
                ExpDate: null,
                BIN: null,
                LOC: null,
                Notes: '',
            };
        }

        function initHeaderControls(vm) {
            $('#TransactionDate').flatpickr({
                dateFormat: 'd/m/Y',
                onChange: function(selectedDates, dateStr) {
                    vm.header.TransactionDate = dateStr;
                }
            });
            $('#ExpiredDate').flatpickr({
                dateFormat: 'd/m/Y',
                onChange: function(selectedDates, dateStr) {
                    vm.header.ExpiredDate = dateStr;
                }
            });

            $('#DivisionID').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: 'Pick Division',
                ajax: {
                    url: '{{ route('misc.division2') }}',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ search: params.term || '' }),
                    processResults: data => ({ results: data }),
                }
            }).on('change', function() {
                vm.header.DivisionID = $(this).val() || '';
            });

            if (vm.header.DivisionID) {
                setSelectValue('#DivisionID', vm.header.DivisionID, vm.header.DivisionID);
            }

            $('#automatic').on('change', function() {
                const isAutomatic = $(this).is(':checked');
                $('#TransactionNo')
                    .prop('disabled', isAutomatic)
                    .prop('readonly', isAutomatic)
                    .prop('required', !isAutomatic);
            }).trigger('change');
        }

        function initModalControls() {
            initPartSelect();
            initWarehouseSelect();
            initStockSelect('#modal-batch-no', 'batch_no', 'BatchNo', 'BatchNo');
            initExpDatePicker();
        }

        function initPartSelect() {
            ajaxSelect('#modal-part', '{{ route('misc.part') }}', 'Pick Part', params => ({ search: params.term || '' }), data => ({ results: data }))
                .off('select2:select.part change.part')
                .on('select2:select.part', function(event) {
                    const selected = event.params.data;
                    app.modalDetail.PartID = selected.id;
                    app.modalDetail.PartName = selected.text ? selected.text.replace(selected.id + ' - ', '') : '';
                    app.modalDetail.WithSerialNo = selected.WithSerialNo || 0;
	                    app.modalDetail.UnitID = null;
	                    app.modalDetail.Conversion = 1;
	                    app.modalDetail.Qty = 1;
	                    app.modalDetail.InputQty = 1;
	                    app.modalDetail.InputUnit = null;
                    app.modalDetail.SerialNo = null;
                    app.clearAllStockAttributes();
                    fetchLowestUnit(selected.id);
                })
                .on('change.part', function() {
                    if (!$(this).val()) {
                        app.modalDetail.PartID = null;
                        app.modalDetail.PartName = '';
                        app.modalDetail.WithSerialNo = 0;
	                        app.modalDetail.UnitID = null;
	                        app.modalDetail.Conversion = 1;
	                        app.modalDetail.Qty = 1;
	                        app.modalDetail.InputQty = 1;
	                        app.modalDetail.InputUnit = null;
                        app.modalDetail.SerialNo = null;
                    }
                });
        }

        function initWarehouseSelect() {
            ajaxSelect('#modal-warehouse', '{{ route('misc.warehouse2') }}', 'Pick Warehouse', params => ({ search: params.term || '' }), data => ({ results: data }))
                .off('change.warehouse')
                .on('change.warehouse', function() {
                    app.modalDetail.WarehouseID = $(this).val() || null;
                    const selected = $(this).select2('data')[0] || null;
                    app.modalDetail.WarehouseName = selected ? warehouseDisplayName(selected.text || selected.id) : null;
                });
        }

        function warehouseDisplayName(value) {
            if (!value) return null;
            const text = String(value);
            const separator = ' - ';
            return text.includes(separator) ? text.substring(text.indexOf(separator) + separator.length) : text;
        }

        function initStockSelect(selector, target, field, column) {
            ajaxSelect(selector, availableStockDetailsUrl, 'Pick an Item', function(params) {
                const data = {
                    part_id: app.modalDetail.PartID || '',
                    warehouse_id: app.modalDetail.WarehouseID || '',
                    target: target,
                    filter_qty: 0,
                    term: params.term || '',
                };

                Object.entries(selectedStockFilters()).forEach(function(entry) {
                    if (entry[0] !== target) {
                        data[entry[0]] = entry[1];
                    }
                });

                return data;
            }, function(response) {
                const rows = response && response.data && response.data.results ? response.data.results : [];
                return {
                    results: rows.map(function(row) {
                        const value = normalizeStockValue(row[column]);
                        return {
                            id: value,
                            text: stockDisplayValue(value),
                            total_qty: row.total_qty,
                            total_qty2: row.total_qty2,
                            unit_id2: row.unit_id2 || row.UnitID2 || null,
                            coil_no: row.coil_no || row.CoilNo || null,
                        };
                    })
                };
            }).off('change.stock').on('change.stock', function() {
                app.modalDetail[field] = $(this).val() || null;
                const selected = $(this).select2('data')[0] || null;
                    app.modalDetail.StockQty = selected && selected.total_qty !== undefined ? Number(selected.total_qty || 0) : null;
                    if (field === 'BatchNo') {
                        app.modalDetail.CoilNo = selected && selected.coil_no ? selected.coil_no : null;
                        app.modalDetail.StockQty2 = selected && selected.total_qty2 !== undefined ? Number(selected.total_qty2 || 0) : null;
                        app.modalDetail.UnitID2 = selected && selected.unit_id2 ? selected.unit_id2 : null;
                        syncInputUnit(app.modalDetail);
                    }
                if (field === 'ExpDate') {
                }
            });
        }

        function ajaxSelect(selector, url, placeholder, data, processResults) {
            if ($(selector).hasClass('select2-hidden-accessible')) {
                $(selector).select2('destroy');
            }

            return $(selector).select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#usage-detail-modal').has(selector).length ? $('#usage-detail-modal') : $(document.body),
                allowClear: true,
                placeholder: placeholder,
                ajax: {
                    url: url,
                    dataType: 'json',
                    delay: 250,
                    data: data,
                    processResults: processResults,
                    cache: true,
                }
            });
        }

        function selectedStockFilters() {
            const filters = {
                batch_no: app.modalDetail.BatchNo,
                serial_no: app.modalDetail.SerialNo,
                exp_date: app.modalDetail.ExpDate,
                bin: app.modalDetail.BIN,
                loc: app.modalDetail.LOC,
            };
            const data = {};

            Object.keys(detailColumnMap).forEach(function(key) {
                if (key === 'serial_no' && true) {
                    return;
                }

                if (filters[key]) {
                    data[key] = filters[key];
                }
            });

            return data;
        }

        function exactStockFiltersForValidation(target) {
            const filters = {
                batch_no: app.modalDetail.BatchNo || nullFilterValue,
                serial_no: false ? (app.modalDetail.SerialNo || nullFilterValue) : nullFilterValue,
                exp_date: app.modalDetail.ExpDate || nullFilterValue,
                bin: app.modalDetail.BIN || nullFilterValue,
                loc: app.modalDetail.LOC || nullFilterValue,
            };
            const data = {};

            Object.keys(filters).forEach(function(key) {
                if (key !== target) {
                    data[key] = filters[key];
                }
            });

            return data;
        }

        function modalStockQtyTarget() {
            if (app.modalDetail.BatchNo) return 'batch_no';
            if (false && app.modalDetail.SerialNo) return 'serial_no';
            if (app.modalDetail.ExpDate) return 'exp_date';
            if (app.modalDetail.BIN) return 'bin';
            if (app.modalDetail.LOC) return 'loc';
            return 'batch_no';
        }

        function selectedValueForTarget(target) {
            const values = {
                batch_no: app.modalDetail.BatchNo || nullFilterValue,
                serial_no: app.modalDetail.SerialNo || nullFilterValue,
                exp_date: app.modalDetail.ExpDate || nullFilterValue,
                bin: app.modalDetail.BIN || nullFilterValue,
                loc: app.modalDetail.LOC || nullFilterValue,
            };

            return values[target] || nullFilterValue;
        }

        function fetchModalStockQty() {
            if (!app.modalDetail.PartID || !app.modalDetail.WarehouseID) {
                return Promise.resolve(0);
            }

            const target = modalStockQtyTarget();
            const selectedValue = selectedValueForTarget(target);
            const data = Object.assign({
                part_id: app.modalDetail.PartID,
                warehouse_id: app.modalDetail.WarehouseID,
                target: target,
                filter_qty: 0,
                term: selectedValue,
            }, exactStockFiltersForValidation(target));

            return $.ajax({
                url: availableStockDetailsUrl,
                type: 'GET',
                data: data,
            }).then(function(response) {
                const column = detailColumnMap[target];
                const rows = response && response.data && response.data.results ? response.data.results : [];
                const row = rows.find(function(item) {
                    return normalizeStockValue(item[column]) === selectedValue;
                });

                app.modalDetail.StockQty = row && row.total_qty !== undefined ? Number(row.total_qty || 0) : null;
                app.modalDetail.StockQty2 = row && row.total_qty2 !== undefined ? Number(row.total_qty2 || 0) : null;
                app.modalDetail.UnitID2 = row ? (row.unit_id2 || row.UnitID2 || null) : null;
                syncInputUnit(app.modalDetail);
                return app.modalDetail.StockQty;
            });
        }

        function syncInputUnit(detail) {
            if (!detail) return;
            if (!detail.InputUnit || (detail.InputUnit !== detail.UnitID && detail.InputUnit !== detail.UnitID2)) {
                detail.InputUnit = detail.UnitID || detail.UnitID2 || null;
            }
            if (detail.InputQty === null || detail.InputQty === undefined || detail.InputQty === '') {
                detail.InputQty = detail.Qty || 0;
            }
        }

        function resolveConversion(detail) {
            if (!detail) return 1;
            if (!detail.InputUnit || (detail.UnitID && detail.InputUnit === detail.UnitID)) {
                return 1;
            }
            if (detail.UnitID2 && detail.InputUnit === detail.UnitID2) {
                const stockQty = Math.abs(Number(detail.StockQty || 0));
                const stockQty2 = Math.abs(Number(detail.StockQty2 || 0));
                if (!stockQty || !stockQty2) return null;
                return stockQty / stockQty2;
            }

            return Number(detail.Conversion || 1);
        }

        function calculatePrimaryInputQty(detail) {
            if (!detail) return null;
            const inputQty = Number(detail.InputQty !== undefined && detail.InputQty !== null ? detail.InputQty : detail.Qty || 0);
            const conversion = resolveConversion(detail);
            if (conversion === null || Number.isNaN(Number(conversion))) return null;

            return inputQty * Number(conversion || 1);
        }

        function calculateQty2(detail) {
            const qty = Number(calculatePrimaryInputQty(detail) || 0);
            const stockQty = Math.abs(Number(detail && detail.StockQty ? detail.StockQty : 0));
            const stockQty2 = Math.abs(Number(detail && detail.StockQty2 ? detail.StockQty2 : 0));
            if (!stockQty || !stockQty2) return null;
            return qty / stockQty * stockQty2;
        }

        function formatQty2(qty2, unitId2) {
            if (qty2 === null || qty2 === undefined || Number.isNaN(Number(qty2))) return '-';
            const formatted = Number(qty2).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
            return formatted + (unitId2 ? ' ' + unitId2 : '');
        }

        function stockNotEnoughMessage() {
            const details = {
                Part: app.modalDetail.PartID,
                Warehouse: app.modalDetail.WarehouseID,
                'Batch No': app.modalDetail.BatchNo,
                'Serial No': false ? app.modalDetail.SerialNo : null,
                'Exp Date': app.modalDetail.ExpDate,
                BIN: app.modalDetail.BIN,
                LOC: app.modalDetail.LOC,
            };

            return Object.keys(details).map(function(label) {
                const value = details[label];
                return '<div><strong>' + label + ':</strong> ' + (value === null || value === undefined || value === '' || value === nullFilterValue ? '(Empty)' : value) + '</div>';
            }).join('');
        }

        function resetModalSelects() {
            ['#modal-part', '#modal-warehouse', '#modal-batch-no', '#modal-serial-no', '#modal-exp-date', '#modal-bin', '#modal-loc'].forEach(function(selector) {
                if ($(selector).hasClass('select2-hidden-accessible')) {
                    $(selector).select2('destroy');
                }

                $(selector).empty().val(null);
            });
        }

        function fetchLowestUnit(partId) {
            if (!partId) {
                app.modalDetail.UnitID = null;
                app.modalDetail.Conversion = 1;
                return;
            }

            $.get('{{ url('misc/pu') }}/' + encodeURIComponent(partId) + '/units')
                .done(function(units) {
	                    const unit = Array.isArray(units) && units.length > 0 ? units[0] : null;
	                    app.modalDetail.UnitID = unit ? unit.UnitID : null;
	                    app.modalDetail.Conversion = unit ? Number(unit.Conversion || 1) : 1;
	                    syncInputUnit(app.modalDetail);
                })
                .fail(function() {
                    app.modalDetail.UnitID = null;
                    app.modalDetail.Conversion = 1;
                    Swal.fire('Unit Not Found', 'Unable to fetch lowest unit for selected part.', 'warning');
                });
        }

        function setSelectValue(selector, value, text) {
            if (value === null || value === undefined || value === '') {
                $(selector).val(null).trigger('change.select2');
                return;
            }

            if ($(selector + ' option[value="' + value + '"]').length === 0) {
                $(selector).append(new Option(text || value, value, true, true));
            }

            $(selector).val(value).trigger('change.select2');
        }

        function stockDisplayValue(value) {
            return value === nullFilterValue ? '(Empty)' : (value || '');
        }

        function normalizeStockValue(value) {
            return value === null || value === undefined || value === '' ? nullFilterValue : value;
        }

        function setExpDatePickerValue(value) {
            const input = $('#modal-exp-date-picker');
            if (input[0] && input[0]._flatpickr) {
                input[0]._flatpickr.setDate(value || null, false, 'Y-m-d');
            }
        }

        function initExpDatePicker() {
            const input = $('#modal-exp-date-picker');
            if (!input.length) return;
            if (!input[0]._flatpickr) {
                input.flatpickr({
                    dateFormat: 'Y-m-d',
                    allowInput: false,
                    onChange: function(selectedDates, dateStr) {
                        app.modalDetail.ExpDate = dateStr || null;
                        setSelectValue('#modal-exp-date', dateStr, dateStr);
                    }
                });
            }
        }
    </script>
@endsection
