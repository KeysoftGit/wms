<?php

namespace App\Http\Controllers\Stock;

use App\Helpers\BukuStockHelper;
use App\Services\WarehouseAccessCriteria;
use App\Exports\StockReportExport;
use App\Http\Controllers\Controller;
use App\Models\ControlPanel;
use App\Models\MsPart;
use App\Models\MsWarehouse;
use App\Models\StockReport;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class StockReportController extends Controller
{
    public function index()
    {
        return view('stock.stock_report.index');
    }

    public function export(Request $request)
    {
        $data = StockReport::with(['part.specification', 'unit', 'warehouse.division']);
        WarehouseAccessCriteria::apply($data);

        // Filter by date range
        if ($request->filled('date')) {
            try {
                $dates = explode(' to ', $request->get('date'));
                if (count($dates) > 1) {
                    $startDate = \DateTime::createFromFormat('d/m/Y', trim($dates[0]));
                    $endDate = \DateTime::createFromFormat('d/m/Y', trim($dates[1]));
                    if ($startDate && $endDate) {
                        $data->whereDate('Date', '>=', $startDate->format('Y-m-d'))
                            ->whereDate('Date', '<=', $endDate->format('Y-m-d'));
                    }
                } else {
                    $singleDate = \DateTime::createFromFormat('d/m/Y', trim($dates[0]));
                    if ($singleDate) {
                        $data->whereDate('Date', $singleDate->format('Y-m-d'));
                    }
                }
            } catch (\Exception $e) {
            }
        }

        if ($request->filled('WarehouseID')) {
            $warehouseId = $request->get('WarehouseID');
            $data->where('WarehouseID', $warehouseId);
        }

        $results = $data->get();

        return Excel::download(new StockReportExport($results), 'Stock_Report_' . date('d_m_Y_H_i_s') . '.xlsx');
    }

    public function datatable(Request $request)
    {
        $data = StockReport::with(['part.specification', 'unit', 'warehouse.division']);
        WarehouseAccessCriteria::apply($data);

        // Filter by date range with error handling
        if ($request->filled('date')) {
            try {
                $dates = explode(' to ', $request->get('date'));

                if (count($dates) > 1) {
                    $startDate = \DateTime::createFromFormat('d/m/Y', trim($dates[0]));
                    $endDate = \DateTime::createFromFormat('d/m/Y', trim($dates[1]));

                    if ($startDate && $endDate) {
                        $data->whereDate('Date', '>=', $startDate->format('Y-m-d'))
                            ->whereDate('Date', '<=', $endDate->format('Y-m-d'));
                    }
                } else {
                    $singleDate = \DateTime::createFromFormat('d/m/Y', trim($dates[0]));
                    if ($singleDate) {
                        $data->whereDate('Date', $singleDate->format('Y-m-d'));
                    }
                }
            } catch (\Exception $e) {
                // Invalid date format, skip filter
                Log::warning('Invalid date format in stock report filter: ' . $request->get('date'));
            }
        }

        if ($request->filled('WarehouseID')) {
            $warehouseId = $request->get('WarehouseID');
            $data->where('WarehouseID', $warehouseId);
        }

        $dt = DataTables::of($data)
            ->addIndexColumn()
            ->filterColumn('part', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('Stock_Report.PartID', 'like', "%{$keyword}%")
                        ->orWhereHas('part', function ($sq) use ($keyword) {
                            $sq->where('PartName', 'like', "%{$keyword}%");
                        });
                });
            })
            ->addColumn('part_id', function ($row) {
                return $row->PartID;
            })
            ->addColumn('part_name', function ($row) {
                return $row->part->PartName ?? '-';
            })
            ->addColumn('unit', function ($row) {
                return $row->UnitID . '-' . ($row->unit->UnitName ?? '-');
            })
            ->addColumn('warehouse', function ($row) {
                return $row->WarehouseID . '-' . ($row->warehouse->WarehouseName ?? '-');
            })
            ->editColumn('Qty', function ($row) {
                return auto_numeric_format($row->Qty);
            })
            ->editColumn('Conversion', function ($row) {
                return auto_numeric_format($row->Conversion);
            })
            ->addColumn('pic', function ($row) {
                return $row->LastUpdateBy ?? '-';
            })
            ->addColumn('input_date', function ($row) {
                return $row->updated_at ? $row->updated_at->format('d/m/Y H:i:s') : '-';
            });

        return $dt->addColumn('action', function ($row) {
            $btn = '<div class="btn-group">';
            if (Auth::user()->hasAnyPermission(['admin', 'stock_report.edit'])) {
                $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled"
                                data-bs-toggle="tooltip" title="Edit"
                                href="' . route('stock_report.edit', $row->id) . '">
                                <i class="fa fa-fw fa-edit"></i>
                            </a>';
            }
            if (Auth::user()->hasAnyPermission(['admin', 'stock_report.delete'])) {
                $btn .= '<button class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled delete-btn"
                                data-bs-toggle="tooltip" title="Delete"
                                data-url="' . route('stock_report.delete', $row->id) . '">
                                <i class="fa fa-fw fa-trash"></i>
                            </button>';
            }

            $btn .= '</div>';
            return $btn;
        })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function add(Request $request)
    {
        return view('stock.stock_report.add');
    }

    public function store(Request $request)
    {
        $request->validate([
            'PartID' => 'required|string|exists:Ms_Part,PartID',
            'UnitID' => 'required|string|exists:Ms_Unit,UnitID',
            'WarehouseID' => 'required|string|exists:Ms_Warehouse,WarehouseID',
            'date' => 'required|date_format:d/m/Y',
            'Qty' => 'required|numeric|min:0',
            'Conversion' => 'required|numeric|min:0',
            'Notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $formattedDate = Carbon::createFromFormat('d/m/Y', $request->date)->format('Y-m-d');

            $exists = StockReport::where('PartID', $request->PartID)
                ->where('WarehouseID', $request->WarehouseID)
                ->where('Date', $formattedDate)
                ->where('Notes', $request->Notes)
                ->exists();

            if ($exists) {
                DB::rollBack();
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['date' => "Stock report for Part {$request->PartID} in this warehouse has already been recorded for this date."]);
            }

            $QtyInventory = 0;

            $isPartIdReported = StockReport::where('PartID', $request->PartID)
                ->where('WarehouseID', $request->WarehouseID)
                ->where('Date', $formattedDate)
                ->first();

            if ($isPartIdReported) {
                $QtyInventory = $isPartIdReported->QtyInventory;
            } else {
                $QtyInventory = BukuStockHelper::calculateCurrentStock($request->PartID, $request->WarehouseID);
            }

            StockReport::create([
                'PartID' => $request->PartID,
                'UnitID' => $request->UnitID,
                'WarehouseID' => $request->WarehouseID,
                'Date' => $formattedDate,
                'Qty' => $request->Qty,
                'Conversion' => $request->Conversion,
                'CreatedBy' => Auth::user()->UserID,
                'LastUpdateBy' => Auth::user()->UserID,
                'Notes' => $request->Notes,
                'QtyInventory' => $QtyInventory,
            ]);

            DB::commit();

            return redirect()->route('stock_report')->with('success', 'Stock Report successfully created!');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed Add Stock Report: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->withErrors(['msg' => 'Failed to create stock report: ' . $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $report = StockReport::findOrFail($id);
        return view('stock.stock_report.edit', compact('report'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'UnitID' => 'required|string|exists:Ms_Unit,UnitID',
            'WarehouseID' => 'required|string|exists:Ms_Warehouse,WarehouseID',
            'Qty' => 'required|numeric|min:0',
            'Conversion' => 'required|numeric|min:0',
            'Notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $report = StockReport::findOrFail($id);

            $exists = StockReport::where('PartID', $report->PartID)
                ->where('WarehouseID', $request->WarehouseID)
                ->where('Date', $report->Date)
                ->where('Notes', $request->Notes)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                DB::rollBack();
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['msg' => "Stock report for Part {$report->PartID} in this warehouse already exists for this date."]);
            }

            $report->update([
                'UnitID' => $request->UnitID,
                'WarehouseID' => $request->WarehouseID,
                'Qty' => $request->Qty,
                'Conversion' => $request->Conversion,
                'LastUpdateBy' => Auth::user()->UserID,
                'Notes' => $request->Notes
            ]);

            DB::commit();

            return redirect()->route('stock_report')->with('success', 'Stock Report successfully updated!');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed Update Stock Report: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->withErrors(['msg' => 'Failed to update stock report: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $report = StockReport::findOrFail($id);
                $report->delete();
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Stock report deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete stock report: ' . $e->getMessage());

            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to delete stock report'
            ], 500);
        }
    }

    public function summary(Request $request)
    {
        return view('stock.stock_report.summary');
    }

    public function summaryDatatable(Request $request)
    {
        $data = StockReport::with(['part.specification', 'part.units.unit1', 'warehouse'])
            ->select(
                'Stock_Report.Date',
                'Stock_Report.PartID',
                'Stock_Report.WarehouseID',
                DB::raw('SUM(Stock_Report.Qty * Stock_Report.Conversion) as Qty'),
                DB::raw('COUNT(*) as TotalCount'),
                DB::raw('MAX(Stock_Report.QtyInventory) as QtyInventory')
            )
            ->groupBy(['Stock_Report.Date', 'Stock_Report.PartID', 'Stock_Report.WarehouseID']);
        WarehouseAccessCriteria::apply($data, 'Stock_Report.WarehouseID');

        // Filter by date range
        if ($request->filled('date')) {
            try {
                $dates = explode(' to ', $request->get('date'));
                if (count($dates) > 1) {
                    $startDate = \DateTime::createFromFormat('d/m/Y', trim($dates[0]))->format('Y-m-d');
                    $endDate = \DateTime::createFromFormat('d/m/Y', trim($dates[1]))->format('Y-m-d');
                    $data->whereDate('Stock_Report.Date', '>=', $startDate)->whereDate('Stock_Report.Date', '<=', $endDate);
                } else {
                    $startDate = \DateTime::createFromFormat('d/m/Y', trim($dates[0]))->format('Y-m-d');
                    $data->whereDate('Stock_Report.Date', $startDate);
                }
            } catch (\Exception $e) {
            }
        }

        $warehouseId =  null;
        if ($request->filled('WarehouseID')) {
            $warehouseId = $request->get('WarehouseID');
            $data->where('Stock_Report.WarehouseID', $warehouseId);
        }

        $dt = DataTables::of($data)
            ->addIndexColumn()
            ->filterColumn('part', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('Stock_Report.PartID', 'like', "%{$keyword}%")
                        ->orWhereHas('part', function ($sq) use ($keyword) {
                            $sq->where('PartName', 'like', "%{$keyword}%");
                        });
                });
            })
            ->addColumn('date_display', function ($row) {
                return date('d/m/Y', strtotime($row->Date));
            })
            ->addColumn('part_id', function ($row) {
                return $row->PartID;
            })
            ->addColumn('part_name', function ($row) {
                return $row->part->PartName ?? '-';
            })
            ->addColumn('satuan', function ($row) {
                $unit = $row->part->units->sortBy('Conversion')->first();
                if ($unit && $unit->unit1) {
                    return $unit->unit1->UnitID . ' - ' . $unit->unit1->UnitName;
                }
                return '-';
            })
            ->addColumn('warehouse_display', function ($row) {
                return $row->WarehouseID . ' - ' . ($row->warehouse->WarehouseName ?? '-');
            })
            ->editColumn('Qty', function ($row) {
                return auto_numeric_format($row->Qty);
            })
            ->addColumn('inventory', function ($row) {
                return auto_numeric_format($row->QtyInventory ?? 0);
            })
            ->addColumn('inventory_diff', function ($row) {
                $inventoryQty = $row->QtyInventory ?? 0;
                $systemQty = $row->Qty;
                $diff = $systemQty - $inventoryQty;
                $formatted = auto_numeric_format($diff);
                return $diff < 0 ? '<span class="text-danger">' . $formatted . '</span>' : $formatted;
            })
            ->addColumn('all_notes', function ($row) {
                $notes = StockReport::where('PartID', $row->PartID)
                    ->where('WarehouseID', $row->WarehouseID)
                    ->whereDate('Date', $row->Date)
                    ->whereNotNull('Notes')
                    ->pluck('Notes')
                    ->toArray();
                return count($notes) > 0 ? implode('#', $notes) . '#' : '-';
            })
            ->addColumn('input_count', function ($row) {
                return auto_numeric_format($row->TotalCount, 0);
            });

        return $dt->rawColumns(['inventory_diff'])->make(true);
    }

    public function summaryExport(Request $request)
    {
        $data = StockReport::with(['part.specification', 'part.units.unit1', 'warehouse'])
            ->select(
                'Stock_Report.Date',
                'Stock_Report.PartID',
                'Stock_Report.WarehouseID',
                DB::raw('SUM(Stock_Report.Qty * Stock_Report.Conversion) as Qty'),
                DB::raw('COUNT(*) as TotalCount'),
                DB::raw('MAX(Stock_Report.QtyInventory) as QtyInventory')
            )
            ->groupBy(['Stock_Report.Date', 'Stock_Report.PartID', 'Stock_Report.WarehouseID']);
        WarehouseAccessCriteria::apply($data, 'Stock_Report.WarehouseID');

        if ($request->filled('date')) {
            try {
                $dates = explode(' to ', $request->get('date'));
                if (count($dates) > 1) {
                    $startDate = \DateTime::createFromFormat('d/m/Y', trim($dates[0]))->format('Y-m-d');
                    $endDate = \DateTime::createFromFormat('d/m/Y', trim($dates[1]))->format('Y-m-d');
                    $data->whereDate('Stock_Report.Date', '>=', $startDate)->whereDate('Stock_Report.Date', '<=', $endDate);
                } else {
                    $startDate = \DateTime::createFromFormat('d/m/Y', trim($dates[0]))->format('Y-m-d');
                    $data->whereDate('Stock_Report.Date', $startDate);
                }
            } catch (\Exception $e) {
            }
        }

        $warehouseId =  null;
        if ($request->filled('WarehouseID')) {
            $warehouseId = $request->get('WarehouseID');
            $data->where('Stock_Report.WarehouseID', $warehouseId);
        }

        $results = $data->get();

        foreach ($results as $row) {
            $row->inventory_value = $row->QtyInventory ?? 0;
            $row->inventory_diff_value = $row->Qty - ($row->QtyInventory ?? 0);

            // Satuan
            $unit = $row->part->units->sortBy('Conversion')->first();
            $row->satuan_value = ($unit && $unit->unit1) ? $unit->unit1->UnitID . ' - ' . $unit->unit1->UnitName : '-';

            // Notes
            $notes = StockReport::where('PartID', $row->PartID)
                ->where('WarehouseID', $row->WarehouseID)
                ->whereDate('Date', $row->Date)
                ->whereNotNull('Notes')
                ->pluck('Notes')
                ->toArray();
            $row->all_notes_value = count($notes) > 0 ? implode('#', $notes) . '#' : '-';
        }

        return Excel::download(new \App\Exports\StockReportSummaryExport($results), 'Stock_Report_Summary_' . date('d_m_Y_H_i_s') . '.xlsx');
    }
}
