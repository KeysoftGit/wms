@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Cost of Inventory Report</title>
@endsection

@section('content')
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2">
            <div class="flex-grow-1 mb-1 mb-md-0">
                <div class="d-flex flex-row align-items-center mb-5">
                    <a href="{{ route('report') }}" class="h3 text-dark m-0">
                        <i class="fa fa-fw fa-arrow-left"></i>
                    </a>
                    <h1 class="h3 fw-bold ms-4 mb-0">
                        Cost of Inventory Report
                    </h1>
                </div>

                <!-- Filter & Summary Section -->
                <div class="block block-rounded mb-4">
                    <div class="block-content">
                        <div class="row mb-3">
                            <div class="col-lg-6">
                                <h5 class="fw-bold mb-3">Report Information</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <strong>Report Date:</strong> {{ date('d F Y', strtotime($date)) }}
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <strong>Period:</strong> {{ $summary['period'] }}
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <strong>Generated On:</strong> {{ date('d F Y H:i:s') }}
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <strong>Total Items:</strong>
                                                {{ auto_numeric_format($summary['total_records']) }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <h5 class="fw-bold mb-3">Summary</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="bg-primary text-white p-3 rounded">
                                            <div class="fs-4 fw-bold">
                                                {{ auto_numeric_format($summary['total_beginning']) }}
                                            </div>
                                            <div class="small">Total Beginning Balance</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="bg-success text-white p-3 rounded">
                                            <div class="fs-4 fw-bold">
                                                {{ auto_numeric_format($summary['total_ending']) }}
                                            </div>
                                            <div class="small">Total Ending Balance</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <button class="btn btn-primary" onclick="window.print()">
                                    <i class="fa fa-fw fa-print me-1"></i> Print Report
                                </button>
                                <button class="btn btn-success ms-2" onclick="exportToExcel()">
                                    <i class="fa fa-fw fa-file-excel me-1"></i> Export to Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Data Table -->
                <div class="block block-rounded">
                    <div class="block-content">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter" id="reportTable">
                                <thead>
                                    <tr>
                                        <th class="text-center">No</th>
                                        <th>Period</th>
                                        <th>Category</th>
                                        <th>Part ID</th>
                                        <th>Part Name</th>
                                        <th>Unit</th>
                                        <th class="text-end">Beginning Balance</th>
                                        <th class="text-end">Ending Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data as $index => $item)
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>{{ $item->Period ?? '-' }}</td>
                                            <td>{{ $item->CategoryName ?? '-' }}</td>
                                            <td>{{ $item->PartID ?? '-' }}</td>
                                            <td>{{ $item->PartName ?? '-' }}</td>
                                            <td>{{ $item->UnitID1 ?? '-' }}</td>
                                            <td class="text-end">{{ auto_numeric_format($item->BeginningBalance) }}</td>
                                            <td class="text-end">{{ auto_numeric_format($item->EndingBalance) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <i class="fa fa-fw fa-inbox fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No data available for the selected period</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if(count($data) > 0)
                                    <tfoot>
                                        <tr>
                                            <td colspan="6" class="text-end fw-bold">GRAND TOTAL:</td>
                                            <td class="text-end fw-bold">
                                                {{ auto_numeric_format($summary['total_beginning']) }}
                                            </td>
                                            <td class="text-end fw-bold">
                                                {{auto_numeric_format($summary['total_ending']) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Report Footer -->
                <div class="block block-rounded mt-4">
                    <div class="block-content text-center text-muted small">
                        <p>Report generated on {{ date('d F Y H:i:s') }} by {{ auth()->user()->name ?? 'System' }}</p>
                        <p>Keyonline Inventory System</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        @media print {
            .no-print,
            .btn,
            .block-header,
            .block-options {
                display: none !important;
            }

            .table {
                font-size: 11px;
            }

            .table th,
            .table td {
                padding: 4px 8px;
            }

            .block-rounded {
                border: none !important;
                box-shadow: none !important;
            }

            .block-content {
                padding: 0 !important;
            }
        }

        .table th {
            background-color: #f8f9fa;
            white-space: nowrap;
        }

        .table td.text-end {
            text-align: right;
        }

        .bg-primary { background-color: #3498db !important; }
        .bg-success { background-color: #2ecc71 !important; }
    </style>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function exportToExcel() {
            // Prepare data for Excel
            const data = [
                ['Cost of Inventory Report - {{ $summary['period'] }}'],
                ['Generated on: {{ date('d F Y H:i:s') }}'],
                [''],
                ['No', 'Period', 'Category', 'Part ID', 'Part Name', 'Unit', 'Beginning Balance', 'Ending Balance']
            ];

            // Add rows
            @foreach($data as $index => $item)
                data.push([
                    {{ $index + 1 }},
                    '{{ $item->Period ?? '' }}',
                    '{{ $item->CategoryName ?? '' }}',
                    '{{ $item->PartID ?? '' }}',
                    '{{ $item->PartName ?? '' }}',
                    '{{ $item->UnitID1 ?? '' }}',
                    {{ $item->BeginningBalance }},
                    {{ $item->EndingBalance }}
                ]);
            @endforeach

            // Add total row
            data.push([
                '', '', '', '', '', 'TOTAL',
                {{ $summary['total_beginning'] }},
                {{ $summary['total_ending'] }}
            ]);

            // Create worksheet
            const ws = XLSX.utils.aoa_to_sheet(data);

            // Set column widths
            const wscols = [
                {wch: 5},   // No
                {wch: 10},  // Period
                {wch: 20},  // Category
                {wch: 15},  // Part ID
                {wch: 30},  // Part Name
                {wch: 10},  // Unit
                {wch: 15},  // Beginning Balance
                {wch: 15}   // Ending Balance
            ];
            ws['!cols'] = wscols;

            // Merge title cells
            if (!ws['!merges']) ws['!merges'] = [];
            ws['!merges'].push({s: {r: 0, c: 0}, e: {r: 0, c: 7}});
            ws['!merges'].push({s: {r: 1, c: 0}, e: {r: 1, c: 7}});

            // Create workbook
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Cost of Inventory');

            // Generate filename
            const fileName = `Cost_of_Inventory_{{ date('Y_m_d_His') }}.xlsx`;

            // Download
            XLSX.writeFile(wb, fileName);
        }
    </script>
@endsection
