@extends('layouts.admin')

@section('titles')
    <title>Keyonline - Edit Stock Report</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <form autocomplete="off" method="post" action="{{ route('stock_report.update', $report->id) }}" id="stock-report-form">
            @csrf

            <div class="d-flex flex-row align-items-center mb-5">
                <a href="{{ route('stock_report') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                <h1 class="h3 fw-bold ms-4 mb-0">
                    Edit Stock Report
                </h1>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <p class="m-0 fs-6">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="block block-rounded">
                <div class="block-content pb-3">
                    <button type="submit" class="btn btn-primary mb-3 fs-6"><i
                            class="fa fa-fw fa-save me-2"></i>Save</button>

                    <div class="row">
                        <div class="col-lg-6 col-12">
                            <div class="row align-items-center mb-3">
                                <div class="col-lg-4 col-12 mb-lg-0 mb-3">
                                    <label class="form-label">ID</label>
                                    <input type="text" class="form-control" value="{{ $report->id }}" readonly
                                        disabled>
                                </div>
                                <div class="col-lg-4 col-12 mb-lg-0 mb-3">
                                    <label class="form-label">Date</label>
                                    <input type="text" class="form-control"
                                        value="{{ \Carbon\Carbon::parse($report->Date)->format('d/m/Y') }}" readonly
                                        disabled>
                                </div>
                                <div class="col-lg-4 col-12">
                                    <label class="form-label">Part</label>
                                    <input type="text" class="form-control"
                                        value="{{ $report->PartID }} - {{ $report->part->PartName ?? '' }}" readonly
                                        disabled>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-12 pe-lg-5">
                            <div class="mb-3">
                                <label class="form-label">Unit <span class="text-danger">*</span></label>
                                <select2 url="{{ route('misc.partunit2') }}?id={{ $report->PartID }}" v-model="UnitID"
                                    :prevalue="UnitID" class="form-select" name="UnitID" id="UnitID" required>
                                    <option value="">- Select Unit -</option>
                                </select2>
                                <small class="text-muted">Current unit: {{ $report->UnitID }}</small>
                            </div>
                        </div>
                        <div class="col-lg-6 col-12">
                            <div class="mb-3">
                                <label class="form-label">Warehouse <span class="text-danger">*</span></label>
                                <select2 url="{{ route('misc.warehouse2', ['select2' => true]) }}" v-model="WarehouseID"
                                    :prevalue="WarehouseID" class="form-select" name="WarehouseID" id="WarehouseID"
                                    required>
                                    <option value="">- Select Warehouse -</option>
                                </select2>
                                <small class="text-muted">Current warehouse: {{ $report->WarehouseID }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-12 pe-lg-5">
                            <div class="mb-3">
                                <label class="form-label">Qty <span class="text-danger">*</span></label>
                                <vue-autonumeric :options="autonumericFormat" name="Qty" class="form-control"
                                    v-model="Qty" required></vue-autonumeric>
                            </div>
                        </div>
                        <div class="col-lg-6 col-12">
                            <div class="mb-3">
                                <label class="form-label">Conversion <span class="text-danger">*</span></label>
                                <vue-autonumeric :options="autonumericFormat2" name="Conversion" class="form-control"
                                    v-model="Conversion" readonly></vue-autonumeric>
                                <small class="text-muted">Conversion rate from selected unit to base unit (auto
                                    fetched)</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes <span class="text-danger">*</span></label>
                                <input type="text" name="Notes"
                                    value="{{ old('Notes') ?? $report->Notes }}"class="form-control"
                                    required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-12">
                            <div class="mb-3">
                                <label class="form-label">Created By</label>
                                <input type="text" class="form-control" value="{{ $report->CreatedBy }}" readonly
                                    disabled>
                            </div>
                        </div>
                        <div class="col-lg-6 col-12">
                            <div class="mb-3">
                                <label class="form-label">Created At</label>
                                <input type="text" class="form-control"
                                    value="{{ \Carbon\Carbon::parse($report->created_at)->format('d M Y H:i') }}" readonly
                                    disabled>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </form>
    </div>
    <!-- END Hero -->

@endsection

@section('styles')
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/flatpickr/flatpickr.min.css') }}">
@endsection

@section('scripts')
    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>

    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.js') }}"></script>
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
                partId: '{{ $report->PartID }}',
                UnitID: '{{ old('UnitID', $report->UnitID) }}',
                WarehouseID: '{{ old('WarehouseID', $report->WarehouseID) }}',
                Qty: {{ old('Qty', $report->Qty) }},
                Conversion: {{ old('Conversion', $report->Conversion) }},

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
                    decimalPlaces: 6,
                    digitGroupSeparator: '.',
                    decimalCharacter: ',',
                    modifyValueOnWheel: false,
                    allowDecimalPadding: false,
                    unformatOnSubmit: true
                },
            },
            watch: {
                UnitID: function(newUnit) {
                    if (this.partId && newUnit) {
                        this.fetchConversion();
                    }
                }
            },
            methods: {
                fetchConversion: function() {
                    let app = this;
                    $.ajax({
                        url: '{{ route('misc.conversion') }}',
                        type: 'GET',
                        data: {
                            part_id: app.partId,
                            unit_id: app.UnitID
                        },
                        success: function(response) {
                            app.Conversion = response.conversion || 1;
                        },
                        error: function() {
                            app.Conversion = 1;
                        }
                    });
                }
            },
            mounted() {
                // Initialize any additional functionality
            }
        });
    </script>
@endsection
