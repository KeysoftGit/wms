<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: {{ $report->ReportName }}</title>
    <link rel="stylesheet" href="{{ asset('css/oneui.min.css') }}">
    <style>
        body { background: #525659; padding: 20px; }
        .page-container {
            background: #fff;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,.4);
        }
        .toolbar {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            display: flex;
            gap: 8px;
        }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; }
        th { background: #f1f5f9; }
        @media print {
            body { background: none; padding: 0; }
            .toolbar { display: none; }
            .page-container { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn btn-dark btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
        <button class="btn btn-alt-danger btn-sm" onclick="window.close()"><i class="fa fa-times me-1"></i> Close</button>
    </div>

    <div class="page-container">
        <h2 class="h4 fw-bold mb-3">{{ $report->ReportName }}</h2>
        <table>
            <thead>
                <tr>
                    @foreach($report->columns as $column)
                        <th>{{ $column->Label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($data as $row)
                    <tr>
                        @foreach($report->columns as $column)
                            <td>{{ $row->{$column->FieldName} ?? '-' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $report->columns->count() }}" class="text-center text-muted">No data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
