@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - QR Detail</title>
@endsection

@section('content')
    @php
        $payload = $qr->json_display ?? [];
        $data = $payload['data'] ?? [];
        $qrContent = $qr->code;
    @endphp

    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start d-print-none">
            <div>
                <h1 class="h3 fw-bold mb-2">QR Detail</h1>
                <div class="text-muted">{{ $qr->code }}</div>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fa fa-print me-1"></i> Print
                </button>
                @if(auth()->user()->hasAnyPermission(['admin', 'qr_generator.edit']))
                    <a href="{{ route('inventory.qr_generator.edit', $qr->id) }}" class="btn btn-alt-secondary ms-2">
                        <i class="fa fa-edit me-1"></i> Edit
                    </a>
                @endif
                <a href="{{ route('inventory.qr_generator') }}" class="btn btn-alt-secondary ms-2">
                    <i class="fa fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        <div class="block block-rounded shadow-sm mt-3 d-print-none">
            <div class="block-content bg-body-light pb-4">
                <div class="label-box">
                    <div class="qr-code">
                        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(100)->generate($qrContent) !!}
                    </div>
                    <div class="label-details">
                        @foreach($data as $key => $value)
                            <div class="d-line">{{ strtoupper($key) }}: {{ $value }}</div>
                        @endforeach
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
                    @foreach($data as $key => $value)
                        <div class="p-line">{{ strtoupper($key) }}: {{ $value }}</div>
                    @endforeach
                </div>
            </div>
        </div>
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
            .p-line { -webkit-text-stroke: 0.2px black; letter-spacing: 0.05em; }
            @page { size: 80mm 30mm; margin: 0; }
        }
    </style>
@endsection
