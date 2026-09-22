@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Edit Item Transfer Receive</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <form autocomplete="off" method="post" action="{{ route('transfer_receive.update') }}" id="receive-form">
            @csrf

            <div class="d-flex flex-row align-items-center mb-5">
                <a href="{{ route('transfer_receive') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                <h1 class="h3 fw-bold ms-4 mb-0">
                    Edit Item Transfer Receive
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
                                    <input type="text" name="TransactionNo" id="TransactionNo" class="form-control" value="{{ $receive->TransactionNo }}" readonly required>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <label class="form-label">Transaction Date <span class="text-danger">*</span></label>
                                    <input type="text" class="js-flatpickr form-control" id="TransactionDate" name="TransactionDate" placeholder="d/m/Y" value="{{ \Carbon\Carbon::parse($receive->TransactionDate)->format('d/m/Y') }}" readonly="readonly" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-lg-6 col-12">
                                    <label class="form-label">Execute No</label>
                                    <input type="text" class="form-control" name="ExecuteNo" value="{{ $receive->ExecuteNo }}" readonly>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <label class="form-label">Staff In Charge (To) <span class="text-danger">*</span></label>
                                    <select name="StaffInChargeTo" id="StaffInChargeTo" class="form-select" required>
                                        <option value="{{ $receive->StaffInChargeTo }}" selected>{{ $receive->staffInChargeTo->EmployeeName ?? $receive->StaffInChargeTo }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 col-12 ps-lg-5">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label">Warehouse To</label>
                                    <input type="text" class="form-control" value="{{ $receive->warehouseTo->WarehouseName ?? $receive->WarehouseIDTo }}" readonly>
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

    <script>
        let app = new Vue({
            el: '#vue-container',
            data: {
                details: [
                    @foreach($receive->details as $dt)
                    {
                        sequence: '{{ $dt->Sequence }}',
                        part: '{{ $dt->PartID }}',
                        part_name: '{{ $dt->part->PartName ?? $dt->PartID }}',
                        unit: '{{ $dt->UnitID }}',
                        unit_name: '{{ $dt->unit->UnitName ?? $dt->UnitID }}',
                        qty: {{ $dt->Qty }},
                        max_qty: {{ $dt->MaxQty }},
                        conversion: {{ $dt->Conversion ?? 1 }},
                    },
                    @endforeach
                ],
            },
            methods: {
                formatNumber(val) {
                    if (val === undefined || val === null || isNaN(val)) return '0';
                    return new Intl.NumberFormat('id-ID', {
                        maximumFractionDigits: 6
                    }).format(val);
                },
            },
            mounted() {
                const self = this;

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
                    // Update Vue data if needed
                });

                $('.js-flatpickr').flatpickr({
                    dateFormat: "d/m/Y",
                });
            }
        });
    </script>
@endsection
