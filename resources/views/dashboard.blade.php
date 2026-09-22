@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Dashboard</title>
@endsection

@section('styles')
    <!-- Select2 CSS -->
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        /* Modern Premium Styles */
        .block-header-default {
            background-color: #fff;
            border-bottom: 1px solid #ebebeb;
        }
        .block.block-rounded {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.04);
            border: 1px solid #f0f0f0;
        }
        .content-heading {
            border-bottom: 1px solid #e1e1e1;
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        
        /* Stats Card Refinement */
        .stat-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
        }
        .icon-box {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .bg-primary-soft { background-color: rgba(78, 115, 223, 0.1); color: #4e73df; }
        .bg-success-soft { background-color: rgba(28, 200, 138, 0.1); color: #1cc88a; }
        .bg-info-soft { background-color: rgba(54, 185, 204, 0.1); color: #36b9cc; }
        .bg-warning-soft { background-color: rgba(246, 194, 62, 0.1); color: #f6c23e; }
        
        .trend-label {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.15rem 0.5rem;
            border-radius: 100px;
        }
        
        .chart-container {
            position: relative;
            height: 340px;
            width: 100%;
        }
        
        /* Custom Scrollbar for tables */
        .table-responsive {
            max-height: 400px;
            scrollbar-width: thin;
        }
    </style>
@endsection

@section('content')
    <div class="content content-narrow">
        <!-- Hero Section -->
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">System Overview</h2>
                <p class="text-muted mb-0">Operational summary for {{ date('d M Y', strtotime($start_date)) }} - {{ date('d M Y', strtotime($end_date)) }}</p>
            </div>
            <div class="mt-3 mt-sm-0">
                <button type="button" class="btn btn-white btn-sm px-3 shadow-sm border" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                    <i class="fa fa-filter me-1"></i> Filter Data
                </button>
            </div>
        </div>

        <!-- Collapsible Filter -->
        <div class="collapse mb-4" id="filterCollapse">
            <div class="block block-rounded shadow-sm">
                <div class="block-content block-content-full bg-body-light">
                    <form id="filter-form" action="{{ route('dashboard') }}" method="GET">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-uppercase">Start Date</label>
                                <input type="date" name="start_date" class="form-control form-control-alt border" value="{{ $start_date }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-uppercase">End Date</label>
                                <input type="date" name="end_date" class="form-control form-control-alt border" value="{{ $end_date }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Warehouse</label>
                                <select name="warehouse_id" class="form-select select2">
                                    <option value="">All Warehouses</option>
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->WarehouseID }}" {{ $warehouse_id == $warehouse->WarehouseID ? 'selected' : '' }}>
                                            {{ $warehouse->WarehouseName }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100 px-4">Apply</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Premium Stats Cards -->
        <div class="row">
            <!-- Total Transactions -->
            <div class="col-6 col-xl-3">
                <div class="block block-rounded stat-card">
                    <div class="block-content block-content-full">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="icon-box bg-primary-soft">
                                <i class="si si-refresh"></i>
                            </div>
                            <div class="trend-label bg-success-soft text-success">
                                <i class="fa fa-caret-up me-1"></i> {{ $main_stats['trends']['transactions'] }}
                            </div>
                        </div>
                        <div class="fs-sm fw-semibold text-muted text-uppercase mb-1">Transactions</div>
                        <div class="fs-2 fw-bold text-dark">{{ number_format($main_stats['total_transactions']) }}</div>
                    </div>
                </div>
            </div>
            <!-- Active SKUs -->
            <div class="col-6 col-xl-3">
                <div class="block block-rounded stat-card">
                    <div class="block-content block-content-full">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="icon-box bg-success-soft">
                                <i class="si si-layers"></i>
                            </div>
                            <div class="trend-label bg-body-light text-muted">
                                {{ $main_stats['trends']['parts'] }} new
                            </div>
                        </div>
                        <div class="fs-sm fw-semibold text-muted text-uppercase mb-1">Active SKUs</div>
                        <div class="fs-2 fw-bold text-dark">{{ number_format($main_stats['total_parts']) }}</div>
                    </div>
                </div>
            </div>
            <!-- Active Users -->
            <div class="col-6 col-xl-3">
                <div class="block block-rounded stat-card">
                    <div class="block-content block-content-full">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="icon-box bg-info-soft">
                                <i class="si si-users"></i>
                            </div>
                            <div class="trend-label bg-body-light text-muted">
                                Static
                            </div>
                        </div>
                        <div class="fs-sm fw-semibold text-muted text-uppercase mb-1">Operators</div>
                        <div class="fs-2 fw-bold text-dark">{{ number_format($main_stats['total_users']) }}</div>
                    </div>
                </div>
            </div>
            <!-- Goods Receiving -->
            <div class="col-6 col-xl-3">
                <div class="block block-rounded stat-card">
                    <div class="block-content block-content-full">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="icon-box bg-warning-soft">
                                <i class="si si-login"></i>
                            </div>
                            <div class="trend-label bg-success-soft text-success">
                                <i class="fa fa-caret-up me-1"></i> {{ $main_stats['trends']['receiving'] }}
                            </div>
                        </div>
                        <div class="fs-sm fw-semibold text-muted text-uppercase mb-1">Stock Inbound</div>
                        <div class="fs-2 fw-bold text-dark">{{ number_format($transaction_summary['STOCK_IN']->total_transactions) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Chart Section -->
            <div class="col-xl-8">
                <div class="block block-rounded" id="chart-block">
                    <div class="block-header">
                        <h3 class="block-title fw-bold">
                            Flow Trends <span class="fs-sm fw-medium text-muted ms-2">Inbound vs Outbound vs Transfer</span>
                        </h3>
                        <div class="block-options">
                            <button type="button" class="btn-block-option" onclick="loadChartData()">
                                <i class="si si-refresh"></i>
                            </button>
                        </div>
                    </div>
                    <div class="block-content block-content-full bg-white">
                        <div class="chart-container">
                            <canvas id="transactionChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Secondary Activities -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="block block-rounded">
                            <div class="block-header block-header-default">
                                <h3 class="block-title fs-sm fw-bold text-uppercase">
                                    <i class="fa fa-circle text-success me-2"></i> Recent Inbound
                                </h3>
                            </div>
                            <div class="block-content p-0">
                                <table class="table table-hover table-vcenter fs-sm mb-0">
                                    <tbody class="border-top-0">
                                        @foreach($recent_stock_ins as $in)
                                        <tr>
                                            <td class="fw-bold">{{ $in->TransactionNo }}</td>
                                            <td class="text-muted">{{ date('d/m', strtotime($in->TransactionDate)) }}</td>
                                            <td class="text-end"><span class="badge bg-primary-soft">{{ $in->WarehouseName }}</span></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="block block-rounded">
                            <div class="block-header block-header-default">
                                <h3 class="block-title fs-sm fw-bold text-uppercase">
                                    <i class="fa fa-circle text-danger me-2"></i> Recent Outbound
                                </h3>
                            </div>
                            <div class="block-content p-0">
                                <table class="table table-hover table-vcenter fs-sm mb-0">
                                    <tbody>
                                        @foreach($recent_stock_outs as $out)
                                        <tr>
                                            <td class="fw-bold">{{ $out->TransactionNo }}</td>
                                            <td class="text-muted">{{ date('d/m', strtotime($out->TransactionDate)) }}</td>
                                            <td class="text-end"><i class="fa fa-external-link-alt text-muted opacity-50"></i></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Side Panels -->
            <div class="col-xl-4">
                <!-- Breakdown Table -->
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title fs-sm fw-bold text-uppercase">Transaction Summary</h3>
                    </div>
                    <div class="block-content p-0">
                        <div class="list-group list-group-flush">
                            @foreach($transaction_summary as $type => $summary)
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div class="fs-sm fw-medium text-muted">
                                    {{ ucwords(strtolower(str_replace('_', ' ', $type))) }}
                                </div>
                                <div class="fs-6 fw-bold text-dark">
                                    {{ number_format($summary->total_transactions) }}
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Stock Alerts -->
                <div class="block block-rounded">
                    <div class="block-header border-bottom">
                        <h3 class="block-title fs-sm fw-bold text-uppercase text-danger">Inventory Alerts</h3>
                    </div>
                    <div class="block-content">
                        @foreach($low_stocks as $stock)
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-grow-1">
                                <div class="fw-bold fs-sm mb-0 text-dark">{{ $stock->PartID }}</div>
                                <div class="fs-xs text-muted text-truncate" style="max-width: 180px;">{{ $stock->PartName }}</div>
                            </div>
                            <div class="text-end">
                                <div class="fs-sm fw-bold text-danger">{{ number_format($stock->current_stock) }}</div>
                                <div class="fs-xs text-muted">Limit: {{ number_format($stock->MinimumStockBuffer) }}</div>
                            </div>
                        </div>
                        @endforeach
                        @if(count($low_stocks) == 0)
                        <div class="text-center py-4">
                            <i class="si si-check fa-2x text-success mb-2"></i>
                            <p class="fs-sm text-muted">Inventory levels optimal</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        $(document).ready(function() {
            if ($.isFunction($.fn.select2)) {
                $('.select2').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });
            }

            const ctx = document.getElementById('transactionChart').getContext('2d');
            let myChart;

            // Global Chart Defaults
            Chart.defaults.font.family = "'Inter', sans-serif";
            Chart.defaults.color = '#858796';

            window.loadChartData = function() {
                const formData = $('#filter-form').serialize();
                
                // Start Loading
                if (typeof One !== 'undefined') {
                    One.block('state_loading', '#chart-block');
                }

                $.ajax({
                    url: '{{ route('dashboard.chart_data') }}',
                    method: 'GET',
                    data: formData,
                    success: function(data) {
                        // Stop Loading
                        if (typeof One !== 'undefined') {
                            One.block('state_normal', '#chart-block');
                        }

                        if (myChart) {
                            myChart.destroy();
                        }
                        
                        // Refine chart datasets for a more "Premium" look
                        data.datasets.forEach((dataset, i) => {
                            dataset.borderWidth = 3;
                            dataset.pointRadius = 3;
                            dataset.pointHoverRadius = 5;
                            dataset.pointBackgroundColor = '#fff';
                            dataset.pointBorderWidth = 2;
                            
                            // Modern Smoothing
                            dataset.tension = 0.4; 
                            dataset.cubicInterpolationMode = 'monotone'; // Premium interpolation mode
                            
                            // Add soft gradient fill
                            dataset.fill = true;
                            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                            gradient.addColorStop(0, dataset.borderColor + '22'); // 13% opacity
                            gradient.addColorStop(1, dataset.borderColor + '00'); // 0% opacity
                            dataset.backgroundColor = gradient;
                        });

                        myChart = new Chart(ctx, {
                            type: 'line',
                            data: data,
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    intersect: false,
                                    mode: 'index',
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            drawBorder: false,
                                            color: '#f0f0f0'
                                        },
                                        ticks: {
                                            stepSize: 5
                                        }
                                    },
                                    x: {
                                        grid: {
                                            display: false
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        position: 'top',
                                        align: 'end',
                                        labels: {
                                            usePointStyle: true,
                                            boxWidth: 8,
                                            padding: 20,
                                            font: { size: 11, weight: '600' }
                                        }
                                    },
                                    tooltip: {
                                        backgroundColor: '#fff',
                                        titleColor: '#1a1a1a',
                                        bodyColor: '#666',
                                        borderColor: '#e1e1e1',
                                        borderWidth: 1,
                                        padding: 12,
                                        cornerRadius: 8,
                                        displayColors: true,
                                        bodyFont: { size: 13 },
                                        titleFont: { size: 14, weight: 'bold' }
                                    }
                                }
                            }
                        });
                    }
                });
            }

            loadChartData();
        });
    </script>
@endsection
