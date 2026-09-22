@extends('layouts.admin')

@section('titles')
    <title>Keyonline - Add Inventory Part</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3" id="vue-container">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">

                <form autocomplete="off" method="post" enctype="multipart/form-data" action="{{ route('inventory.part.store') }}" id="inventory-part-form">
                    @csrf

                    <div class="d-flex flex-row align-items-center mb-5">
                        <a href="{{ route('inventory.part') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                        <h1 class="h3 fw-bold ms-4 mb-0">
                            Add Inventory Part
                        </h1>
                    </div>

                    @if(count($errors->all()) > 0)
                        <div class="alert alert-danger">
                            @foreach($errors->all() as $error)
                                <p class="m-0 fs-6">{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <div class="block block-rounded">
                        <div class="block-content pb-3">
                            <button type="submit" class="btn btn-primary mb-3 fs-6"><i class="fa fa-fw fa-save me-2"></i>Save</button>

                            <div class="row align-items-center mb-3">
                                <div class="col-lg-2 col-12">
                                    <label class="form-label">Part ID <span class="text-danger">*</span></label>
                                    <input type="text" name="PartID" id="PartID" class="form-control" v-model="PartID" required>
                                </div>
                                <div class="col-auto px-lg-0">
                                    <label class="form-label"></label>
                                    <div class="form-check ms-lg-4 pt-lg-2">
                                        <input class="form-check-input fs-6" type="checkbox" value="1" name="Active" id="Active" v-model="Active">
                                        <label class="form-check-label fs-6" for="Active">
                                            Active
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-6 col-12 pe-lg-5">
                                    <div class="mb-3">
                                        <label class="form-label">Category <span class="text-danger">*</span></label>
                                        <select2 url="{{ route('misc.category') }}" v-model="CategoryID" :prevalue="CategoryID" class="form-select"
                                                 name="CategoryID" id="CategoryID" required>
                                            <option value="">-</option>
                                        </select2>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Specification <span class="text-danger">*</span></label>
                                        <select2 url="{{ route('misc.specification') }}" v-model="SpecificationID" :prevalue="SpecificationID" class="form-select"
                                                 name="SpecificationID" id="SpecificationID" required>
                                            <option value="">-</option>
                                        </select2>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Variant <span class="text-danger">*</span></label>
                                        <select2 url="{{ route('misc.variant') }}" v-model="VariantID" :prevalue="VariantID" class="form-select"
                                                 name="VariantID" id="VariantID" required>
                                            <option value="">-</option>
                                        </select2>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Inventory Type <span class="text-danger">*</span></label>
                                        <select2 url="{{ route('misc.type') }}" v-model="InventoryTypeID" :prevalue="InventoryTypeID" class="form-select"
                                                 name="InventoryTypeID" id="InventoryTypeID" required>
                                            <option value="">-</option>
                                        </select2>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label">Type</label>
                                        <div class="row">
                                            <div class="col">
                                                <div class="space-x-2">
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" id="PartTypeS" name="PartType" value="S" v-model="PartType">
                                                        <label class="form-check-label" for="PartTypeS">Stock</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" id="PartTypeN" name="PartType" value="N" v-model="PartType">
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
                                                <vue-autonumeric :options="autonumericFormat" name="MinimumStockBuffer" class="form-control" v-model="MinimumStockBuffer"></vue-autonumeric>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="mb-3">
                                                <label class="form-label">Maximum Stock Buffer</label>
                                                <vue-autonumeric :options="autonumericFormat" name="MaximumStockBuffer" class="form-control" v-model="MaximumStockBuffer"></vue-autonumeric>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Unique VAT Rate</label>
                                        <div class="d-flex flex-row align-items-center">
                                            <vue-autonumeric :options="autonumericFormat" name="VAT2" class="form-control" v-model="VAT2" style="width: 100px"></vue-autonumeric>
                                            <p class="mb-0 ms-2 fs-6">%</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6 col-12 ps-lg-5">
                                    <div class="mb-3">
                                        <label class="form-label">Part Name</label>
                                        <input type="text" name="PartName" v-model="PartName" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Coil No</label>
                                        <input type="text" name="OtherID" v-model="OtherID" class="form-control">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Deffered Warehouse</label>
                                        <select2 url="{{ route('misc.warehouse') }}" v-model="DeferedWarehouseID" :prevalue="DeferedWarehouseID" class="form-select"
                                                 name="DeferedWarehouseID" id="DeferedWarehouseID">
                                            <option value="">-</option>
                                        </select2>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea name="Notes" class="form-control" rows="3" v-model="Notes"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="block block-rounded">
                        <div class="block-content">
                            <div class="position-relative mb-3 pt-2">
                                <p class="position-absolute bg-white px-1 form-label" style="top: 0; left: 10px;">Level 1</p>
                                <div class="border border-light rounded p-3">
                                    <div class="row">
                                        <div class="col">
                                            <label class="form-label" for="Unit1">Unit ID</label>
                                            <select2 url="{{ route('misc.unit') }}" v-model="Unit1" :prevalue="Unit1" class="form-select"
                                                     name="Unit[]" id="Unit1" required @selected_text="updateUnit1Text">
                                                <option value="">-</option>
                                            </select2>
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
                                <p class="position-absolute bg-white px-1 form-label" style="top: 0; left: 10px;">Level 2</p>
                                <div class="border border-light rounded p-3">
                                    <div class="row">
                                        <div class="col">
                                            <label class="form-label" for="Unit2">Unit ID</label>
                                            <select2 url="{{ route('misc.unit') }}" v-model="Unit2" :prevalue="Unit2" class="form-select"
                                                     name="Unit[]" id="Unit2" :disabled="!Unit1">
                                                <option value="">-</option>
                                            </select2>
                                        </div>
                                        <div class="col-auto">
                                            <label class="form-label invisible">-</label>
                                            <div class="d-flex flex-row align-items-center space-x-3">
                                                <p class="m-0 fs-6">=</p>
                                                <input class="form-control" v-model="Unit1Text" readonly style="width: 25vw;">
                                                <p class="m-0 fs-6 ps-3">x</p>
                                                <vue-autonumeric :options="autonumericFormat" name="Conversion[]" style="width: 25vw;" class="form-control" v-model="Conversion2" :disabled="!Unit1 || !Unit2"></vue-autonumeric>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="position-relative mb-3 pt-2">
                                <p class="position-absolute bg-white px-1 form-label" style="top: 0; left: 10px;">Level 3</p>
                                <div class="border border-light rounded p-3">
                                    <div class="row">
                                        <div class="col">
                                            <label class="form-label" for="Unit3">Unit ID</label>
                                            <select2 url="{{ route('misc.unit') }}" v-model="Unit3" :prevalue="Unit3" class="form-select"
                                                     name="Unit[]" id="Unit3" :disabled="!Unit2">
                                                <option value="">-</option>
                                            </select2>
                                        </div>
                                        <div class="col-auto">
                                            <label class="form-label invisible">-</label>
                                            <div class="d-flex flex-row align-items-center space-x-3">
                                                <p class="m-0 fs-6">=</p>
                                                <input class="form-control" v-model="Unit1Text" readonly style="width: 25vw;">
                                                <p class="m-0 fs-6 ps-3">x</p>
                                                <vue-autonumeric :options="autonumericFormat" name="Conversion[]" style="width: 25vw;" class="form-control" v-model="Conversion3" :disabled="!Unit2 || !Unit3"></vue-autonumeric>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="position-relative mb-3 pt-2">
                                <p class="position-absolute bg-white px-1 form-label" style="top: 0; left: 10px;">Level 4</p>
                                <div class="border border-light rounded p-3">
                                    <div class="row">
                                        <div class="col">
                                            <label class="form-label" for="Unit4">Unit ID</label>
                                            <select2 url="{{ route('misc.unit') }}" v-model="Unit4" :prevalue="Unit4" class="form-select"
                                                     name="Unit[]" id="Unit4" :disabled="!Unit3">
                                                <option value="">-</option>
                                            </select2>
                                        </div>
                                        <div class="col-auto">
                                            <label class="form-label invisible">-</label>
                                            <div class="d-flex flex-row align-items-center space-x-3">
                                                <p class="m-0 fs-6">=</p>
                                                <input class="form-control" v-model="Unit1Text" readonly style="width: 25vw;">
                                                <p class="m-0 fs-6 ps-3">x</p>
                                                <vue-autonumeric :options="autonumericFormat" name="Conversion[]" style="width: 25vw;" class="form-control" v-model="Conversion4" :disabled="!Unit3 || !Unit4"></vue-autonumeric>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="position-relative mb-3 pt-2">
                                <p class="position-absolute bg-white px-1 form-label" style="top: 0; left: 10px;">Level 5</p>
                                <div class="border border-light rounded p-3">
                                    <div class="row">
                                        <div class="col">
                                            <label class="form-label" for="Unit5">Unit ID</label>
                                            <select2 url="{{ route('misc.unit') }}" v-model="Unit5" :prevalue="Unit5" class="form-select"
                                                     name="Unit[]" id="Unit5" :disabled="!Unit4">
                                                <option value="">-</option>
                                            </select2>
                                        </div>
                                        <div class="col-auto">
                                            <label class="form-label invisible">-</label>
                                            <div class="d-flex flex-row align-items-center space-x-3">
                                                <p class="m-0 fs-6">=</p>
                                                <input class="form-control" v-model="Unit1Text" readonly style="width: 25vw;">
                                                <p class="m-0 fs-6 ps-3">x</p>
                                                <vue-autonumeric :options="autonumericFormat" name="Conversion[]" style="width: 25vw;" class="form-control" v-model="Conversion5" :disabled="!Unit4 || !Unit5"></vue-autonumeric>
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
                                <input class="form-control" type="file" id="Image" name="Image" accept="image/png, image/jpg, image/jpeg">
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
                                                        <input class="form-check-input" type="radio" id="Price1" name="Pricing" value="BasedOnPrice" v-model="Pricing">
                                                        <label class="form-check-label" for="Price1">Based On Price</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" id="Price2" name="Pricing" value="BasedOnDiscBarometer" v-model="Pricing">
                                                        <label class="form-check-label" for="Price2">Based On Discount Barometer</label>
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
                                                        <input class="form-check-input" type="radio" id="G1" name="TypeOfGuarantee" value="GUARANTEE_PART" v-model="TypeOfGuarantee">
                                                        <label class="form-check-label" for="G1">Part Guarantee</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" id="G2" name="TypeOfGuarantee" value="GUARANTEE_SERVICE" v-model="TypeOfGuarantee">
                                                        <label class="form-check-label" for="G2">Service Guarantee</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" id="G3" name="TypeOfGuarantee" value="GUARANTEE_PART_SERVICE" v-model="TypeOfGuarantee">
                                                        <label class="form-check-label" for="G3">Service And Part Guarantee</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" id="G4" name="TypeOfGuarantee" value="NON_GUARANTEE" v-model="TypeOfGuarantee">
                                                        <label class="form-check-label" for="G4">Non Guarantee</label>
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
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('scripts')
    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>

    <!-- Page JS Plugins -->
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

    <!-- Page JS Code -->
    <script>
        let app = new Vue({
            el: '#vue-container',
            data: {
                PartID: '{{ old('PartID') ?? '' }}',
                Active: {{ old('Active') || count($errors->all()) == 0 ? 'true' : 'false' }},
                CategoryID: '{{ old('CategoryID') ?? '' }}',
                SpecificationID: '{{ old('SpecificationID') ?? '' }}',
                VariantID: '{{ old('VariantID') ?? '' }}',
                InventoryTypeID: '{{ old('InventoryTypeID') ?? '' }}',
                PartType: '{{ old('PartType') ?? 'S' }}',
                MinimumStockBuffer: '{{ old('MinimumStockBuffer') ?? '' }}',
                MaximumStockBuffer: '{{ old('MaximumStockBuffer') ?? '' }}',
                VAT2: '{{ old('VAT2') ?? '11' }}',
                PartName: '{{ old('PartName') ?? '' }}',
                OtherID: '{{ old('OtherID') ?? '' }}',
                DeferedWarehouseID: '{{ old('DeferedWarehouseID') ?? '' }}',
                Notes: '{{ old('Notes') ?? '' }}',
                Pricing: '{{ old('Pricing') ?? 'BasedOnPrice' }}',
                TypeOfGuarantee: '{{ old('TypeOfGuarantee') ?? 'NON_GUARANTEE' }}',

                Unit1: '{{ old('Unit.0') ?? '' }}',
                Unit2: '{{ old('Unit.1') ?? '' }}',
                Unit3: '{{ old('Unit.2') ?? '' }}',
                Unit4: '{{ old('Unit.3') ?? '' }}',
                Unit5: '{{ old('Unit.4') ?? '' }}',
                Conversion2: '{{ old('Conversion.0') ?? '' }}',
                Conversion3: '{{ old('Conversion.1') ?? '' }}',
                Conversion4: '{{ old('Conversion.2') ?? '' }}',
                Conversion5: '{{ old('Conversion.3') ?? '' }}',
                Unit1Text: '-',

                isLoading: false,

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
            },
            mounted() {
                FormPreserver.initVue(this, 'inventory_part_add', ['isLoading', 'autonumericFormat']);
            },
            methods: {
                updateUnit1Text(text){
                    if(text && text !== '-' && text !== ''){
                        this.Unit1Text = text;
                    } else {
                        this.Unit1Text = '-';
                    }
                }
            }
        });
    </script>
@endsection
