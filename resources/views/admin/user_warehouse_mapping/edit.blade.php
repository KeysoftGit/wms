@extends('layouts.admin')

@section('titles')
    <title>Keyonline - Edit User Warehouse Mapping</title>
@endsection

@section('content')
    <div class="px-lg-5 py-lg-3 p-3">
        <form method="post" action="{{ route('user_warehouse_mapping.update', $mapping->UserID) }}">
            @csrf
            @method('PUT')

            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('user_warehouse_mapping.index') }}" class="h3 text-dark mb-0">
                    <i class="fa fa-fw fa-arrow-left"></i>
                </a>
                <h1 class="h3 fw-bold ms-4 mb-0">Edit User Warehouse Mapping</h1>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="block block-rounded">
                <div class="block-content pb-4">
                    <button class="btn btn-primary mb-4" type="submit">
                        <i class="fa fa-fw fa-save me-1"></i> Save Changes
                    </button>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">User</label>
                                <input class="form-control" value="{{ $mapping->UserID }}" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Effective Date</label>
                                <input class="form-control"
                                    value="{{ $mapping->EffectiveDate->format('d/m/Y H:i') }}" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Warehouses <span class="text-danger">*</span></label>
                                <select id="WarehouseID" class="form-select" name="WarehouseID[]"
                                    data-placeholder="Select Warehouses" multiple required>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{ $warehouse->WarehouseID }}"
                                            @selected(in_array($warehouse->WarehouseID, old('WarehouseID', $selectedWarehouseIds)))>
                                            {{ $warehouse->WarehouseID }}{{ $warehouse->WarehouseName ? ' - ' . $warehouse->WarehouseName : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="Notes" rows="3">{{ old('Notes', $mapping->Notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <style>
        .select2-container--bootstrap-5 .select2-selection--multiple {
            min-height: 38px;
            font-size: .9rem;
        }
        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
            font-size: .8rem;
        }
        .select2-container--bootstrap-5 .select2-search__field,
        .select2-container--bootstrap-5 .select2-results__option {
            font-size: .9rem;
        }
    </style>
@endsection

@section('scripts')
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(function () {
            $('#WarehouseID').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select Warehouses',
                closeOnSelect: false
            });
        });
    </script>
@endsection
