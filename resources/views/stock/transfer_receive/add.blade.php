@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Add Item Transfer Receive</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <form autocomplete="off" method="post" action="{{ route('transfer_receive.store') }}" id="receive-form">
            @csrf

            <div class="d-flex flex-row align-items-center mb-5">
                <a href="{{ route('transfer_receive') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                <h1 class="h3 fw-bold ms-4 mb-0">
                    Add Item Transfer Receive
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
                                <div class="col-lg-4 col-12 mb-lg-0 mb-3">
                                    <label class="form-label">Transaction No <span class="text-danger">*</span></label>
                                    <input type="text" name="TransactionNo" id="TransactionNo" class="form-control" v-model="transactionNo" required :disabled="automatic">
                                </div>
                                <div class="col-lg-auto col-12 mb-lg-0 mb-3">
                                    <label class="form-label"></label>
                                    <div class="form-check pt-lg-2">
                                        <input class="form-check-input fs-6" type="checkbox" value="1" name="automatic" id="automatic" v-model="automatic">
                                        <label class="form-check-label fs-6" for="automatic">
                                            Automatic
                                        </label>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-12">
                                    <label class="form-label">Transaction Date <span class="text-danger">*</span></label>
                                    <input type="text" class="js-flatpickr form-control" id="TransactionDate" name="TransactionDate" placeholder="d/m/Y" v-model="transactionDate" readonly="readonly" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-lg-6 col-12">
                                    <label class="form-label">Execute No <span class="text-danger">*</span></label>
                                    <select name="ExecuteNo" id="ExecuteNo" class="form-select" required>
                                        <option value="">-</option>
                                        @foreach($executes as $execute)
                                            <option value="{{ $execute->TransactionNo }}">{{ $execute->TransactionNo }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <label class="form-label">Staff In Charge (To) <span class="text-danger">*</span></label>
                                    <select name="StaffInChargeTo" id="StaffInChargeTo" class="form-select" required>
                                        <option value="">-</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 col-12 ps-lg-5">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label">Warehouse To</label>
                                    <input type="text" class="form-control" v-model="warehouseTo" readonly>
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
                    </div>

                    <div class="table-responsive w-100">
                        <table class="table table-bordered nowrap w-100">
                            <thead>
                            <tr>
                                <th style="width: 300px;">Part</th>
                                <th style="width: 150px;">Unit</th>
                                <th style="width: 150px;">Qty</th>
                                <th style="width: 150px;">Total Qty</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-if="details.length == 0">
                                <td colspan="4" class="text-center p-3">Please select an Execute No to pull items</td>
                            </tr>
                            <tr v-for="(detail, index) in details" :key="index">
                                <td>
                                    <input type="hidden" name="sequence[]" :value="detail.sequence">
                                    <input type="hidden" name="part[]" :value="detail.part">
                                    <input type="text" class="form-control" :value="detail.part_name" readonly>
                                </td>
                                <td>
                                    <input type="hidden" name="unit[]" :value="detail.unit">
                                    <input type="text" class="form-control" :value="detail.unit_name" readonly>
                                </td>
                                <td>
                                    <input type="number" name="qty[]" class="form-control text-end" v-model.number="detail.qty" min="0" step="any">
                                    <div v-if="detail.qty > detail.max_qty" class="text-warning fs-xs">Surplus: @{{ formatNumber(detail.qty - detail.max_qty) }}</div>
                                    <div v-if="detail.qty < detail.max_qty" class="text-danger fs-xs">Shortage: @{{ formatNumber(detail.max_qty - detail.qty) }}</div>
                                </td>
                                <td>
                                    <input type="text" class="form-control text-end" :value="formatNumber(detail.qty * detail.conversion)" readonly>
                                </td>
                            </tr>
                            </tbody>
                        </table>
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
    </style>
@endsection

@section('scripts')
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.js') }}"></script>
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('js/plugins/flatpickr/flatpickr.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.13/dist/vue.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.0/axios.min.js"></script>
    <script src="{{ asset('js/moment.min.js') }}"></script>

    <script>
        let app = new Vue({
            el: '#vue-container',
            data: {
                transactionNo: '{{ old('TransactionNo') ?? '' }}',
                automatic: {{ old('automatic') ? 'true' : (count($errors->all()) > 0 ? 'false' : 'true') }},
                transactionDate: '{{ old('TransactionDate') ?? date('d/m/Y') }}',
                executeNo: '',
                staffInChargeTo: '',
                staffInChargeToText: '',
                warehouseTo: '',
                details: [],
                isRestoring: false,
            },
            methods: {
                formatNumber(val) {
                    if (val === undefined || val === null || isNaN(val)) return '0';
                    return new Intl.NumberFormat('id-ID', {
                        maximumFractionDigits: 6
                    }).format(val);
                },
                fetchExecuteDetails() {
                    if (!this.executeNo) {
                        this.warehouseTo = '';
                        this.details = [];
                        return;
                    }

                    let url = '{{ route('transfer_receive.execute_details') }}';
                    axios.get(url, {
                        params: {
                            executeNo: this.executeNo
                        }
                    })
                        .then(response => {
                            let data = response.data;

                            // Populate Staff In Charge To from Request first
                            let requestHD = data.requestHD || data.request_h_d;
                            if (requestHD && requestHD.StaffInChargeIDTo) {
                                let staffID = requestHD.StaffInChargeIDTo;
                                let staff_to = requestHD.staff_to || requestHD.staffTo;
                                let staffName = requestHD.staff_to_name || (staff_to ? (staff_to.EmployeeID + ' - ' + (staff_to.EmployeeName || (staff_to.FirstName + ' ' + staff_to.LastName))) : staffID);

                                let $staffSelect = $('#StaffInChargeTo');

                                // Reset select2 first to ensure a clean state
                                $staffSelect.val(null).trigger('change');

                                // Check if option already exists
                                if ($staffSelect.find("option[value='" + staffID + "']").length === 0) {
                                    let newOption = new Option(staffName, staffID, true, true);
                                    $staffSelect.append(newOption);
                                }

                                $staffSelect.val(staffID).trigger('change');

                                // Manually trigger select2:select to notify listeners
                                $staffSelect.trigger({
                                    type: 'select2:select',
                                    params: {
                                        data: { id: staffID, text: staffName }
                                    }
                                });

                                this.staffInChargeTo = staffID;
                                this.staffInChargeToText = staffName;
                            } else {
                                $('#StaffInChargeTo').val(null).trigger('change');
                                this.staffInChargeTo = '';
                                this.staffInChargeToText = '';
                            }

                            this.warehouseTo = requestHD && requestHD.warehouse_to ? requestHD.warehouse_to.WarehouseName : (requestHD ? requestHD.WarehouseIDTo : '');

                            if (data.details) {
                                this.details = data.details.map(dt => ({
                                    sequence: dt.Sequence,
                                    part: dt.PartID,
                                    part_name: dt.part ? dt.part.PartName : dt.PartID,
                                    unit: dt.UnitID,
                                    unit_name: dt.unit ? dt.unit.UnitName : dt.UnitID,
                                    qty: dt.Qty,
                                    max_qty: dt.Qty,
                                    exp_date: dt.ExpDate ? moment(dt.ExpDate).format('DD/MM/YYYY') : '',
                                    dimension: dt.Dimension,
                                    cartoon: dt.CartoonNo,
                                    notes: dt.Notes,
                                    conversion: dt.Conversion || 1
                                }));
                            }
                        })
                        .catch(error => {
                            console.error(error);
                            alert('Failed to fetch execute details');
                        });
                }
            },
            mounted() {
                const self = this;

                self.isRestoring = true;
                FormPreserver.initVue(self, 'stock_transfer_receive_add', ['isRestoring']);

                $('#StaffInChargeTo').select2({
                    theme: 'bootstrap-5',
                    ajax: {
                        url: '{{ route('misc.employee', ['select2' => true]) }}',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                search: params.term
                            };
                        },
                        processResults: function (data) {
                            return {
                                results: data
                            };
                        },
                        cache: true
                    }
                }).on('change', function () {
                    self.staffInChargeTo = $(this).val();
                    let data = $(this).select2('data');
                    if (data && data.length > 0) {
                        self.staffInChargeToText = data[0].text;
                    }
                });

                $('#ExecuteNo').select2({
                    theme: 'bootstrap-5'
                }).on('change', function () {
                    self.executeNo = $(this).val();
                    if (!self.isRestoring) {
                        self.fetchExecuteDetails();
                    }
                });

                this.$nextTick(function() {
                    // Update Select2 UI
                    if (self.executeNo) {
                        $('#ExecuteNo').val(self.executeNo).trigger('change');
                    }

                    if (self.staffInChargeTo && self.staffInChargeToText) {
                        let newOption = new Option(self.staffInChargeToText, self.staffInChargeTo, true, true);
                        $('#StaffInChargeTo').append(newOption).trigger('change');
                    }

                    $('.js-flatpickr').flatpickr({
                        dateFormat: "d/m/Y",
                        onChange: function(selectedDates, dateStr, instance) {
                            self.transactionDate = dateStr;
                        }
                    });

                    self.isRestoring = false;
                });
            }
        });
    </script>
@endsection
