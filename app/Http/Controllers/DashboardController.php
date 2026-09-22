<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ControlPanel;
use App\Models\MsWarehouse;
use App\Services\WarehouseAccessCriteria;

class DashboardController extends Controller
{
    private $transactionConfig = [
        'STOCK_IN' => [
            'table' => 'Trans_GoodsReceivingHD',
            'label' => 'Goods Receiving',
            'color' => '#4e73df',
            'date_field' => 'TransactionDate', 
            'warehouse_field' => 'WarehouseID'
        ],
        'STOCK_OUT' => [
            'table' => 'Trans_DeliveryOrderHD',
            'label' => 'Delivery Order',
            'color' => '#e74a3b',
            'date_field' => 'TransactionDate', 
            'warehouse_field' => null // Usually in DT
        ],
        'ITEM_TRANSFER' => [
            'table' => 'Trans_DirectItemTransferHD',
            'label' => 'Item Transfer',
            'color' => '#6f42c1',
            'date_field' => 'TransactionDate',
            'warehouse_field' => ['WarehouseIDFrom', 'WarehouseIDTo']
        ],
        'TRANSFER_REQUEST' => [
            'table' => 'Trans_ItemTransferRequestHD',
            'label' => 'Transfer Request',
            'color' => '#f6c23e',
            'date_field' => 'TransactionDate',
            'warehouse_field' => ['WarehouseIDFrom', 'WarehouseIDTo']
        ],
        'TRANSFER_EXECUTE' => [
            'table' => 'Trans_ItemTransferExecuteHD',
            'label' => 'Transfer Execute',
            'color' => '#1cc88a',
            'date_field' => 'TransactionDate',
            'warehouse_field' => null
        ],
        'TRANSFER_RECEIVE' => [
            'table' => 'Trans_ItemTransferReceiveHD',
            'label' => 'Transfer Receive',
            'color' => '#36b9cc',
            'date_field' => 'TransactionDate',
            'warehouse_field' => null
        ],
        'STOCK_USAGE' => [
            'table' => 'Trans_PartUsageHD',
            'label' => 'Part Usage',
            'color' => '#858796',
            'date_field' => 'TransactionDate',
            'warehouse_field' => null // Column is in DT, not HD
        ],
        'STOCK_ADJUSTMENT' => [
            'table' => 'Trans_InventoryAdjustmentHD',
            'label' => 'Adjustment',
            'color' => '#5a5c69',
            'date_field' => 'TransactionDate',
            'warehouse_field' => 'WarehouseID'
        ],
        'STOCK_OPNAME' => [
            'table' => 'Trans_StockOpnameHD',
            'label' => 'Stock Opname',
            'color' => '#b7b9cc',
            'date_field' => 'TransactionDate',
            'warehouse_field' => 'WarehouseID'
        ],
    ];

    private function warehouseFilter(?string $requestedWarehouse)
    {
        $allowedIds = WarehouseAccessCriteria::allowedIds();

        if ($allowedIds === null) {
            return $requestedWarehouse;
        }

        if ($requestedWarehouse) {
            abort_unless(in_array($requestedWarehouse, $allowedIds, true), 403);

            return $requestedWarehouse;
        }

        return $allowedIds;
    }

    private function applyWarehouseFilter($query, string|array $fields, string|array $warehouseIds): void
    {
        $fields = (array) $fields;
        $warehouseIds = (array) $warehouseIds;

        $query->where(function ($warehouseQuery) use ($fields, $warehouseIds) {
            foreach ($fields as $field) {
                $warehouseQuery->whereIn($field, $warehouseIds);
            }
        });
    }

    public function index(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $warehouseId = $request->get('warehouse_id');
        $warehouseFilter = $this->warehouseFilter($warehouseId);

        $data = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'warehouse_id' => $warehouseId,
            'warehouses' => (
                ControlPanel::isEnabled('implement_user_warehouse_mapping')
                    ? MsWarehouse::accessibleTo(auth()->user())
                    : MsWarehouse::query()
            )->get(),

            'transaction_summary' => $this->getTransactionSummary($startDate, $endDate, $warehouseFilter),
            'main_stats' => $this->getMainStats($startDate, $endDate, $warehouseFilter),

            'top_stocks' => $this->getTopStocks($warehouseFilter),
            'low_stocks' => $this->getLowStocks($warehouseFilter),

            'recent_stock_ins' => $this->getRecentStockIns($warehouseFilter),
            'recent_stock_outs' => $this->getRecentStockOuts($warehouseFilter),
        ];

        return view('dashboard', $data);
    }

    private function getTransactionSummary($startDate, $endDate, $warehouseId = null)
    {
        $results = [];

        foreach ($this->transactionConfig as $type => $config) {
            $query = DB::table($config['table'])
                ->select(DB::raw('COUNT(*) as total_transactions'))
                ->whereBetween(DB::raw("CAST(" . $config['date_field'] . " AS DATE)"), [$startDate, $endDate]);

            if ($warehouseId && $config['warehouse_field']) {
                $this->applyWarehouseFilter($query, $config['warehouse_field'], $warehouseId);
            }

            $count = $query->first()->total_transactions ?? 0;

            $results[$type] = (object)[
                'total_transactions' => $count,
            ];
        }

        return $results;
    }

    private function getMainStats($startDate, $endDate, $warehouseId = null)
    {
        $totalParts = DB::table('Ms_Part')->where('Active', 1)->count();
        $totalUsers = DB::table('Ms_User')->where('Active', 1)->count();

        $totalTransactions = 0;
        foreach ($this->transactionConfig as $type => $config) {
            $query = DB::table($config['table'])
                ->whereBetween(DB::raw("CAST(" . $config['date_field'] . " AS DATE)"), [$startDate, $endDate]);
            
            if ($warehouseId && $config['warehouse_field']) {
                $this->applyWarehouseFilter($query, $config['warehouse_field'], $warehouseId);
            }
            $totalTransactions += $query->count();
        }

        return [
            'total_parts' => $totalParts,
            'total_users' => $totalUsers,
            'total_transactions' => $totalTransactions,
            'trends' => [
                'transactions' => '+12.5%', 
                'parts' => '+3',
                'users' => '0',
                'receiving' => '+5.2%'
            ]
        ];
    }

    private function getTopStocks($warehouseId = null)
    {
        $query = DB::table('Ms_Part as p')
            ->join('Buku_Stock as bs', 'p.PartID', '=', 'bs.PartID')
            ->select('p.PartID', 'p.PartName', DB::raw('SUM(bs.Qty) as current_stock'))
            ->where('p.Active', 1)
            ->groupBy('p.PartID', 'p.PartName')
            ->orderByDesc(DB::raw('SUM(bs.Qty)'))
            ->limit(10);

        if ($warehouseId) {
            $this->applyWarehouseFilter($query, 'bs.WarehouseID', $warehouseId);
        }

        return $query->get();
    }

    private function getLowStocks($warehouseId = null)
    {
        $query = DB::table('Ms_Part as p')
            ->join('Buku_Stock as bs', 'p.PartID', '=', 'bs.PartID')
            ->select('p.PartID', 'p.PartName', 'p.MinimumStockBuffer', DB::raw('SUM(bs.Qty) as current_stock'))
            ->where('p.Active', 1)
            ->groupBy('p.PartID', 'p.PartName', 'p.MinimumStockBuffer')
            ->havingRaw('SUM(bs.Qty) <= COALESCE(p.MinimumStockBuffer, 0)')
            ->orderBy(DB::raw('SUM(bs.Qty)'))
            ->limit(10);

        if ($warehouseId) {
            $this->applyWarehouseFilter($query, 'bs.WarehouseID', $warehouseId);
        }

        return $query->get();
    }

    private function getRecentStockIns($warehouseId = null)
    {
        $query = DB::table('Trans_GoodsReceivingHD as hd')
            ->join('Ms_Warehouse as w', 'hd.WarehouseID', '=', 'w.WarehouseID')
            ->select('hd.TransactionNo', 'hd.TransactionDate', 'w.WarehouseName')
            ->orderByDesc('hd.TransactionDate')
            ->limit(5);

        if ($warehouseId) {
            $this->applyWarehouseFilter($query, 'hd.WarehouseID', $warehouseId);
        }

        return $query->get();
    }

    private function getRecentStockOuts($warehouseId = null)
    {
        $query = DB::table('Trans_DeliveryOrderHD as hd')
            ->select('hd.TransactionNo', 'hd.TransactionDate')
            ->orderByDesc('hd.TransactionDate')
            ->limit(5);

        if ($warehouseId) {
            $warehouseIds = (array) $warehouseId;
            $query->whereNotExists(function ($detailQuery) use ($warehouseIds) {
                $detailQuery->select(DB::raw(1))
                    ->from('Trans_DeliveryOrderDT as dt')
                    ->whereColumn('dt.TransactionNo', 'hd.TransactionNo')
                    ->where(function ($unauthorizedQuery) use ($warehouseIds) {
                        $unauthorizedQuery->whereNull('dt.WarehouseID')
                            ->orWhereNotIn('dt.WarehouseID', $warehouseIds);
                    });
            });
        }

        return $query->get();
    }

    public function getChartData(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
            $warehouseId = $request->get('warehouse_id');
            $warehouseId = $this->warehouseFilter($warehouseId);

            $dates = [];
            $current = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            while ($current <= $end) {
                $dates[] = $current->format('Y-m-d');
                $current->addDay();
            }

            $chartData = [
                'labels' => array_map(function($d) { return date('d M', strtotime($d)); }, $dates),
                'datasets' => []
            ];

            foreach ($this->transactionConfig as $type => $config) {
                if (in_array($type, ['TRANSFER_REQUEST', 'STOCK_ADJUSTMENT', 'STOCK_OPNAME'])) continue;

                $dailyCounts = [];
                
                $query = DB::table($config['table'])
                    ->select(DB::raw("CAST(" . $config['date_field'] . " AS DATE) as date"), DB::raw('COUNT(*) as count'))
                    ->whereBetween(DB::raw("CAST(" . $config['date_field'] . " AS DATE)"), [$startDate, $endDate]);

                if ($warehouseId && $config['warehouse_field']) {
                    $this->applyWarehouseFilter($query, $config['warehouse_field'], $warehouseId);
                }

                $counts = $query->groupBy(DB::raw("CAST(" . $config['date_field'] . " AS DATE)"))
                    ->pluck('count', 'date')
                    ->toArray();

                foreach ($dates as $date) {
                    $dailyCounts[] = $counts[$date] ?? ($counts[date('Y-m-d', strtotime($date))] ?? 0);
                }

                $chartData['datasets'][] = [
                    'label' => $config['label'],
                    'data' => $dailyCounts,
                    'backgroundColor' => $config['color'],
                    'borderColor' => $config['color'],
                    'fill' => false,
                    'tension' => 0.4
                ];
            }

            return response()->json($chartData);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
