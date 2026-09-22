@extends('layouts.admin')

@section('titles')
    <title>Keyonline - Item Transfer - {{ $transfer->TransactionNo }}</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <div class="d-flex flex-row align-items-center mb-5">
            <a href="{{ route('transfer') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
            <h1 class="h3 fw-bold ms-4 mb-0">{{ $transfer->TransactionNo }}</h1>
        </div>

        @if (count($errors->all()) > 0)
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <p class="m-0 fs-6">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="row justify-content-between mb-3 align-items-end">
            <div class="col-auto">
                <div class="d-flex flex-row">
                    @if ($implementWms)
                        @if (
                            $transfer->Editable == 1 &&
                                auth()->user()->hasAnyPermission(['admin', 'transfer.edit']))
                            <a href="{{ route('transfer.edit', $transfer->id) }}" class="btn btn-primary fs-6"><i
                                    class="fa fa-fw fa-edit"></i> Edit</a>
                        @endif
                    @endif

                </div>
            </div>

            <div class="col-auto">
                <form autocomplete="off" action="{{ config('app.report_url') }}/print/index">
                    <input type="hidden" name="transactionCode" value="{{ $transfer->TransactionNo }}">
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

        <div class="block block-rounded">
            <div class="block-content pb-3">

                <div class="row">
                    <div class="col-lg-6 col-12">
                        <div class="row align-items-center mb-3">
                            <div class="col-lg-3 col-12 mb-lg-0 mb-3">
                                <label class="form-label">Transaction No</label>
                                <input type="text" name="TransactionNo" id="TransactionNo" class="form-control"
                                    value="{{ $transfer->TransactionNo }}" readonly>
                            </div>
                            <div class="col-lg-3 col-12">
                                <label class="form-label">Transaction Date</label>
                                <input type="text" class="form-control" id="TransactionDate" name="TransactionDate"
                                    placeholder="d/m/Y" value="{{ date('d/m/Y', strtotime($transfer->TransactionDate)) }}"
                                    readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 col-12 pe-lg-5">
                        <div class="position-relative mb-3 pt-2">
                            <p class="position-absolute bg-white px-1 form-label" style="top: 0; left: 10px;">Transfer From
                            </p>
                            <div class="border border-light rounded p-3">
                                <div class="mb-3">
                                    <label class="form-label">Warehouse</label>
                                    <input type="text" class="form-control"
                                        value="{{ $transfer->WarehouseIDFrom . ($transfer->warehouseFrom->WarehouseName ? ' - ' . $transfer->warehouseFrom->WarehouseName : '') }}"
                                        readonly>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Staff In Charge</label>
                                    <input type="text" class="form-control"
                                        value="{{ $transfer->StaffInChargeIDFrom . ($transfer->staffFrom->EmployeeName ? ' - ' . $transfer->staffFrom->EmployeeName : '') }}"
                                        readonly>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-lg-6 col-12 ps-lg-5">
                        <div class="position-relative mb-3 pt-2">
                            <p class="position-absolute bg-white px-1 form-label" style="top: 0; left: 10px;">Transfer To
                            </p>
                            <div class="border border-light rounded p-3">
                                <div class="mb-3">
                                    <label class="form-label">Warehouse</label>
                                    <input type="text" class="form-control"
                                        value="{{ $transfer->WarehouseIDTo . ($transfer->warehouseTo->WarehouseName ? ' - ' . $transfer->warehouseTo->WarehouseName : '') }}"
                                        readonly>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Staff In Charge</label>
                                    <input type="text" class="form-control"
                                        value="{{ $transfer->StaffInChargeIDTo . ($transfer->staffTo->EmployeeName ? ' - ' . $transfer->staffTo->EmployeeName : '') }}"
                                        readonly>
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
                </div>

                <div class="table-responsive w-100">
                    <table class="table table-bordered table-vcenter w-100">
                        <thead>
                            <tr>
                                <th style="width: 180px;">Part ID</th>
                                <th>Part Name</th>
                                <th style="width: 140px;">Qty</th>
                                <th style="width: 140px;">Unit</th>
                                <th style="width: 120px;">Conversion</th>
                                <th style="width: 140px;">Qty Actual</th>
                                <th style="width: 160px;">Batch No</th>
                                <th style="width: 220px;">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="8" class="text-center p-3" v-if="details.length == 0">No Item Added Yet
                                </td>
                            </tr>
                            <tr v-for="(detail, index) in details" :key="detail.id">
                                <td>@{{ detail.part || '-' }}</td>
                                <td>@{{ detail.part_name || detail.partName || '-' }}</td>
                                <td class="text-end">@{{ trimDecimal(detail.qty) }}</td>
                                <td>@{{ detail.unit || '-' }}</td>
                                <td class="text-end">@{{ trimDecimal(detail.conversion) }}</td>
                                <td class="text-end">@{{ trimDecimal(qtyActual(detail)) }}</td>
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
                        <textarea name="Notes" class="form-control" rows="3" readonly>{{ old('Notes') ?? $transfer->Notes }}</textarea>
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
                initiate: false,

                details: @json($details),

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
                trimDecimal(value) {
                    if (value === null || value === undefined || value === '') return '';
                    let stringValue = String(value);
                    if (stringValue.indexOf('.') === -1) return stringValue;
                    return stringValue.replace(/(\.\d*?[1-9])0+$/, '$1').replace(/\.0+$/, '').replace(/\.$/, '');
                },
                toNumber(value) {
                    return Number(String(value || 0).replace(/,/g, '')) || 0;
                },
                qtyActual(detail) {
                    return this.toNumber(detail.qty) * this.toNumber(detail.conversion || 1);
                }
            },
            computed: {

            },
            watch: {

            },
            mounted() {

            }
        });


        $('.form-select2').select2({
            theme: 'bootstrap-5'
        });

        $('.js-flatpickr').flatpickr({
            dateFormat: "d/m/Y",
            defaultDate: "today"
        });
        $('.js-flatpickr:visible').on('focus', function() {
            $(this).blur()
        });
        $('.js-flatpickr:visible').prop('readonly', false);
    </script>
@endsection
