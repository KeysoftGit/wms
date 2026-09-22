@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - QR Preview</title>
@endsection

@section('content')
    <div class="px-lg-5 py-lg-3 p-3">
        <!-- Control Header -->
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start d-print-none">
            <div class="flex-grow-1 mb-3 mb-md-0">
                <h1 class="h3 fw-bold mb-2">Generate QR Codes</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt mb-0">
                        <li class="breadcrumb-item">Inventory</li>
                        <li class="breadcrumb-item">Part</li>
                        <li class="breadcrumb-item active" aria-current="page">QR Preview</li>
                    </ol>
                </nav>
            </div>
            <div class="block-options">
                <button onclick="window.print()" class="btn btn-primary shadow-sm">
                    <i class="fa fa-print me-1"></i> Print Labels
                </button>
                <a href="{{ route('inventory.part') }}" class="btn btn-alt-secondary shadow-sm ms-2">
                    <i class="fa fa-arrow-left me-1"></i> Back to List
                </a>
            </div>
        </div>

        <!-- Preview Area -->
        <div class="block block-rounded shadow-sm mt-3 d-print-none">
            <div class="block-header block-header-default">
                <h3 class="block-title">Print Preview</h3>
                <div class="block-options text-muted small">
                    <i class="fa fa-info-circle me-1"></i> Label Size: 80mm x 30mm
                </div>
            </div>
            <div class="block-content bg-body-light pb-4">
                <div class="preview-scroll-container">
                    <div class="labels-container">
                        @foreach ($parts as $part)
                            @php
                                $qrContent = json_encode([
                                    'data' => [
                                        'PartID' => $part->PartID,
                                    ],
                                ]);
                            @endphp
                            <div class="label-preview-item">
                                <div class="label-box">
                                    <div class="qr-code">
                                        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(100)->generate($qrContent) !!}
                                    </div>
                                    <div class="label-details">
                                        <div class="d-line">PN: {{ $part->PartID }}</div>
                                        @if($part->PartName)
                                            <div class="d-line">NAME: {{ Str::limit($part->PartName, 40) }}</div>
                                        @endif
                                        @if($part->category)
                                            <div class="d-line">CAT: {{ $part->category->CategoryName }}</div>
                                        @endif
                                        @if($part->specification)
                                            <div class="d-line">SPEC: {{ $part->specification->SpecificationName }}</div>
                                        @endif
                                        @if($part->type)
                                            <div class="d-line">TYPE: {{ $part->type->InventoryTypeName }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Real Print Area (Hidden in browser, visible in print) -->
        <div class="print-only-area">
            @foreach ($parts as $part)
                @php
                    $qrContent = json_encode([
                        'data' => [
                            'PartID' => $part->PartID,
                        ],
                    ]);
                @endphp
                <div class="print-label">
                    <div class="p-qr">
                        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(100)->generate($qrContent) !!}
                    </div>
                    <div class="p-details">
                        <div class="p-line">PN: {{ $part->PartID }}</div>
                        @if($part->PartName)
                            <div class="p-line">NAME: {{ Str::limit($part->PartName, 40) }}</div>
                        @endif
                        @if($part->category)
                            <div class="p-line">CAT: {{ $part->category->CategoryName }}</div>
                        @endif
                        @if($part->specification)
                            <div class="p-line">SPEC: {{ $part->specification->SpecificationName }}</div>
                        @endif
                        @if($part->type)
                            <div class="p-line">TYPE: {{ $part->type->InventoryTypeName }}</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@section('styles')
    <style>
        /* --- PREVIEW UI STYLES --- */
        .preview-scroll-container {
            width: 100%;
            overflow-x: auto;
            padding: 20px 0;
            display: flex;
            justify-content: center;
        }

        .labels-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            align-items: center;
        }

        .label-preview-item {
            background: #fff;
            padding: 0;
            border-radius: 4px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            position: relative;
        }

        /* Scaled Preview (matches real aspect ratio) */
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

        .qr-code {
            width: 24mm;
            height: 24mm;
            flex-shrink: 0;
        }
        .qr-code svg { width: 100% !important; height: 100% !important; }

        .label-details {
            margin-left: 3mm;
            font-family: "Arial Black", sans-serif;
            font-size: 9pt;
            font-weight: 900;
            line-height: 1.1;
            color: #000;
        }

        .d-line {
            word-break: break-all;
            letter-spacing: 0.02em;
        }

        /* --- PRINT STYLES --- */
        .print-only-area {
            display: none;
        }

        @media print {
            /* Hide entire system layout */
            body * { visibility: hidden; }
            #page-container, #main-container, .print-only-area, .print-only-area * { visibility: visible; }
            
            #page-container { padding: 0 !important; margin: 0 !important; }
            #sidebar, #page-header, .d-print-none, .preview-scroll-container, .block-header { display: none !important; }
            
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

            @page {
                size: 80mm 30mm;
                margin: 0;
            }
        }
    </style>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dynamic Font Sizing for both Preview and Print
            const adjustFont = (containerSelector, lineSelector) => {
                document.querySelectorAll(containerSelector).forEach(container => {
                    const lines = container.querySelectorAll(lineSelector);
                    const count = lines.length;
                    
                    if (count <= 2) container.style.fontSize = '11pt';
                    else if (count <= 3) container.style.fontSize = '10pt';
                    else if (count <= 4) container.style.fontSize = '9pt';
                    else container.style.fontSize = '8pt';
                });
            };

            adjustFont('.label-details', '.d-line');
            adjustFont('.p-details', '.p-line');
        });
    </script>
@endsection
