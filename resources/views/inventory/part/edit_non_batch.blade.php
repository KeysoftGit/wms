@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Edit Inventory Part</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">

                <form autocomplete="off" method="post" enctype="multipart/form-data"
                    action="{{ route('inventory.part.update_non_batch') }}">
                    @csrf

                    <div class="d-flex flex-row align-items-center mb-5">
                        <a href="{{ route('inventory.part') }}" class="h3 text-dark m-0"><i
                                class="fa fa-fw fa-arrow-left"></i></a>
                        <h1 class="h3 fw-bold ms-4 mb-0">
                            Edit Inventory Part (Non Batch)
                        </h1>
                    </div>

                    <input type="hidden" name="id" value="{{ $part->PartID }}">

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

                            <div class="row align-items-center mb-3">
                                <div class="col-lg-2 col-12">
                                    <label class="form-label">Part ID <span class="text-danger">*</span></label>
                                    <input type="text" name="PartID" id="PartID" class="form-control"
                                        value="{{ old('PartID') ?? $part->PartID }}" required readonly>
                                </div>
                                <div class="col-auto px-lg-0">
                                    <label class="form-label"></label>
                                    <div class="form-check ms-lg-4 pt-lg-2">
                                        <input class="form-check-input fs-6" type="checkbox" value="1" name="Active"
                                            id="Active"
                                            {{ old('Active') ? 'checked' : ($part->Active == 1 ? 'checked' : '') }}>
                                        <label class="form-check-label fs-6" for="Active">
                                            Active
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-6 col-12 pe-lg-5">
                                    <div class="mb-3" id="loading-category">
                                        <label class="form-label">Category <span class="text-danger">*</span></label>
                                        <select class="form-select" disabled>
                                            <option id="loading-text">Loading Categories.....</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="category-container">
                                        <label class="form-label">Category <span class="text-danger">*</span></label>
                                        <select class="form-select form-select2" name="CategoryID" id="CategoryID" required>
                                            <option value="">-</option>
                                        </select>
                                    </div>

                                    <div class="mb-3" id="loading-variant">
                                        <label class="form-label">Variant <span class="text-danger">*</span></label>
                                        <select class="form-select" disabled>
                                            <option id="loading-text">Loading Variants.....</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="variant-container">
                                        <label class="form-label">Variant <span class="text-danger">*</span></label>
                                        <select class="form-select form-select2" name="VariantID" id="VariantID" required>
                                            <option value="">-</option>
                                        </select>
                                    </div>

                                    <div class="mb-3" id="loading-type">
                                        <label class="form-label">Inventory Type <span class="text-danger">*</span></label>
                                        <select class="form-select" disabled>
                                            <option id="loading-text">Loading Inventory Types.....</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="type-container">
                                        <label class="form-label">Inventory Type <span class="text-danger">*</span></label>
                                        <select class="form-select form-select2" name="InventoryTypeID" id="InventoryTypeID"
                                            required>
                                            <option value="">-</option>
                                        </select>
                                    </div>



                                    <div class="mb-4">
                                        <label class="form-label">Type</label>
                                        <div class="row">
                                            <div class="col">
                                                <div class="space-x-2">
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" id="PartTypeS"
                                                            name="PartType" value="S"
                                                            {{ old('PartType') ? (old('PartType') == 'S' ? 'checked' : '') : ($part->PartType == 'S' ? 'checked' : '') }}>
                                                        <label class="form-check-label" for="PartTypeS">Stock</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" id="PartTypeN"
                                                            name="PartType" value="N"
                                                            {{ old('PartType') ? (old('PartType') == 'N' ? 'checked' : '') : ($part->PartType == 'N' ? 'checked' : '') }}>
                                                        <label class="form-check-label" for="PartTypeN">Service</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col">
                                            <div class="mb-3">
                                                <label class="form-label">Minimum Stock Buffer</label>
                                                <input type="text" name="MinimumStockBuffer"
                                                    value="{{ old('MinimumStockBuffer') ?? $part->MinimumStockBuffer }}"
                                                    class="form-control number-input">
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="mb-3">
                                                <label class="form-label">Maximum Stock Buffer</label>
                                                <input type="text" name="MaximumStockBuffer"
                                                    value="{{ old('MaximumStockBuffer') ?? $part->MaximumStockBuffer }}"
                                                    class="form-control number-input">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Unique VAT Rate</label>
                                        <div class="d-flex flex-row align-items-center">
                                            <input type="text" name="VAT2"
                                                value="{{ old('VAT2') ?? $part->VAT2 }}"
                                                class="form-control number-input" style="width: 100px">
                                            <p class="mb-0 ms-2 fs-6">%</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6 col-12 ps-lg-5">
                                    <div class="mb-3">
                                        <label class="form-label">Part Name</label>
                                        <input type="text" name="PartName"
                                            value="{{ old('PartName') ?? $part->PartName }}" class="form-control"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Coil No</label>
                                        <input type="text" name="OtherID"
                                            value="{{ old('OtherID') ?? $part->OtherID }}" class="form-control">
                                    </div>

                                    <div class="mb-3" id="loading-warehouse">
                                        <label class="form-label">Deffered Warehouse</label>
                                        <select class="form-select" disabled>
                                            <option id="loading-text">Loading Warehouses.....</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="warehouse-container">
                                        <label class="form-label">Deffered Warehouse</label>
                                        <select class="form-select form-select2" name="DeferedWarehouseID"
                                            id="DeferedWarehouseID" required>
                                            <option value="">-</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea name="Notes" class="form-control" rows="3">{{ old('Notes') ?? $part->Notes }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="block block-rounded" id="vue-container">
                        <div class="block-content">
                            <div class="position-relative mb-3 pt-2">
                                <p class="position-absolute bg-white px-1 form-label" style="top: 0; left: 10px;">Level 1
                                </p>
                                <div class="border border-light rounded p-3">
                                    <div class="row">
                                        <div class="col">
                                            <div class="loading-unit">
                                                <label class="form-label">Unit ID</label>
                                                <select class="form-select" disabled>
                                                    <option id="loading-text">Loading Units.....</option>
                                                </select>
                                            </div>
                                            <div class="unit-container">
                                                <label class="form-label" for="Unit1">Unit ID</label>
                                                <select class="form-select unit" data-sequence="1" name="Unit[]" required
                                                    id="Unit1">
                                                    <option value="">-</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-auto invisible">
                                            <label class="form-label">-</label>
                                            <div class="d-flex flex-row align-items-center space-x-3">
                                                <p class="m-0 fs-6">=</p>
                                                <input class="form-control" style="width: 25vw;">
                                                <p class="m-0 fs-6 ps-3">x</p>
                                                <input class="form-control " style="width: 25vw;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="position-relative mb-3 pt-2">
                                <p class="position-absolute bg-white px-1 form-label" style="top: 0; left: 10px;">Level 2
                                </p>
                                <div class="border border-light rounded p-3">
                                    <div class="row">
                                        <div class="col">
                                            <div class="loading-unit">
                                                <label class="form-label">Unit ID</label>
                                                <select class="form-select" disabled>
                                                    <option id="loading-text">Loading Units.....</option>
                                                </select>
                                            </div>
                                            <div class="unit-container">
                                                <label class="form-label" for="Unit1">Unit ID</label>
                                                <select class="form-select unit" data-sequence="2" name="Unit[]"
                                                    id="Unit2" :disabled="!picked1">
                                                    <option value="">-</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <label class="form-label invisible">-</label>
                                            <div class="d-flex flex-row align-items-center space-x-3">
                                                <p class="m-0 fs-6">=</p>
                                                <input class="form-control unit1" v-model="unit1" readonly
                                                    style="width: 25vw;">
                                                <p class="m-0 fs-6 ps-3">x</p>
                                                <input class="form-control number-input" name="Conversion[]"
                                                    style="width: 25vw;"
                                                    value="{{ old('Conversion.0') ?? (count($part->units) > 1 ? $part->units[1]->Conversion : '') }}"
                                                    :disabled="!picked1">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="position-relative mb-3 pt-2">
                                <p class="position-absolute bg-white px-1 form-label" style="top: 0; left: 10px;">Level 3
                                </p>
                                <div class="border border-light rounded p-3">
                                    <div class="row">
                                        <div class="col">
                                            <div class="loading-unit">
                                                <label class="form-label">Unit ID</label>
                                                <select class="form-select" disabled>
                                                    <option id="loading-text">Loading Units.....</option>
                                                </select>
                                            </div>
                                            <div class="unit-container">
                                                <label class="form-label" for="Unit1">Unit ID</label>
                                                <select class="form-select unit" data-sequence="3" name="Unit[]"
                                                    id="Unit3" :disabled="!picked2">
                                                    <option value="">-</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <label class="form-label invisible">-</label>
                                            <div class="d-flex flex-row align-items-center space-x-3">
                                                <p class="m-0 fs-6">=</p>
                                                <input class="form-control unit1" v-model="unit1" readonly
                                                    style="width: 25vw;">
                                                <p class="m-0 fs-6 ps-3">x</p>
                                                <input class="form-control number-input" name="Conversion[]"
                                                    value="{{ old('Conversion.1') ?? (count($part->units) > 2 ? $part->units[2]->Conversion : '') }}"
                                                    style="width: 25vw;" :disabled="!picked2">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="position-relative mb-3 pt-2">
                                <p class="position-absolute bg-white px-1 form-label" style="top: 0; left: 10px;">Level 4
                                </p>
                                <div class="border border-light rounded p-3">
                                    <div class="row">
                                        <div class="col">
                                            <div class="loading-unit">
                                                <label class="form-label">Unit ID</label>
                                                <select class="form-select" disabled>
                                                    <option id="loading-text">Loading Units.....</option>
                                                </select>
                                            </div>
                                            <div class="unit-container">
                                                <label class="form-label" for="Unit1">Unit ID</label>
                                                <select class="form-select unit" data-sequence="4" name="Unit[]"
                                                    id="Unit4" :disabled="!picked3">
                                                    <option value="">-</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <label class="form-label invisible">-</label>
                                            <div class="d-flex flex-row align-items-center space-x-3">
                                                <p class="m-0 fs-6">=</p>
                                                <input class="form-control unit1" v-model="unit1" readonly
                                                    style="width: 25vw;">
                                                <p class="m-0 fs-6 ps-3">x</p>
                                                <input class="form-control number-input" name="Conversion[]"
                                                    value="{{ old('Conversion.2') ?? (count($part->units) > 3 ? $part->units[3]->Conversion : '') }}"
                                                    style="width: 25vw;" :disabled="!picked3">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="position-relative mb-3 pt-2">
                                <p class="position-absolute bg-white px-1 form-label" style="top: 0; left: 10px;">Level 5
                                </p>
                                <div class="border border-light rounded p-3">
                                    <div class="row">
                                        <div class="col">
                                            <div class="loading-unit">
                                                <label class="form-label">Unit ID</label>
                                                <select class="form-select" disabled>
                                                    <option id="loading-text">Loading Units.....</option>
                                                </select>
                                            </div>
                                            <div class="unit-container">
                                                <label class="form-label" for="Unit1">Unit ID</label>
                                                <select class="form-select unit" data-sequence="5" name="Unit[]"
                                                    id="Unit5" :disabled="!picked4">
                                                    <option value="">-</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <label class="form-label invisible">-</label>
                                            <div class="d-flex flex-row align-items-center space-x-3">
                                                <p class="m-0 fs-6">=</p>
                                                <input class="form-control unit1" v-model="unit1" readonly
                                                    style="width: 25vw;">
                                                <p class="m-0 fs-6 ps-3">x</p>
                                                <input class="form-control number-input" name="Conversion[]"
                                                    value="{{ old('Conversion.3') ?? (count($part->units) > 4 ? $part->units[4]->Conversion : '') }}"
                                                    style="width: 25vw;" :disabled="!picked4">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="block block-rounded">
                        <div class="block-content">
                            <div class="mb-3">
                                <label class="form-label" for="Image">Image</label>
                                <div class="row">
                                    <div class="col">
                                        <input class="form-control" type="file" id="Image" name="Image"
                                            accept="image/png, image/jpg, image/jpeg">
                                    </div>
                                    <div class="col-auto">
                                        @if ($part->Image2 != null)
                                            <a href="{{ asset('storage/part/' . $part->Image2) }}" title="See Original"
                                                class="btn btn-alt-secondary" target="_blank"><i
                                                    class="fa fa-fw fa-eye"></i></a>
                                        @else
                                            <button type="button" class="btn btn-alt-secondary" disabled><i
                                                    class="fa fa-fw fa-eye"></i></button>
                                        @endif
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="block block-rounded">
                        <div class="block-content">
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-4">
                                        <label class="form-label mb-3">Price</label>
                                        <div class="row">
                                            <div class="col">
                                                <div class="space-y-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" id="Price1"
                                                            name="Pricing" value="BasedOnPrice"
                                                            {{ old('Pricing') ? (old('Pricing') == 'BasedOnPrice' ? 'checked' : '') : ($part->Pricing == 'BasedOnPrice' ? 'checked' : '') }}>
                                                        <label class="form-check-label" for="Price1">Based On
                                                            Price</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" id="Price2"
                                                            name="Pricing" value="BasedOnDiscBarometer"
                                                            {{ old('Pricing') ? (old('Pricing') == 'BasedOnDiscBarometer' ? 'checked' : '') : ($part->Pricing == 'BasedOnDiscBarometer' ? 'checked' : '') }}>
                                                        <label class="form-check-label" for="Price2">Based On Discount
                                                            Barometer</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-4">
                                        <label class="form-label mb-3">Type Of Guarantee</label>
                                        <div class="row">
                                            <div class="col">
                                                <div class="space-y-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" id="G1"
                                                            name="TypeOfGuarantee" value="GUARANTEE_PART"
                                                            {{ old('TypeOfGuarantee') ? (old('TypeOfGuarantee') == 'GUARANTEE_PART' ? 'checked' : '') : ($part->TypeOfGuarantee == 'GUARANTEE_PART' ? 'checked' : '') }}>
                                                        <label class="form-check-label" for="G1">Part
                                                            Guarantee</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" id="G2"
                                                            name="TypeOfGuarantee" value="GUARANTEE_SERVICE"
                                                            {{ old('TypeOfGuarantee') ? (old('TypeOfGuarantee') == 'GUARANTEE_SERVICE' ? 'checked' : '') : ($part->TypeOfGuarantee == 'GUARANTEE_SERVICE' ? 'checked' : '') }}>
                                                        <label class="form-check-label" for="G2">Service
                                                            Guarantee</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" id="G3"
                                                            name="TypeOfGuarantee" value="GUARANTEE_PART_SERVICE"
                                                            {{ old('TypeOfGuarantee') ? (old('TypeOfGuarantee') == 'GUARANTEE_PART_SERVICE' ? 'checked' : '') : ($part->TypeOfGuarantee == 'GUARANTEE_PART_SERVICE' ? 'checked' : '') }}>
                                                        <label class="form-check-label" for="G3">Service And Part
                                                            Guarantee</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" id="G4"
                                                            name="TypeOfGuarantee" value="NON_GUARANTEE"
                                                            {{ old('TypeOfGuarantee') ? (old('TypeOfGuarantee') == 'NON_GUARANTEE' ? 'checked' : '') : ($part->TypeOfGuarantee == 'NON_GUARANTEE' ? 'checked' : '') }}>
                                                        <label class="form-check-label" for="G4">Non
                                                            Guarantee</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
@endsection

@section('scripts')
    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>

    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.5.4"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.13/dist/vue.js"></script>

    <!-- Page JS Code -->
    <script>
        function load_category(n) {
            if (n === 0) {
                console.log('Sudah 3x proses load category')
                return
            }

            $.ajax({
                url: '{!! route('misc.category') !!}',
                type: 'GET',
            }).done(function(data) {
                if (data.status == 'success') {
                    var options = ''
                    var oldID = '{{ old('CategoryID') ?? $part->CategoryID }}'
                    for (var i = 0; i < data.data.length; i++) {
                        options +=
                            `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`
                    }

                    $('#CategoryID').append(options)
                    $('#loading-category').hide()
                    $('#category-container').show()
                } else {
                    $('#loading-text').html('Something went wrong!')

                    load_category(n - 1)
                    let nsisa = n - 1
                    console.log(`load category gagal, coba ${nsisa}x lagi`)
                }
            })
        }



        function load_specification(n) {
            if (n === 0) {
                console.log('Sudah 3x proses load specification')
                return
            }

            $.ajax({
                url: '{!! route('misc.specification') !!}',
                type: 'GET',
            }).done(function(data) {
                if (data.status == 'success') {
                    var options = ''
                    var oldID = '{{ old('SpecificationID') ?? $part->SpecificationID }}'
                    for (var i = 0; i < data.data.length; i++) {
                        options +=
                            `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`
                    }

                    $('#SpecificationID').append(options)
                    $('#loading-specification').hide()
                    $('#specification-container').show()
                } else {
                    $('#loading-text').html('Something went wrong!')

                    load_specification(n - 1)
                    let nsisa = n - 1
                    console.log(`load specification gagal, coba ${nsisa}x lagi`)
                }
            })
        }



        function load_variant(n) {
            if (n === 0) {
                console.log('Sudah 3x proses load variant')
                return
            }

            $.ajax({
                url: '{!! route('misc.variant') !!}',
                type: 'GET',
            }).done(function(data) {
                if (data.status == 'success') {
                    var options = ''
                    var oldID = '{{ old('VariantID') ?? $part->VariantID }}'
                    for (var i = 0; i < data.data.length; i++) {
                        options +=
                            `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`
                    }

                    $('#VariantID').append(options)
                    $('#loading-variant').hide()
                    $('#variant-container').show()
                } else {
                    $('#loading-text').html('Something went wrong!')

                    load_variant(n - 1)
                    let nsisa = n - 1
                    console.log(`load variant gagal, coba ${nsisa}x lagi`)
                }
            })
        }



        function load_type(n) {
            if (n === 0) {
                console.log('Sudah 3x proses load type')
                return
            }

            $.ajax({
                url: '{!! route('misc.type') !!}',
                type: 'GET',
            }).done(function(data) {
                if (data.status == 'success') {
                    var options = ''
                    var oldID = '{{ old('InventoryTypeID') ?? $part->InventoryTypeID }}'
                    for (var i = 0; i < data.data.length; i++) {
                        options +=
                            `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`
                    }

                    $('#InventoryTypeID').append(options)
                    $('#loading-type').hide()
                    $('#type-container').show()
                } else {
                    $('#loading-text').html('Something went wrong!')

                    load_type(n - 1)
                    let nsisa = n - 1
                    console.log(`load type gagal, coba ${nsisa}x lagi`)
                }
            })
        }



        function load_warehouse(n) {
            if (n === 0) {
                console.log('Sudah 3x proses load warehouse')
                return
            }

            $.ajax({
                url: '{!! route('misc.warehouse') !!}',
                type: 'GET',
            }).done(function(data) {
                if (data.status == 'success') {
                    var options = ''
                    var oldID = '{{ old('DeferedWarehouseID') ?? $part->DeferedWarehouseID }}'
                    for (var i = 0; i < data.data.length; i++) {
                        options +=
                            `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`
                    }

                    $('#DeferedWarehouseID').append(options)
                    $('#loading-warehouse').hide()
                    $('#warehouse-container').show()
                } else {
                    $('#loading-text').html('Something went wrong!')

                    load_warehouse(n - 1)
                    let nsisa = n - 1
                    console.log(`load warehouse gagal, coba ${nsisa}x lagi`)
                }
            })
        }



        $(function() {
            $('.form-select2').select2({
                theme: 'bootstrap-5'
            });

            let app = new Vue({
                el: '#vue-container',
                data: {
                    unit1: '-',
                    picked1: false,
                    picked2: false,
                    picked3: false,
                    picked4: false,
                },
                mounted() {
                    let numeric = new AutoNumeric.multiple('.number-input', {
                        allowDecimalPadding: "false",
                        modifyValueOnWheel: false,
                        digitGroupSeparator: '.',
                        decimalCharacter: ',',
                        unformatOnSubmit: true
                    });

                    $('.unit').select2({
                        theme: 'bootstrap-5'
                    }).on('change', function() {
                        app.updateUnit($(this));
                    });

                    this.loadUnit();
                },
                methods: {
                    updateUnit(event) {
                        if (event.data('sequence') == '1') {
                            this.unit1 = event.find("option:selected").text();
                            if (event.val() != '') {
                                this.picked1 = true;
                            } else {
                                this.picked1 = false;
                                this.picked2 = false;
                                $('#Unit2').val('').change();
                            }
                        } else if (event.data('sequence') == '2') {
                            if (event.val() != '') {
                                this.picked2 = true;
                            } else {
                                this.picked2 = false;
                                $('#Unit3').val('').change();
                            }
                        } else if (event.data('sequence') == '3') {
                            if (event.val() != '') {
                                this.picked3 = true;
                            } else {
                                this.picked3 = false;
                                $('#Unit4').val('').change();
                            }
                        } else if (event.data('sequence') == '4') {
                            if (event.val() != '') {
                                this.picked4 = true;
                            } else {
                                this.picked4 = false;
                                $('#Unit5').val('').change();
                            }
                        }
                    },
                    loadUnit() {
                        let el = this;
                        $.ajax({
                            url: '{!! route('misc.unit') !!}',
                            type: 'GET',
                        }).done(function(data) {
                            if (data.status == 'success') {
                                var opt1 = '';
                                var oldID =
                                    '{{ old('Unit.0') ?? (count($part->units) > 0 ? $part->units[0]->UnitID1 : '') }}';
                                console.log(oldID);
                                for (var i = 0; i < data.data.length; i++) {
                                    if (oldID == data.data[i].id) {
                                        el.unit1 =
                                            `${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}`;
                                    }
                                    opt1 +=
                                        `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                                }
                                if (oldID != '') {
                                    el.picked1 = true;
                                }
                                $('#Unit1').append(opt1);

                                var opt2 = '';
                                oldID =
                                    '{{ old('Unit.1') ?? (count($part->units) > 1 ? $part->units[1]->UnitID2 : '') }}';
                                for (var i = 0; i < data.data.length; i++) {
                                    opt2 +=
                                        `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                                }
                                if (oldID != '') {
                                    el.picked2 = true;
                                }
                                $('#Unit2').append(opt2);

                                var opt3 = '';
                                oldID =
                                    '{{ old('Unit.2') ?? (count($part->units) > 2 ? $part->units[2]->UnitID2 : '') }}';
                                for (var i = 0; i < data.data.length; i++) {
                                    opt3 +=
                                        `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                                }
                                if (oldID != '') {
                                    el.picked3 = true;
                                }
                                $('#Unit3').append(opt3);

                                var opt4 = '';
                                oldID =
                                    '{{ old('Unit.3') ?? (count($part->units) > 3 ? $part->units[3]->UnitID2 : '') }}';
                                for (var i = 0; i < data.data.length; i++) {
                                    opt4 +=
                                        `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                                }
                                if (oldID != '') {
                                    el.picked4 = true;
                                }
                                $('#Unit4').append(opt4);

                                var opt5 = '';
                                oldID =
                                    '{{ old('Unit.4') ?? (count($part->units) > 4 ? $part->units[4]->UnitID2 : '') }}';
                                for (var i = 0; i < data.data.length; i++) {
                                    opt5 +=
                                        `<option value="${data.data[i].id}" ${ oldID == data.data[i].id ? 'selected' : '' }>${data.data[i].id}${data.data[i].text != null ? ' - ' + data.data[i].text : ''}</option>`;
                                }
                                $('#Unit5').append(opt5);

                                $('.loading-unit').hide();
                                $('.unit-container').show();
                            } else {
                                $('#loading-text').html('Something went wrong!');
                            }
                        });
                    }
                }
            });

            $('#category-container').hide();
            $('#specification-container').hide();
            $('#variant-container').hide();
            $('#type-container').hide();
            $('#warehouse-container').hide();
            $('.unit-container').hide();


            load_category(3)
            load_specification(3)
            load_variant(3)
            load_type(3)
            load_warehouse(3)

        });
    </script>
@endsection
