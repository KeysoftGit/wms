@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Inventory Part - {{ $part->PartID }}</title>
@endsection

@section('content')

    <!-- Hero -->
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">

                <div class="d-flex flex-row align-items-center mb-5">
                    <a href="{{ route('inventory.part') }}" class="h3 text-dark m-0"><i class="fa fa-fw fa-arrow-left"></i></a>
                    <h1 class="h3 fw-bold ms-4 mb-0">
                        {{ $part->PartID }} {{ $part->PartName != null ? '- ' . $part->PartName : '' }}
                    </h1>
                </div>

                @if($part->Editable == 1 && auth()->user()->hasAnyPermission(['admin', 'part..edit']))
                    <a href="{{ route('inventory.part.edit', $part->id) }}" class="btn btn-primary fs-6 mb-3"><i class="fa fa-fw fa-edit"></i> Edit</a>
                @endif


                <div class="block block-rounded">
                    <div class="block-content">

                        <div class="row justify-content-between pe-7">
                            <div class="col-auto row">
                                <div class="col-lg-auto col-12">
                                    <div class="rounded rounded-4 overflow-hidden mb-3">
                                        @if($part->Image2 != null)
                                            <a href="{{ asset('storage/part/'.$part->Image2) }}" target="_blank">
                                                <img src="{{ asset('storage/part/'.$part->Image2) }}"
                                                     style="object-fit: cover; width: 250px; height: 250px; cursor: pointer">
                                            </a>
                                        @else
                                            <img src="{{ asset('media/placeholder.jpeg') }}"
                                                 style="object-fit: cover; width: 250px; height: 250px;">
                                        @endif

                                    </div>
                                </div>

                                <div class="col-lg-auto col-12 ms-lg-4">
                                    <div class="mb-3">
                                        <p class="m-0 fw-bold fs-6">Part Name</p>
                                        <p class="m-0 fs-6">{{ $part->PartName != null ? $part->PartName : '-' }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="m-0 fw-bold fs-6">Coil No</p>
                                        <p class="m-0 fs-6">{{ $part->OtherID != null ? $part->OtherID : '-' }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="m-0 fw-bold fs-6">Deffered Warehouse</p>
                                        <p class="m-0 fs-6">{{ $part->DeferedWarehouseID != null ? $part->warehouse->WarehouseID . ($part->warehouse->WarehouseName != null ? ' - ' . $part->warehouse->WarehouseName : '') : '-' }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="m-0 fw-bold fs-6">Notes</p>
                                        <p class="m-0 fs-6">{{ $part->Notes != null ? $part->Notes : '-' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-auto col-12">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Category</p>
                                    <p class="m-0 fs-6">{{ $part->category != null ? $part->CategoryID . ($part->category->CategoryName != null ? ' - ' . $part->category->CategoryName : '') : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Specification</p>
                                    <p class="m-0 fs-6">{{ $part->specification != null ? $part->SpecificationID . ($part->specification->SpecificationName != null ? ' - ' . $part->specification->SpecificationName : '') : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Variant</p>
                                    <p class="m-0 fs-6">{{ $part->variant != null ? $part->VariantID . ($part->variant->VariantName != null ? ' - ' . $part->variant->VariantName : '') : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Inventory Type</p>
                                    <p class="m-0 fs-6">{{ $part->type != null ? $part->InventoryTypeID . ($part->type->InventoryTypeName != null ? ' - ' . $part->type->InventoryTypeName : '') : '-' }}</p>
                                </div>
                            </div>

                            <div class="col-lg-auto col-12">
{{--                                <div class="mb-3">--}}
{{--                                    <p class="m-0 fw-bold fs-6">Serial Number</p>--}}
{{--                                </div>--}}
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Part Type</p>
                                    <p class="m-0 fs-6">{{ $part->PartType != null ? ($part->PartType == 'S' ? 'Stock' : 'Service') : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Minimum Stock Buffer</p>
                                    <p class="m-0 fs-6">{{ $part->MinimumStockBuffer != null ? $part->MinimumStockBuffer : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Maximum Stock Buffer</p>
                                    <p class="m-0 fs-6">{{ $part->MaximumStockBuffer != null ? $part->MaximumStockBuffer : '-' }}</p>
                                </div>
                            </div>

                            <div class="col-lg-auto col-12">
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Unique VAT Rate</p>
                                    <p class="m-0 fs-6">{{ $part->VAT2 != null ? $part->VAT2 : '-' }}%</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Pricing</p>
                                    <p class="m-0 fs-6">{{ $part->Pricing != null ? ($part->Pricing == 'BasedOnPrice' ? 'Based On Price' : 'Based On Discount Barometer') : '-' }}</p>
                                </div>
                                <div class="mb-3">
                                    <p class="m-0 fw-bold fs-6">Type Of Guarantee</p>
                                    @if($part->TypeOfGuarantee == 'GUARANTEE_PART')
                                        <p class="m-0 fs-6">Part Guarantee</p>
                                    @elseif($part->TypeOfGuarantee == 'GUARANTEE_SERVICE')
                                        <p class="m-0 fs-6">Service Guarantee</p>
                                    @elseif($part->TypeOfGuarantee == 'GUARANTEE_PART_SERVICE')
                                        <p class="m-0 fs-6">Service And Part Guarantee</p>
                                    @elseif($part->TypeOfGuarantee == 'NON_GUARANTEE')
                                        <p class="m-0 fs-6">Non Guarantee</p>
                                    @else
                                        <p class="m-0 fs-6">-</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="block block-rounded">
                    <div class="block-content pb-2">
                        <h5>Unit</h5>
                        @foreach($part->units as $i => $unit)
                            @if($i == 0)
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Level {{ $unit->Sequence }}</span>
                                    <input type="text" class="form-control fs-6" value="[{{ $unit->unit2->UnitID . ($unit->unit2->UnitName != null ? ' - ' . $unit->unit2->UnitName : '') }}]" readonly>
                                </div>
                            @else
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Level {{ $unit->Sequence }}</span>
                                    <input type="text" class="form-control fs-6" value="[{{ $unit->unit2->UnitID . ($unit->unit2->UnitName != null ? ' - ' . $unit->unit2->UnitName : '') }}] = [{{ $unit->unit1->UnitID . ($unit->unit1->UnitName != null ? ' - ' . $unit->unit1->UnitName : '') }}] x {{ number_format($unit->Conversion, 0, '.', '') }}" readonly>
                                </div>
                            @endif

                        @endforeach
                    </div>
                </div>

            </div>
        </div>
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

    <!-- Page JS Plugins -->

    <!-- Page JS Code -->
    <script>
        $(function() {


        });
    </script>
@endsection
