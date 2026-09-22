@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Stock Monitoring - {{ $id }}</title>
@endsection

@section('content')
    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">
                <div class="d-flex flex-row align-items-center mb-5">
                    <a href="{{ route('monitor') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                    <h1 class="fs-3 fw-bold ms-4 mb-0">
                        {{ $id }} Histories
                    </h1>
                </div>


                <form autocomplete="off" id="filter-form">
                    <div class="d-flex flex-row align-items-end flex-wrap">
                        <input type="hidden" name="id" value="{{ $id }}">
                        <input type="hidden" name="warehouse" value="{{ $warehouse }}">

                        <div class="me-3 mb-2">
                            <label class="form-label">Period</label>
                            <input type="text" class="js-flatpickr form-control" id="date" name="date"
                                value="{{ request()->get('date' ?? '') }}">
                        </div>

                        <div class="mb-2">
                            <button type="submit" class="btn btn-secondary"><i class="fa fa-fw fa-filter"></i>
                                Filter</button>
                        </div>
                    </div>
                </form>

                <div class="mt-3">
                    <div class="alert alert-info">
                        <strong>Total In:</strong> {{ number_format($total_in, 0, ',', '.') }} &nbsp; |
                        <strong>Total Out:</strong> {{ number_format($total_out, 0, ',', '.') }} &nbsp; |
                        <strong>Final Stock:</strong> {{ number_format($total_balance, 0, ',', '.') }}
                    </div>
                </div>

                <div class="block block-rounded">
                    <div class="block-content">
                        <div class="table-responsive w-100">
                            <table class="table table-bordered table-striped table-vcenter table-fixed history-table">
                                <thead>
                                    <tr>
                                        <th class="text-center">Date</th>
                                        <th class="text-center">Transaction Type</th>
                                        <th class="text-center">Transaction No</th>
                                        <th class="text-center">Warehouse ID</th>
                                        <th class="text-center">Notes</th>
                                        <th class="text-center">Initial Stock</th>
                                        <th class="text-center">Qty Change</th>
                                        <th class="text-center">Resulting Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data as $history)
                                        <tr>
                                            <td class="text-center">{{ $history->date }}</td>
                                            <td class="text-center">{{ $history->type }}</td>
                                            <td class="text-center">{{ $history->invoice }}</td>
                                            <td class="text-center">{{ $history->warehouse }}</td>
                                            <td class="text-center">
                                                @if ($history->is_anomali)
                                                    @if ($history->notes == 'Surplus')
                                                        <span class="badge bg-success">Surplus</span>
                                                    @elseif ($history->notes == 'Shortage')
                                                        <span class="badge bg-danger">Shortage</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark">{{ $history->notes }}</span>
                                                    @endif
                                                @else
                                                    {{ $history->notes }}
                                                @endif
                                            </td>
                                            <td class="text-center">{{ number_format($history->initial, 0, ',', '.') }}
                                            </td>
                                            <td
                                                class="text-center fw-bold {{ $history->qty < 0 ? 'text-danger' : 'text-success' }}">
                                                {{ $history->qty > 0 ? '+' : '' }}{{ number_format($history->qty, 0, ',', '.') }}
                                            </td>
                                            <td class="text-center">{{ number_format($history->final, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


            </div>
        </div>

        @include('stock.card.warehouse_modal')
    </div>
    <!-- END Hero -->
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-buttons-bs5/buttons.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/sweetalert2/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">

    <style>
        .history-table {
            overflow-x: hidden;
            overflow-y: auto;
            max-height: calc(100vh - 500px);
        }

        .history-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
        }
    </style>
@endsection

@section('scripts')
    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>

    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('js/moment.min.js') }}"></script>
    <script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.js') }}"></script>
    <script src="{{ asset('js/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('js/plugins/flatpickr/flatpickr.min.js') }}"></script>

    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
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

                selectedPart: '',
                warehouse: '{{ request()->get('warehouse') ?? '' }}',
                details: [],

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
            methods: {

            },
            computed: {

            },
            watch: {

            },
            mounted() {
                let app = this;
            }
        });

        $("#date").flatpickr({
            mode: "range",
            dateFormat: "d/m/Y",
        });



        @if (session()->has('type'))
            One.helpers('jq-notify', {
                type: '{{ session('type') }}',
                icon: '{{ session('icon') }}',
                message: '{{ session('message') }}',
            });
        @endif
    </script>
@endsection
