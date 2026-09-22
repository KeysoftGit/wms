@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Edit Item Transfer Execute</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <form autocomplete="off" method="post" action="{{ route('transfer_execute.update') }}" id="transfer-form">
            @csrf

            <div class="d-flex flex-row align-items-center mb-5">
                <a href="{{ route('transfer_execute') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                <h1 class="h3 fw-bold ms-4 mb-0">
                    Edit Item Transfer Execute
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
                    <button type="submit" class="btn btn-primary mb-3 fs-6"><i class="fa fa-fw fa-save me-2"></i>Update</button>

                    <div class="row">
                        <div class="col-lg-6 col-12">
                            <div class="row align-items-center mb-3">
                                <div class="col-lg-6 col-12">
                                    <label class="form-label">Transaction No <span class="text-danger">*</span></label>
                                    <input type="text" name="TransactionNo" id="TransactionNo" class="form-control" value="{{ $transfer->TransactionNo }}" readonly required>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <label class="form-label">Transaction Date <span class="text-danger">*</span></label>
                                    <input type="text" class="js-flatpickr form-control" id="TransactionDate" name="TransactionDate" placeholder="d/m/Y" value="{{ \Carbon\Carbon::parse($transfer->TransactionDate)->format('d/m/Y') }}" readonly="readonly" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-lg-6 col-12">
                                    <label class="form-label">Request No</label>
                                    <input type="text" class="form-control" name="RequestNo" value="{{ $transfer->RequestNo }}" readonly>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <label class="form-label">Staff In Charge <span class="text-danger">*</span></label>
                                    <select name="StaffInChargeFrom" id="StaffInChargeFrom" class="form-select" required>
                                        <option value="{{ $transfer->StaffInChargeFrom }}" selected>{{ $transfer->staffInChargeFrom->EmployeeName ?? $transfer->StaffInChargeFrom }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 col-12 ps-lg-5">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label">Warehouse From</label>
                                    <input type="text" class="form-control" v-model="warehouseFrom" readonly>
                                </div>
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
                                <th style="width: 120px;">Remaining Qty</th>
                                <th style="width: 150px;">Qty</th>
                                <th style="width: 150px;">Total Qty</th>
                            </tr>
                            </thead>
                            <tbody>
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
                                    <input type="text" class="form-control text-end" :value="detail.remaining_qty" readonly>
                                </td>
                                <td>
                                    <vue-autonumeric :options="autonumericFormat" name="qty[]" class="form-control" v-model="detail.qty" required></vue-autonumeric>
                                    <input type="hidden" name="dimension[]" v-model="detail.dimension">
                                    <input type="hidden" name="cartoon[]" v-model="detail.cartoon">
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
    <script src="{{ asset('js/moment.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.5.4"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.13/dist/vue.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.0/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue-autonumeric@1.2.6/dist/vue-autonumeric.min.js"></script>

    <script>
        let app = new Vue({
            el: '#vue-container',
            data: {
                requestNo: '{{ $transfer->RequestNo }}',
                staffInCharge: '{{ $transfer->StaffInChargeFrom }}',
                warehouseFrom: '',
                warehouseTo: '',
                details: [],
                autonumericFormat: {
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
                formatNumber(val) {
                    if (val === undefined || val === null || isNaN(val)) return '0';
                    return new Intl.NumberFormat('id-ID', {
                        maximumFractionDigits: 6
                    }).format(val);
                },
                fetchRequestDetails() {
                    if (!this.requestNo) return;
                    
                    let url = '{{ route('transfer_execute.request_details') }}';
                    axios.get(url, {
                        params: {
                            requestNo: this.requestNo
                        }
                    })
                        .then(response => {
                            let data = response.data;
                            this.warehouseFrom = data.warehouse_from ? data.warehouse_from.WarehouseName : (data.WarehouseIDFrom || '');
                            this.warehouseTo = data.warehouse_to ? data.warehouse_to.WarehouseName : (data.WarehouseIDTo || '');
                            
                            // Initialize details
                            this.details = [
                                @foreach($transfer->details as $dt)
                                {
                                    sequence: '{{ $dt->Sequence }}',
                                    part: '{{ $dt->PartID }}',
                                    part_name: '{{ $dt->part->PartName ?? $dt->PartID }}',
                                    unit: '{{ $dt->UnitID }}',
                                    unit_name: '{{ $dt->unit->UnitName ?? $dt->UnitID }}',
                                    remaining_qty: (function() {
                                        let reqDt = data.details.find(d => d.Sequence == '{{ $dt->Sequence }}');
                                        return reqDt ? reqDt.RemainingQty + {{ $dt->Qty }} : {{ $dt->Qty }};
                                    })(),
                                    qty: {{ $dt->Qty }},
                                    dimension: '{{ $dt->Dimension }}',
                                    cartoon: '{{ $dt->CartoonNo }}',
                                    conversion: {{ $dt->Conversion ?? 1 }}
                                },
                                @endforeach
                            ];
                        })
                        .catch(error => {
                            console.error(error);
                        });
                }
            },
            mounted() {
                const self = this;
                this.fetchRequestDetails();

                // Initialize Staff In Charge AJAX Select2
                $('#StaffInChargeFrom').select2({
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
                    self.staffInCharge = $(this).val();
                });

                $('.js-flatpickr').flatpickr({
                    dateFormat: "d/m/Y",
                });
            }
        });
    </script>
@endsection
