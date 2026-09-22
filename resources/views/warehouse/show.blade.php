@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Warehouse - {{ $warehouse->WarehouseID }}</title>
@endsection

@section('content')
    @php
        $qrContent = $qr?->code;
        $data = $qr ? ($qr->json_display['data'] ?? ['Code' => $qr->code, 'WarehouseID' => $warehouse->WarehouseID]) : [];
    @endphp

    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start d-print-none">
            <div class="flex-grow-1 mb-3 mb-md-0">
                <div class="d-flex flex-row align-items-center mb-2">
                    <a href="{{ route('warehouse') }}" class="h3 text-dark m-0">
                        <i class="fa fa-fw fa-arrow-left"></i>
                    </a>
                    <h1 class="h3 fw-bold ms-4 mb-0">
                        {{ $warehouse->WarehouseID }}{{ $warehouse->WarehouseName ? ' - ' . $warehouse->WarehouseName : '' }}
                    </h1>
                </div>
            </div>
            <div>
                @if($qr)
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fa fa-print me-1"></i> Print QR
                    </button>
                @elseif(auth()->user()->hasAnyPermission(['admin', 'warehouse.edit']))
                    <form action="{{ route('warehouse.generate_qr', $warehouse->id) }}" method="post" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-qrcode me-1"></i> Generate QR
                        </button>
                    </form>
                @endif
                @if(auth()->user()->hasAnyPermission(['admin', 'warehouse.edit']))
                    <a href="{{ route('warehouse.edit', $warehouse->id) }}" class="btn btn-alt-secondary ms-2">
                        <i class="fa fa-edit me-1"></i> Edit
                    </a>
                @endif
            </div>
        </div>

        <div class="block block-rounded d-print-none">
            <div class="block-content">
                <div class="row justify-content-between">
                    <div class="col-lg-4 col-12">
                        <div class="mb-3">
                            <p class="m-0 fw-bold fs-6">Warehouse Name</p>
                            <p class="m-0 fs-6">{{ $warehouse->WarehouseName ?: '-' }}</p>
                        </div>
                        <div class="mb-3">
                            <p class="m-0 fw-bold fs-6">Location</p>
                            <p class="m-0 fs-6">{{ $warehouse->Location ?: '-' }}</p>
                        </div>
                        <div class="mb-3">
                            <p class="m-0 fw-bold fs-6">Active</p>
                            <p class="m-0 fs-6">{{ $warehouse->Active ? 'YES' : 'NO' }}</p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-12">
                        <div class="mb-3">
                            <p class="m-0 fw-bold fs-6">Parent Warehouse</p>
                            <p class="m-0 fs-6">
                                {{ $warehouse->parent ? $warehouse->ParentID . ($warehouse->parent->WarehouseName ? ' - ' . $warehouse->parent->WarehouseName : '') : '-' }}
                            </p>
                        </div>
                        <div class="mb-3">
                            <p class="m-0 fw-bold fs-6">Staff In Charge</p>
                            <p class="m-0 fs-6">
                                {{ $warehouse->staff ? $warehouse->StaffInChargeID . ' - ' . trim(($warehouse->staff->FirstName ?? '') . ' ' . ($warehouse->staff->LastName ?? '')) : '-' }}
                            </p>
                        </div>
                        <div class="mb-3">
                            <p class="m-0 fw-bold fs-6">Division</p>
                            <p class="m-0 fs-6">
                                {{ $warehouse->division ? $warehouse->DivisionID . ($warehouse->division->DivisionName ? ' - ' . $warehouse->division->DivisionName : '') : '-' }}
                            </p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-12">
                        <div class="mb-3">
                            <p class="m-0 fw-bold fs-6">QR Code</p>
                            <p class="m-0 fs-6">{{ $qr?->code ?? '-' }}</p>
                        </div>
                        <div class="mb-3">
                            <p class="m-0 fw-bold fs-6">Notes</p>
                            <p class="m-0 fs-6">{{ $warehouse->Notes ?: '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($qr)
            <div class="block block-rounded shadow-sm mt-3 d-print-none">
                <div class="block-header block-header-default">
                    <h3 class="block-title">QR Label Preview</h3>
                    <div class="block-options text-muted small">
                        <i class="fa fa-info-circle me-1"></i> Label Size: 80mm x 30mm
                    </div>
                </div>
                <div class="block-content bg-body-light pb-4">
                    <div class="label-box">
                        <div class="qr-code">
                            {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(100)->generate($qrContent) !!}
                        </div>
                        <div class="label-details">
                            <div class="d-line">WH: {{ $warehouse->WarehouseID }}</div>
                            @if($warehouse->WarehouseName)
                                <div class="d-line">NAME: {{ Str::limit($warehouse->WarehouseName, 40) }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="print-only-area">
                <div class="print-label">
                    <div class="p-qr">
                        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(100)->generate($qrContent) !!}
                    </div>
                    <div class="p-details">
                        <div class="p-line">WH: {{ $warehouse->WarehouseID }}</div>
                        @if($warehouse->WarehouseName)
                            <div class="p-line">NAME: {{ Str::limit($warehouse->WarehouseName, 40) }}</div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('styles')
    <style>
        .label-box {
            width: 80mm;
            height: 30mm;
            display: flex;
            align-items: center;
            padding-left: 4mm;
            border: 1px solid #eee;
            box-sizing: border-box;
            background: #fff;
        }
        .qr-code { width: 24mm; height: 24mm; flex-shrink: 0; }
        .qr-code svg { width: 100% !important; height: 100% !important; }
        .label-details {
            margin-left: 3mm;
            font-family: "Arial Black", sans-serif;
            font-size: 9pt;
            font-weight: 900;
            line-height: 1.1;
            color: #000;
        }
        .d-line { word-break: break-all; letter-spacing: 0.02em; }
        .print-only-area { display: none; }

        @media print {
            body * { visibility: hidden; }
            #page-container, #main-container, .print-only-area, .print-only-area * { visibility: visible; }
            #page-container { padding: 0 !important; margin: 0 !important; }
            #sidebar, #page-header, .d-print-none { display: none !important; }
            .print-only-area {
                display: block !important;
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .print-label {
                width: 80mm;
                height: 30mm;
                display: flex;
                align-items: center;
                box-sizing: border-box;
                padding-left: 4mm;
                page-break-after: always;
                page-break-inside: avoid;
                font-family: "Arial Black", sans-serif;
                color: #000;
            }
            .p-qr { width: 24mm; height: 24mm; flex-shrink: 0; }
            .p-qr svg { width: 100% !important; height: 100% !important; }
            .p-details {
                margin-left: 3mm;
                font-size: 9pt;
                font-weight: 900;
                line-height: 1.1;
            }
            .p-line {
                -webkit-text-stroke: 0.2px black;
                letter-spacing: 0.05em;
            }
            @page { size: 80mm 30mm; margin: 0; }
        }
    </style>
@endsection
