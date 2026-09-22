@extends('layouts.admin')

@section('titles')
    <title>Keyonline - Add Stock Report</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <form autocomplete="off" method="post" action="{{ route('stock_report.store') }}" id="stock-report-form">
            @csrf

            <div class="d-flex flex-row align-items-center mb-5">
                <a href="{{ route('stock_report') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                <h1 class="h3 fw-bold ms-4 mb-0">
                    Add Stock Report
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
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="text" class="js-flatpickr form-control" id="date" name="date"
                                        placeholder="d/m/Y" value="{{ old('date') }}" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-12 pe-lg-5">
                            <div class="mb-3">
                                <label class="form-label">Part <span class="text-danger">*</span></label>
                                <select2 url="{{ route('misc.part', ['select2' => true]) }}" v-model="part"
                                    :prevalue="part" class="form-select" name="PartID" id="PartID" required>
                                    <option value="">- Select Part -</option>
                                </select2>
                            </div>
                        </div>
                        <div class="col-lg-6 col-12">
                            <div class="mb-3">
                                <label class="form-label">Unit <span class="text-danger">*</span></label>
                                <select2 :url="unitUrl" v-model="unit" :prevalue="unit" class="form-select"
                                    name="UnitID" id="UnitID" required :disabled="!part">
                                    <option value="">- Select Unit -</option>
                                </select2>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-12 pe-lg-5">
                            <div class="mb-3">
                                <label class="form-label">Warehouse <span class="text-danger">*</span></label>
                                <select2 url="{{ route('misc.warehouse2', ['select2' => true]) }}" v-model="warehouse"
                                    :prevalue="warehouse" class="form-select" name="WarehouseID" id="WarehouseID" required>
                                    <option value="">- Select Warehouse -</option>
                                </select2>
                            </div>
                        </div>
                        <div class="col-lg-6 col-12">
                            <div class="mb-3">
                                <label class="form-label">Qty <span class="text-danger">*</span></label>
                                <vue-autonumeric :options="autonumericFormat" name="Qty" class="form-control"
                                    v-model="qty" required></vue-autonumeric>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-12 pe-lg-5">
                            <div class="mb-3">
                                <label class="form-label">Conversion <span class="text-danger">*</span></label>
                                <vue-autonumeric :options="autonumericFormat2" name="Conversion" class="form-control"
                                    v-model="conversion" readonly></vue-autonumeric>
                                <small class="text-muted">Conversion rate from selected unit to base unit (auto
                                    fetched)</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes <span class="text-danger">*</span></label>
                                <input type="text" name="Notes"
                                    value="{{ old('Notes') ?? '' }}"class="form-control" required>
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
                part: '{{ old('PartID') ?? '' }}',
                unit: '{{ old('UnitID') ?? '' }}',
                warehouse: '{{ old('WarehouseID') ?? '' }}',
                qty: {{ old('Qty') ?? 0 }},
                conversion: {{ old('Conversion') ?? 1 }},

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
            computed: {
                unitUrl: function() {
                    if (this.part) {
                        return '{{ route('misc.partunit2') }}?id=' + this.part;
                    }
                    return '';
                }
            },
            watch: {
                part: function() {
                    this.unit = '';
                    this.conversion = 1;
                },
                unit: function(newUnit) {
                    if (this.part && newUnit) {
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
                            part_id: app.part,
                            unit_id: app.unit
                        },
                        success: function(response) {
                            app.conversion = response.conversion || 1;
                        },
                        error: function() {
                            app.conversion = 1;
                        }
                    });
                }
            },
            mounted() {
                // Initialize flatpickr
                $('.js-flatpickr').flatpickr({
                    dateFormat: "d/m/Y",
                    defaultDate: "today"
                });
            }
        });
    </script>
@endsection
