@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Import Master</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2">
            <div class="flex-grow-1 mb-1 mb-md-0">

                <h1 class="h3 fw-bold mb-5">
                    Import Master
                </h1>

                <div class="block block-rounded">
                    <div class="block-content">
                        <div class="d-flex flex-row mb-4">
                            <a class="btn btn-primary me-2" href="{{ asset('template/import_template.xlsx') }}"><i class="fa fa-fw fa-download me-2"></i>Download Template</a>
                            <a class="btn btn-info" href="{{ route('import.instruction') }}" target="_blank"><i class="fa fa-fw fa-circle-info me-2"></i>Instruction</a>
                        </div>

                        <h3 class="fw-bold">Notes</h3>

                        <ul class="ps-4 fs-6">
                            <li>Don't modify/delete the worksheets or columns!</li>
                            <li>Make sure the value or data type in each column match the intended data!</li>
                            <li>If the data reference another data (Eg. Customer data references Salesman by EmployeeID), make sure the referenced data already exists (added/imported)!</li>
                            <li>IDs are <b>case sensitive</b>! Make sure you inputted the right value!</li>
                            <li>If your data ID can be <b>Auto-Generated</b> in the app, don't put the same format as the <b>Auto-Generated</b> ID as it will break the auto-increment sequence!</li>
                        </ul>
                    </div>
                </div>

                <div class="block block-rounded">
                    <div class="block-content">
                        <form autocomplete="off" method="post" id="import-form" enctype="multipart/form-data">
                            <div class="row mb-3">
                                <div class="col-lg-3 col-12 mb-3 mb-lg-0">
                                    <label class="form-label">Sheet to Import</label>
                                    <select class="form-select" name="sheet">
                                        <option value="Country">Country</option>
                                        <option value="Currency">Currency</option>
                                        <option value="Vehicle">Vehicle</option>
                                        <option value="Division">Division</option>
                                        <option value="Employee">Employee</option>
                                        <option value="Supplier">Supplier</option>
                                        <option value="Customer">Customer</option>
                                        <option value="COA">COA</option>
                                        <option value="FACategory">FACategory</option>
                                        <option value="FALocation">FALocation</option>
                                        <option value="Warehouse">Warehouse</option>
                                        <option value="Unit">Unit</option>
                                        <option value="PartCategory">PartCategory</option>
                                        <option value="PartSpecification">PartSpecification</option>
                                        <option value="PartVariant">PartVariant</option>
                                        <option value="InventoryType">InventoryType</option>
                                        <option value="Part">Part</option>
                                        <option value="BeginningStock">BeginningStock</option>
                                        <option value="Hutang">Hutang</option>
                                        <option value="Piutang">Piutang</option>
                                    </select>
                                </div>
                                <div class="col-lg-8 col-12 mb-3 mb-lg-0">
                                    <label class="form-label">File</label>
                                    <input type="file" class="form-control" accept=".xls, .xlsx" name="excel" id="excel" required>
                                </div>
                                <div class="col-lg-1 col-12 d-flex flex-column">
                                    <button class="btn btn-primary mt-auto">Upload</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>

        @include('import.modal')
        @include('import.error')
    </div>
    <!-- END Hero -->


@endsection

@section('styles')
    <style>
        input[readonly]
        {
            background:white !important;
        }
    </style>
@endsection

@section('scripts')
    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.13/dist/vue.js"></script>
    <script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.js') }}"></script>

    <!-- Page JS Code -->
    <script>
        var check = null;
        var id = "";

        function checkStatus(){
            $.ajax({
                url: '{{ route('import.check') }}',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                data: {
                    id: id,
                }
            }).done(function (result)  {
                if(result.status.trim() != 'importing'){
                    $('#import-message').html('Validating Sheet...');
                    $('#loading-modal').modal('hide');
                    clearInterval(check);
                    if (result.status.trim() == 'done'){
                        One.helpers('jq-notify', {
                            type: 'success',
                            icon: 'fa fa-fw fa-circle-check',
                            message: 'Import is successful!',
                        });
                    }
                    else {
                        One.helpers('jq-notify', {
                            type: 'danger',
                            icon: 'fa fa-times me-1',
                            message: 'Import failed!',
                        });
                    }
                }
            });
        }

        let app = new Vue({
            el: '#vue-container',
            data() {
                return {
                    errors: [],
                }
            },
            methods: {
                showErrorModal(errorData) {
                    // Convert errors to proper format
                    if (typeof errorData === 'string') {
                        this.errors = [errorData];
                    }
                    else if (Array.isArray(errorData)) {
                        this.errors = errorData.map(error => {
                            if (typeof error === 'string') {
                                return error;
                            }
                            else if (Array.isArray(error) && error.length > 0) {
                                return error[0];
                            }
                            else if (typeof error === 'object') {
                                return JSON.stringify(error);
                            }
                            return String(error);
                        });
                    }
                    else if (typeof errorData === 'object') {
                        // Handle Laravel validation error format
                        const errorArray = [];
                        for (const key in errorData) {
                            if (Array.isArray(errorData[key])) {
                                errorData[key].forEach(err => {
                                    errorArray.push(err);
                                });
                            }
                            else {
                                errorArray.push(errorData[key]);
                            }
                        }
                        this.errors = errorArray;
                    }
                    else {
                        this.errors = ['An unknown error occurred'];
                    }

                    // Show error modal
                    this.$nextTick(() => {
                        $('#error-modal').modal('show');
                    });
                }
            },
            mounted() {
                let app = this;

                $('#import-form').on('submit', function (e){
                    e.preventDefault();

                    var data = new FormData(this);

                    $('#loading-modal').modal('show');

                    $.ajax({
                        url: '{{ route('import.upload') }}',
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        data: data,
                        cache: false,
                        contentType: false,
                        processData: false,
                    }).done(function (result)  {
                        console.log('Response from server:', result);

                        if (result.success){
                            $('#import-message').html('Importing Rows...');
                            id = result.id;
                            check = window.setInterval(function (){
                                checkStatus()
                            }, 3000);
                        }
                        else {
                            console.log('Error data:', result.errors);
                            $('#loading-modal').modal('hide');
                            app.showErrorModal(result.errors);
                        }
                    }).fail(function (xhr) {
                        console.error('AJAX request failed:', xhr);
                        $('#loading-modal').modal('hide');

                        let errorMessage = 'Network error or server unavailable.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            app.showErrorModal(xhr.responseJSON.errors);
                        }
                        else if (xhr.responseJSON && xhr.responseJSON.message) {
                            app.showErrorModal(xhr.responseJSON.message);
                        }
                        else if (xhr.status === 404) {
                            app.showErrorModal('Route not found. Please check if the import route is properly configured.');
                        }
                        else if (xhr.status === 405) {
                            app.showErrorModal('Method not allowed. The route does not accept POST method.');
                        }
                        else {
                            app.showErrorModal(errorMessage);
                        }
                    }).always(function (){
                        $('#excel').val(null);
                    });
                });
            }
        });
    </script>
@endsection
