@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Edit Advanced Stock Opname</title>
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
        <form autocomplete="off" method="post" enctype="multipart/form-data" action="{{ route('opname.update') }}" id="opname-form">
            @csrf
            <input type="hidden" name="id" value="{{ $opname->TransactionNo }}">
            <input type="hidden" name="Status" :value="opStatus">
            <input type="hidden" name="DetailsJson" :value="JSON.stringify(details)">

            <div class="d-flex flex-row align-items-center mb-5">
                <a href="{{ route('opname') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                <h1 class="h3 fw-bold ms-4 mb-0">
                    Edit Stock Opname
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
                                <div class="col-lg-6 col-12 mb-lg-0 mb-3">
                                    <label class="form-label">Transaction No</label>
                                    <input type="text" class="form-control" value="{{ $opname->TransactionNo }}" readonly>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <label class="form-label">Transaction Date <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="TransactionDate" value="{{ old('TransactionDate') ?? date('d/m/Y', strtotime($opname->TransactionDate)) }}" readonly required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Warehouse</label>
                                <input type="hidden" name="WarehouseID" value="{{ $opname->WarehouseID }}">
                                <input type="text" class="form-control" value="{{ $opname->warehouse->WarehouseName ?? $opname->WarehouseID }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <input type="text" class="form-control" v-model="opStatus" readonly>
                            </div>
                        </div>

                        <div class="col-lg-6 col-12 ps-lg-5">
                            <div class="mb-3">
                                <label class="form-label">Reason For Delays</label>
                                <textarea name="ReasonsForDelays" class="form-control" rows="2">{{ old('ReasonsForDelays') ?? $opname->ReasonsForDelays }}</textarea>
                            </div>
                            @if ($opname->Status == 'PENDING')
                                <div class="row mb-3">
                                    <div class="col">
                                        <input type="file" name="excel" id="excel" class="form-control" accept=".xlsx,.xls" :disabled="isImporting">
                                    </div>
                                    <div class="col-auto px-0">
                                        <button type="button" class="btn btn-success" v-if="!isImporting" @click="uploadExcel">
                                            <i class="fa fa-fw fa-file-excel me-2"></i>Import Excel
                                        </button>
                                        <button type="button" class="btn btn-success" disabled v-if="isImporting">
                                            <i class="fa fa-fw fa-spin fa-circle-notch me-2"></i>Importing
                                        </button>
                                    </div>
                                    <div class="col-auto">
                                        <a class="btn btn-primary" href="{{ route('opname.template', ['id' => $opname->id]) }}">
                                            <i class="fa fa-fw fa-download me-2"></i>Advanced Template
                                        </a>
                                    </div>
                                </div>
                                <p class="m-0 fs-xs text-info">Download the template, fill QtyOpname and Closed, then import it back.</p>
                            @endif
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="Notes" class="form-control" rows="3">{{ old('Notes') ?? $opname->Notes }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block block-rounded">
                <div class="block-content">
                    <div class="d-flex flex-row justify-content-between align-items-center mb-3">
                        <h5 class="mb-3">Stock</h5>
                        <button type="button" class="btn btn-secondary" @click="checkAll"><i class="fa fa-fw fa-check-double me-2"></i>Check All</button>
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
                                    <th style="width: 100px;">Closed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="details.length == 0">
                                    <td colspan="11" class="text-center p-3">No data available.</td>
                                </tr>
                                <tr v-for="(detail, index) in details" :key="detail.id">
                                    <td><input type="text" class="form-control" :value="detail.part_name" readonly></td>
                                    <td><input type="text" class="form-control" v-model="detail.unit" readonly></td>
                                    <td><vue-autonumeric :options="autonumericFormat2" class="form-control" v-model="detail.stock" readonly></vue-autonumeric></td>
                                    <td><vue-autonumeric :options="autonumericFormat2" class="form-control" v-model="detail.opname" readonly></vue-autonumeric></td>
                                    <td><vue-autonumeric :options="autonumericFormat" class="form-control" :value="Number(detail.opname || 0) - Number(detail.stock || 0)" readonly></vue-autonumeric></td>
                                    <td><input type="text" class="form-control" :value="displayStockValue(detail.batch_no)" readonly></td>
                                    <td><input type="text" class="form-control" :value="displayDash(detail.coil_no)" readonly></td>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input fs-4" v-model="detail.closed">
                                    </td>
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
                                            <option v-if="checker.employee && checker.employee_text" :value="checker.employee" selected>@{{ checker.employee_text }}</option>
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

        @include('stock.opname.modal')
        @include('stock.opname.error')
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <style>
        th { white-space: nowrap; }
    </style>
@endsection

@section('scripts')
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.js') }}"></script>
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
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
                isImporting: false,
                opStatus: '{{ old('Status') ?? $opname->Status }}',
                details: @json($details ?: []),
                checkers: @json($checkers ?: []),
                errors: [],
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
                checkAll() {
                    const closeAll = this.details.some(detail => !detail.closed);
                    this.details.forEach(detail => detail.closed = closeAll);
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
                uploadExcel() {
                    let app = this;
                    let data = new FormData($('#opname-form')[0]);

                    if ($('#excel')[0].files.length > 0) {
                        this.isImporting = true;
                        $('#loading-modal').modal('show');

                        $.ajax({
                            url: '{{ route('opname.import') }}',
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            data: data,
                            cache: false,
                            contentType: false,
                            processData: false,
                        }).done(function (result) {
                            if (result.success) {
                                app.opStatus = 'ADJUSTED';
                                app.details = result.details;
                                $('#loading-modal').modal('hide');
                            } else {
                                app.errors = result.errors;
                                $('#loading-modal').modal('hide');
                                $('#error-modal').modal('show');
                            }
                        }).always(function () {
                            $('#excel').val(null);
                            app.isImporting = false;
                        });
                    } else {
                        One.helpers('jq-notify', {
                            type: 'warning',
                            icon: '',
                            message: 'Pick a spreadsheet first!',
                        });
                    }
                },
                displayStockValue(value) {
                    return value === null || value === undefined || value === '' ? '(Empty)' : value;
                },
                displayDash(value) {
                    return value === null || value === undefined || value === '' ? '-' : value;
                },
            },
        });
    </script>
@endsection
