@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Edit Inventory Specification</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">

                <form autocomplete="off" method="post" enctype="multipart/form-data"
                    action="{{ route('inventory.specification.update') }}">
                    @csrf

                    <input type="hidden" name="id" value="{{ $specification->SpecificationID }}">

                    <div class="d-flex flex-row align-items-center mb-5">
                        <a href="{{ route('inventory.specification') }}" class="h3 text-dark m-0"><i
                                class="fa fa-fw fa-arrow-left"></i></a>
                        <h1 class="h3 fw-bold ms-4 mb-0">
                            Edit Inventory Specification
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
                            <button type="submit" class="btn btn-primary mb-3 fs-6"><i
                                    class="fa fa-fw fa-save me-2"></i>Save</button>


                            <div class="row">
                                <div class="col-lg-3 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Specification ID <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="SpecificationID" class="form-control"
                                            value="{{ old('SpecificationID') ?? $specification->SpecificationID }}"
                                            readonly>
                                    </div>
                                </div>
                                <div class="col-lg-5 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Specification Name</label>
                                        <input type="text" name="SpecificationName"
                                            value="{{ old('SpecificationName') ?? $specification->SpecificationName }}"class="form-control">
                                    </div>
                                </div>

                                @if ($showBatch)
                                    <div class="col-auto px-lg-0">
                                        <label class="form-label"></label>
                                        <div class="form-check ms-lg-4 pt-lg-2">
                                            <input class="form-check-input fs-6" type="checkbox" value="1"
                                                name="asBatch" id="asBatch"
                                                {{ old('asBatch') ? 'checked' : ($specification->asBatch == 1 ? 'checked' : '') }}>
                                            <label class="form-check-label fs-6" for="asBatch">
                                                As Batch Number
                                            </label>
                                        </div>
                                    </div>
                                @endif

                            </div>

                            <div class="row">
                                <div class="col-lg-8 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea name="Notes" class="form-control" rows="3">{{ old('Notes') ?? $specification->Notes }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <!-- END Hero -->

@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-buttons-bs5/buttons.bootstrap5.min.css') }}">
@endsection

@section('scripts')
    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>

    <!-- Page JS Plugins -->


    <!-- Page JS Code -->
    <script>
        $(function() {

        });
    </script>
@endsection
