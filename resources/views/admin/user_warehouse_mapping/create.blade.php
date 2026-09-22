@extends('layouts.admin')

@section('titles')
    <title>Keyonline - Add User Warehouse Mapping</title>
@endsection

@section('content')
    <div class="px-lg-5 py-lg-3 p-3">
        <form method="post" action="{{ route('user_warehouse_mapping.store') }}">
            @csrf
            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('user_warehouse_mapping.index') }}" class="h3 text-dark mb-0">
                    <i class="fa fa-fw fa-arrow-left"></i>
                </a>
                <h1 class="h3 fw-bold ms-4 mb-0">Add User Warehouse Mapping</h1>
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
                        <i class="fa fa-fw fa-save me-1"></i> Save New Mapping
                    </button>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">User <span class="text-danger">*</span></label>
                                <select id="UserID" class="form-select form-select2" name="UserID"
                                    data-placeholder="Select User" required>
                                    <option value="">- Select User -</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->UserID }}" @selected(old('UserID') === $user->UserID)>
                                            {{ $user->UserID }}{{ $user->UserName ? ' - ' . $user->UserName : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Effective Date <span class="text-danger">*</span></label>
                                <input class="form-control" type="datetime-local" name="EffectiveDate"
                                    value="{{ old('EffectiveDate', now()->format('Y-m-d\TH:i')) }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Warehouses <span class="text-danger">*</span></label>
                                <select id="WarehouseID" class="form-select form-select2" name="WarehouseID[]"
                                    data-placeholder="Select Warehouses" multiple required>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{ $warehouse->WarehouseID }}"
                                            @selected(in_array($warehouse->WarehouseID, old('WarehouseID', [])))>
                                            {{ $warehouse->WarehouseID }}{{ $warehouse->WarehouseName ? ' - ' . $warehouse->WarehouseName : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Select the complete warehouse access for this new version.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="Notes" rows="3">{{ old('Notes') }}</textarea>
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
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__rendered {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem;
            margin: 0;
            padding: .25rem .5rem;
            font-size: .9rem;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
            margin: 0;
            font-size: .8rem;
            line-height: 1.25rem;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-search {
            margin: 0;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-search__field,
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
            $('#UserID').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select User',
                allowClear: true
            });

            $('#WarehouseID').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select Warehouses',
                closeOnSelect: false
            });
        });
    </script>
@endsection
