@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Add Advanced Stock Opname</title>
@endsection

@php
    $details = old('DetailsJson') ? json_decode(old('DetailsJson'), true) : $details;
    if (old('employee')) {
        $checkers = [];
        foreach (old('employee') as $i => $employee) {
            $checkers[] = [
                'id' => generateRandomString(10),
                'employee' => $employee,
                'status' => old('status.' . $i),
            ];
        }
    }
@endphp

@section('content')
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <form autocomplete="off" method="post" action="{{ route('opname.store') }}" id="opname-form">
            @csrf
            <input type="hidden" name="DetailsJson" :value="JSON.stringify(details)">

            <div class="d-flex flex-row align-items-center mb-5">
                <a href="{{ route('opname') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                <h1 class="h3 fw-bold ms-4 mb-0">
                    Add Stock Opname
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
                    <button type="submit" class="btn btn-primary mb-3 fs-6"><i class="fa fa-fw fa-save me-2"></i>Save</button>

                    <div class="row">
                        <div class="col-lg-6 col-12 pe-lg-5">
                            <div class="row align-items-center mb-3">
                                <div class="col-lg-5 col-12 mb-lg-0 mb-3">
                                    <label class="form-label">Transaction No <span class="text-danger">*</span></label>
                                    <input type="text" name="TransactionNo" id="TransactionNo" class="form-control" value="{{ old('TransactionNo') ?? '' }}" required {{ old('automatic') ? 'disabled' : (count($errors->all()) > 0 ? '' : 'disabled') }}>
                                </div>
                                <div class="col-lg-auto col-12 mb-lg-0 mb-3">
                                    <label class="form-label"></label>
                                    <div class="form-check pt-lg-2">
                                        <input class="form-check-input fs-6" type="checkbox" value="1" name="automatic" id="automatic" {{ old('automatic') ? 'checked' : (count($errors->all()) > 0 ? '' : 'checked') }}>
                                        <label class="form-check-label fs-6" for="automatic">Automatic</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Warehouse <span class="text-danger">*</span></label>
                                <select2 url="{{ route('misc.warehouse2', ['opname' => true]) }}" v-model="warehouse" :prevalue="warehouse" class="form-select"
                                    name="WarehouseID" id="WarehouseID" required :disabled="isLoading">
                                    <option value="">-</option>
                                </select2>
                            </div>
                        </div>

                        <div class="col-lg-6 col-12 ps-lg-5">
                            <div class="mb-3">
                                <label class="form-label">Transaction Date <span class="text-danger">*</span></label>
                                <input type="text" class="js-flatpickr form-control" id="TransactionDate" name="TransactionDate" placeholder="d/m/Y" value="{{ old('TransactionDate') ?? '' }}" readonly required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="Notes" class="form-control" rows="3">{{ old('Notes') ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block block-rounded">
                <div class="block-content">
                    <div class="d-flex flex-row justify-content-between align-items-center mb-3">
                        <h5 class="mb-3">Stock</h5>
                        <p class="mb-3" v-if="isLoading"><i class="fa fa-fw fa-spin fa-circle-notch"></i></p>
                    </div>

                    <div class="table-responsive w-100">
                        <table class="table table-bordered table-vcenter nowrap w-100">
                            <thead>
                                <tr>
                                    <th style="min-width: 280px;">Part</th>
                                    <th style="width: 110px;">Unit</th>
                                    <th style="width: 150px;">Qty Stock</th>
                                    <th style="width: 150px;">Qty Opname</th>
                                    <th style="width: 150px;">Difference</th>
                                    <th style="width: 160px;">Batch No</th>
                                    <th style="width: 160px;">Coil No</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="details.length == 0 && !isLoading">
                                    <td colspan="10" class="text-center p-3">Pick Warehouse to see stock</td>
                                </tr>
                                <tr v-for="detail in details" :key="detail.id">
                                    <td><input type="text" class="form-control" :value="detail.part_name" readonly></td>
                                    <td><input type="text" class="form-control" v-model="detail.unit" readonly></td>
                                    <td><vue-autonumeric :options="autonumericFormat2" class="form-control" v-model="detail.stock" readonly></vue-autonumeric></td>
                                    <td><vue-autonumeric :options="autonumericFormat2" class="form-control" v-model="detail.opname" readonly></vue-autonumeric></td>
                                    <td><vue-autonumeric :options="autonumericFormat" class="form-control" :value="Number(detail.opname || 0) - Number(detail.stock || 0)" readonly></vue-autonumeric></td>
                                    <td><input type="text" class="form-control" :value="displayStockValue(detail.batch_no)" readonly></td>
                                    <td><input type="text" class="form-control" :value="displayDash(detail.coil_no)" readonly></td>
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
                        <table class="table table-bordered table-vcenter nowrap w-100">
                            <thead>
                                <tr>
                                    <th style="width: 65px"></th>
                                    <th>Employee</th>
                                    <th style="width: 220px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="checkers.length == 0">
                                    <td colspan="3" class="text-center p-3">No Checker added Yet</td>
                                </tr>
                                <tr v-for="checker in checkers" :key="checker.id">
                                    <td>
                                        <button type="button" class="btn btn-danger" @click="deleteChecker(checker)"><i class="fa fa-fw fa-trash-can"></i></button>
                                    </td>
                                    <td>
                                        <select2 url="{{ route('misc.employee', ['select2' => true]) }}" v-model="checker.employee" :prevalue="checker.employee" class="form-select" name="employee[]" placeholder="Pick Employee" required>
                                            <option>-</option>
                                        </select2>
                                    </td>
                                    <td>
                                        <select class="form-select" name="status[]" v-model="checker.status">
                                            <option value="SUPERVISOR">Supervisor</option>
                                            <option value="STAFF">Staff</option>
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/flatpickr/flatpickr.min.css') }}">
    <style>
        th { white-space: nowrap; }
    </style>
@endsection

@section('scripts')
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
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

    <script>
        let app = new Vue({
            el: '#vue-container',
            data: {
                isLoading: false,
                isRestoring: false,
                warehouse: @json(old('WarehouseID') ?? ''),
                details: @json($details ?: []),
                checkers: @json($checkers ?: []),
                autonumericFormat: {
                    minimumValue: '-9999999999999',
                    maximumValue: '9999999999999',
                    decimalPlaces: 6,
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
                getStock() {
                    this.isLoading = true;
                    $.ajax({
                        url: '{{ route('opname.stock') }}',
                        type: 'GET',
                        data: { id: this.warehouse }
                    }).then(result => {
                        this.details = result.stock || [];
                    }).always(() => {
                        this.isLoading = false;
                    });
                },
                addChecker() {
                    this.checkers.push({
                        id: makeid(10),
                        employee: '',
                        status: 'SUPERVISOR'
                    });
                },
                deleteChecker(item) {
                    this.checkers = this.checkers.filter(x => x !== item);
                },
                displayStockValue(value) {
                    return value === null || value === undefined || value === '' ? '(Empty)' : value;
                },
                displayDash(value) {
                    return value === null || value === undefined || value === '' ? '-' : value;
                },
            },
            watch: {
                warehouse() {
                    if (this.warehouse != '' && !this.isRestoring) {
                        this.getStock();
                    }
                }
            },
            mounted() {
                this.isRestoring = true;
                FormPreserver.initVue(this, 'stock_opname_advanced_add', ['isRestoring', 'isLoading', 'autonumericFormat', 'autonumericFormat2']);
                this.$nextTick(() => {
                    this.isRestoring = false;
                    if (this.warehouse && this.details.length === 0) {
                        this.getStock();
                    }
                });
            }
        });

        $('#automatic').on('change', function() {
            if ($('#automatic').is(':checked')) {
                $('#TransactionNo').attr('disabled', true);
            } else {
                $('#TransactionNo').removeAttr('disabled');
            }
        }).trigger('change');

        $('.js-flatpickr').flatpickr({
            dateFormat: 'd/m/Y',
            defaultDate: 'today'
        });
    </script>
@endsection
