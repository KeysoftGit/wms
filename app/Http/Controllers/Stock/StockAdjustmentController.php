<?php

namespace App\Http\Controllers\Stock;

use App\Helpers\BukuStockHelper;
use App\Helpers\CoilNoHelper;
use App\Helpers\DualQuantityHelper;
use App\Http\Controllers\Controller;
use App\Models\BukuStock;
use App\Models\DocPrint;
use App\Models\MsAutoNumber;
use App\Models\MsPart;
use App\Models\MsPartUnit;
use App\Models\TransInventoryAdjustmentCheckers;
use App\Models\TransInventoryAdjustmentDT;
use App\Models\TransInventoryAdjustmentExecution;
use App\Models\TransInventoryAdjustmentHD;
use App\Models\TransJournalDT;
use App\Models\TransJournalHD;
use App\Models\TransStockOpnameHD;
use App\Services\FormatService;
use App\Services\GeneralService;
use App\Services\InventoryAdjustmentJournalService;
use App\Services\WarehouseAccessCriteria;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class StockAdjustmentController extends Controller
{
    private FormatService $formatService;
    private GeneralService $generalService;

    public function __construct(FormatService $formatService, GeneralService $generalService)
    {
        $this->formatService = $formatService;
        $this->generalService = $generalService;
    }

    public function index()
    {
        $this->guardAdvancedAccess();

        return view('stock.adjustment.index');
    }

    public function datatable(Request $request)
    {
        $this->guardAdvancedAccess();

        $data = TransInventoryAdjustmentHD::select('id', 'TransactionNo', 'TransactionDate', 'WarehouseID', 'StockOpnameNo', 'Notes', 'Editable')->with('warehouse');
        WarehouseAccessCriteria::apply($data);

        if ($request->get('date')) {
            $dates = explode(' to ', $request->get('date'));
            if (count($dates) > 1) {
                $data->whereDate('TransactionDate', '>=', Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d'));
                $data->whereDate('TransactionDate', '<=', Carbon::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d'));
            } else {
                $data->whereDate('TransactionDate', Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d'));
            }
        }

        return DataTables::of($data)
            ->editColumn('TransactionDate', fn ($row) => Carbon::parse($row->TransactionDate)->format('Y-m-d'))
            ->editColumn('WarehouseID', fn ($row) => optional($row->warehouse)->WarehouseName ?? $row->WarehouseID)
            ->addColumn('action', function ($row) {
                $btn = '<div class="btn-group">';
                $btn .= '<a class="btn btn-sm btn-alt-secondary" title="Show" href="' . route('adjust.show', ['id' => $row->id]) . '"><i class="fa fa-fw fa-eye"></i></a>';

                if ($row->Editable == 1) {
                    if (Auth::user()->hasAnyPermission(['admin', 'stock_adj.edit'])) {
                        $btn .= '<a class="btn btn-sm btn-alt-secondary" title="Edit" href="' . route('adjust.edit', ['id' => $row->id]) . '"><i class="fa fa-fw fa-edit"></i></a>';
                    }

                    if (Auth::user()->hasAnyPermission(['admin', 'stock_adj.delete'])) {
                        $btn .= '<button class="btn btn-sm btn-alt-secondary delete-btn" title="Delete" data-url="' . route('adjust.delete', ['id' => $row->id]) . '"><i class="fa fa-fw fa-trash"></i></button>';
                    }
                }

                return $btn . '</div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function add()
    {
        $this->guardAdvancedAccess();

        return view('stock.adjustment.add', [
            'details' => [],
            'checkers' => [],
        ]);
    }

    public function edit($id)
    {
        $this->guardAdvancedAccess();

        $adjust = TransInventoryAdjustmentHD::with('details.part', 'details.unit', 'checkers', 'warehouse')->where('id', $id)->firstOrFail();

        return view('stock.adjustment.edit', [
            'adjust' => $adjust,
            'details' => $this->buildDetailPayload($adjust),
            'checkers' => $this->buildCheckerPayload($adjust),
        ]);
    }

    public function show($id)
    {
        $this->guardAdvancedAccess();

        $adjust = TransInventoryAdjustmentHD::with('details.part', 'details.unit', 'checkers.employee', 'warehouse', 'division', 'type')
            ->where('id', $id)
            ->firstOrFail();

        foreach ($adjust->details as $detail) {
            $stockRow = BukuStock::where('TransactionNo', $adjust->TransactionNo)
                ->where('TransactionType', 'INVENTORY_ADJUSTMENT')
                ->where('PartID', $detail->PartID)
                ->where('WarehouseID', $adjust->WarehouseID);
            $this->applyNullableBukuStockFilter($stockRow, 'BatchNo', $detail->BatchNo);
            $this->applyNullableBukuStockFilter($stockRow, 'SerialNo', $detail->SerialNo);
            $this->applyNullableBukuStockFilter($stockRow, 'ExpDate', $detail->ExpDate);
            $this->applyNullableBukuStockFilter($stockRow, 'BIN', $detail->BIN);
            $this->applyNullableBukuStockFilter($stockRow, 'LOC', $detail->LOC);
            $stockRow = $stockRow->first();
            $detail->QtyStockFormatted = $this->formatService->formatPrice($detail->QtyStock);
            $detail->QtyOpnameFormatted = $this->formatService->formatPrice($detail->QtyOpname);
            $detail->DifferenceFormatted = $this->formatService->formatPrice($detail->QtyOpname - $detail->QtyStock);
            $detail->Qty2Formatted = $stockRow && $stockRow->Qty2 !== null ? $this->formatService->formatPrice(abs((float) $stockRow->Qty2)) : null;
            $detail->UnitID2 = $stockRow->UnitID2 ?? null;
            $detail->ExpDateFormatted = $detail->ExpDate ? Carbon::parse($detail->ExpDate)->format('Y-m-d') : '';
            $detail->CoilNo = $detail->BatchNo ? CoilNoHelper::get($detail->PartID, $detail->BatchNo) : null;
        }

        $options = DocPrint::where('ModuleCode', 'IA')
            ->where('TypeStr', 'print')
            ->get();

        return view('stock.adjustment.show', compact('adjust', 'options'));
    }

    public function getOpname(Request $request)
    {
        $this->guardAdvancedAccess();

        $opnames = TransStockOpnameHD::where('Status', 'ADJUSTED')
            ->when($request->query('search'), function ($query) use ($request) {
                $query->where('TransactionNo', 'like', '%' . $request->query('search') . '%');
            })
            ->orderBy('TransactionNo')
            ->limit(20)
            ->get();

        $data = [];
        foreach ($opnames as $opname) {
            if ($opname->details()->where('Closed', 0)->exists()) {
                continue;
            }

            $data[] = [
                'id' => $opname->TransactionNo,
                'text' => $opname->TransactionNo,
            ];
        }

        if ($request->get('edit')) {
            $edit = TransStockOpnameHD::where('TransactionNo', $request->get('edit'))->first();
            if ($edit) {
                $data[] = [
                    'id' => $edit->TransactionNo,
                    'text' => $edit->TransactionNo,
                ];
            }
        }

        return response($data);
    }

    public function getOpnameDetail(Request $request)
    {
        $this->guardAdvancedAccess();

        $opname = TransStockOpnameHD::with('details.part', 'checkers', 'warehouse')
            ->where('TransactionNo', $request->get('id'))
            ->firstOrFail();

        return response([
            'warehouse' => $opname->WarehouseID,
            'warehouse_name' => $opname->WarehouseID . ($opname->warehouse && $opname->warehouse->WarehouseName ? ' - ' . $opname->warehouse->WarehouseName : ''),
            'details' => $this->buildOpnameDetailPayload($opname),
            'checkers' => $this->buildOpnameCheckerPayload($opname),
        ]);
    }

    public function getStockDetail(Request $request)
    {
        $this->guardAdvancedAccess();

        $partId = $this->generalService->nullableDetailValue($request->get('part_id'));
        $warehouseId = $this->generalService->nullableDetailValue($request->get('warehouse_id'));

        if (!$partId || !$warehouseId) {
            return response()->json(['message' => 'Part and warehouse are required.'], 422);
        }

        $part = MsPart::where('PartID', $partId)->first();
        if (!$part) {
            return response()->json(['message' => "Part {$partId} does not exist."], 422);
        }

        $withSerialNo = false;
        $stockFilters = $this->submittedStockFilters($request->all(), $withSerialNo);
        $stockAttributes = $this->stockFiltersForInsert($stockFilters);

        $stock = BukuStockHelper::calculateCurrentStock(
            $partId,
            $warehouseId,
            $stockFilters['BatchNo'],
            $stockFilters['SerialNo'],
            $stockFilters['ExpDate'],
            $stockFilters['BIN'],
            $stockFilters['LOC']
        );
        $secondaryStock = DualQuantityHelper::currentStockSecondaryQuantity(
            $partId,
            $warehouseId,
            $stockFilters['BatchNo'],
            $stockFilters['SerialNo'],
            $stockFilters['ExpDate'],
            $stockFilters['BIN'],
            $stockFilters['LOC']
        );

        return response([
            'detail' => [
                'id' => generateRandomString(10),
                'PartID' => $partId,
                'PartName' => $part->PartName ?? '',
                'WithSerialNo' => 0,
                'UnitID' => $this->lowestUnit($partId),
                'QtyStock' => (float) $stock,
                'QtyOpname' => (float) $stock,
                'QtyStock2' => $secondaryStock['qty2'] !== null ? (float) $secondaryStock['qty2'] : null,
                'UnitID2' => $secondaryStock['unit_id2'],
                'BatchNo' => $stockAttributes['BatchNo'],
                'CoilNo' => $stockAttributes['BatchNo'] ? CoilNoHelper::get($partId, $stockAttributes['BatchNo']) : null,
                'SerialNo' => $stockAttributes['SerialNo'],
                'ExpDate' => $stockAttributes['ExpDate'],
                'BIN' => $stockAttributes['BIN'],
                'LOC' => $stockAttributes['LOC'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->guardAdvancedAccess();
        $this->validateRequest($request, true);

        try {
            DB::transaction(function () use ($request) {
                $transactionNo = $this->resolveTransactionNo($request);

                if (TransInventoryAdjustmentHD::where('TransactionNo', $transactionNo)->exists()) {
                    throw new Exception('Transaction No has already been taken!');
                }

                TransInventoryAdjustmentHD::create([
                    'TransactionNo' => $transactionNo,
                    'TransactionDate' => $this->generalService->formatDate($request->input('TransactionDate')),
                    'ExpiredDate' => $this->generalService->formatDate($request->input('ExpiredDate')),
                    'StockOpnameNo' => $request->input('Type') === 'opname' ? ($request->input('StockOpnameNo') ?? '') : '',
                    'WarehouseID' => $request->input('WarehouseID'),
                    'DivisionID' => $request->input('DivisionID'),
                    'InventoryTypeID' => $request->input('InventoryTypeID') ?: null,
                    'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                    'CreatedBy' => Auth::user()->UserID,
                    'EntryTime' => date('Y-m-d H:i:s'),
                    'LastUpdateBy' => Auth::user()->UserID,
                    'LastUpdate' => date('Y-m-d H:i:s'),
                    'IsAuto' => $request->input('automatic') ?? 0,
                    'LastDigit' => $request->input('_last_digit'),
                    'Editable' => true,
                ]);

                $this->markOpnameDone($request->input('StockOpnameNo'));
                $this->insertAdjustmentRows($request, $transactionNo);

                InventoryAdjustmentJournalService::rebuild($transactionNo);
            });

            clear_form_preservation('stock_adjustment_advanced_add');

            return redirect()->route('adjust')->with([
                'type' => 'success',
                'icon' => 'fa fa-fw fa-circle-check',
                'message' => 'Advanced Stock Adjustment successfully added!',
            ]);
        } catch (Exception $exception) {
            Log::error($exception);

            return redirect()->back()->withInput()->withErrors([
                $exception->getMessage() ?: 'Something went wrong',
            ]);
        }
    }

    public function update(Request $request)
    {
        $this->guardAdvancedAccess();
        $this->validateRequest($request, false);

        try {
            DB::transaction(function () use ($request) {
                $adjust = TransInventoryAdjustmentHD::with('details')->where('TransactionNo', $request->input('id'))->first();
                if (!$adjust) {
                    throw new Exception('Stock Adjustment does not exist.');
                }

                $serialStockDeltas = $this->serialStockDeltasFromExistingDetails($adjust);

                $this->releaseOpname($adjust->StockOpnameNo);
                BukuStock::where('TransactionNo', $adjust->TransactionNo)->delete();
                BukuStockHelper::validateSerialStockDoesNotExceedOne($serialStockDeltas, false);
                TransInventoryAdjustmentCheckers::where('TransactionNo', $adjust->TransactionNo)->delete();
                TransInventoryAdjustmentExecution::where('TransactionNo', $adjust->TransactionNo)->delete();
                TransInventoryAdjustmentDT::where('TransactionNo', $adjust->TransactionNo)->delete();

                $adjust->update([
                    'StockOpnameNo' => $request->input('Type') === 'opname' ? ($request->input('StockOpnameNo') ?? '') : '',
                    'WarehouseID' => $request->input('WarehouseID'),
                    'DivisionID' => $request->input('DivisionID'),
                    'InventoryTypeID' => $request->input('InventoryTypeID') ?: null,
                    'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                    'LastUpdateBy' => Auth::user()->UserID,
                    'LastUpdate' => date('Y-m-d H:i:s'),
                ]);

                $this->markOpnameDone($request->input('StockOpnameNo'));
                $this->insertAdjustmentRows($request, $adjust->TransactionNo);

                InventoryAdjustmentJournalService::rebuild($adjust->TransactionNo);
            });

            return redirect()->route('adjust')->with([
                'type' => 'success',
                'icon' => 'fa fa-fw fa-circle-check',
                'message' => 'Advanced Stock Adjustment successfully updated!',
            ]);
        } catch (Exception $exception) {
            Log::error($exception);

            return redirect()->back()->withInput()->withErrors([
                $exception->getMessage() ?: 'Something went wrong',
            ]);
        }
    }

    public function destroy($id)
    {
        $this->guardAdvancedAccess();

        try {
            DB::transaction(function () use ($id) {
                $adjust = TransInventoryAdjustmentHD::with('details')->where('id', $id)->first();
                if (!$adjust) {
                    throw new Exception('Stock Adjustment does not exist.');
                }

                $serialStockDeltas = $this->serialStockDeltasFromExistingDetails($adjust);

                $this->releaseOpname($adjust->StockOpnameNo);
                BukuStock::where('TransactionNo', $adjust->TransactionNo)->delete();
                BukuStockHelper::validateSerialStockDoesNotExceedOne($serialStockDeltas, false);
                TransJournalDT::where('TransactionNo', $adjust->TransactionNo)->delete();
                TransJournalHD::where('TransactionNo', $adjust->TransactionNo)->delete();
                TransInventoryAdjustmentCheckers::where('TransactionNo', $adjust->TransactionNo)->delete();
                TransInventoryAdjustmentExecution::where('TransactionNo', $adjust->TransactionNo)->delete();
                TransInventoryAdjustmentDT::where('TransactionNo', $adjust->TransactionNo)->delete();
                $adjust->delete();
            });

            return response()->json(['status' => 'success']);
        } catch (Exception $exception) {
            Log::error($exception);

            return response()->json([
                'status' => 'error',
                'message' => 'Delete failed! Make sure this stock adjustment is not used in any other data!',
            ]);
        }
    }

    private function validateRequest(Request $request, bool $create): void
    {
        $rules = [
            'TransactionDate' => 'required|date_format:d/m/Y',
            'ExpiredDate' => 'required|date_format:d/m/Y',
            'WarehouseID' => 'required|string',
            'DivisionID' => 'required|string',
            'Type' => 'required|in:opname,manual',
            'DetailsJson' => 'required|string',
            'employee' => 'required|array|min:1',
            'status' => 'required|array|min:1',
        ];

        if ($create) {
            $rules['TransactionNo'] = 'required_without:automatic|string|max:50|unique:Trans_InventoryAdjustmentHD,TransactionNo';
        } else {
            $rules['id'] = 'required|string';
        }

        if ($request->input('Type') === 'opname') {
            $rules['StockOpnameNo'] = 'required|string';
        }

        $request->validate($rules, [
            'TransactionNo.unique' => 'Transaction No has already been taken!',
            'DetailsJson.required' => 'You need to use at least one item!',
        ]);

        $details = $this->decodeDetails($request);
        if (count($details) === 0) {
            throw new Exception('You need to use at least one item!');
        }

        if ($request->input('Type') === 'opname') {
            $opname = TransStockOpnameHD::where('TransactionNo', $request->input('StockOpnameNo'))->first();
            $existingAdjust = $request->input('id')
                ? TransInventoryAdjustmentHD::where('TransactionNo', $request->input('id'))->first()
                : null;
            $sameLinkedOpname = $existingAdjust && $existingAdjust->StockOpnameNo === $request->input('StockOpnameNo');

            if (!$opname || $opname->details()->where('Closed', 0)->exists() || ($opname->Status !== 'ADJUSTED' && !$sameLinkedOpname)) {
                throw new Exception('Stock Opname must be adjusted and all details must be closed.');
            }
        }

        $hasSupervisor = collect($request->input('status', []))->contains('SUPERVISOR');
        if (!$hasSupervisor) {
            throw new Exception('Need at least 1 checker as supervisor!');
        }

        $seen = [];
        foreach ($details as $detail) {
            $partId = $this->detailValue($detail, 'PartID', 'part');
            $part = MsPart::where('PartID', $partId)->first();
            if (!$part) {
                throw new Exception("Part {$partId} does not exist.");
            }

            $qtyOpname = (float) ($detail['QtyOpname'] ?? $detail['opname'] ?? 0);
            if ($qtyOpname < 0) {
                throw new Exception('Opname Qty is invalid!');
            }

            $stockAttributes = $this->stockFiltersForInsert($this->submittedStockFilters($detail, (int) ($part->WithSerialNo ?? 0) === 1));

            $key = implode('|', [
                $partId,
                $detail['UnitID'] ?? $detail['unit'] ?? '',
                $stockAttributes['BatchNo'] ?? '',
            ]);

            if (isset($seen[$key])) {
                throw new Exception("Duplicate stock detail found for Part {$partId}.");
            }

            $seen[$key] = true;
        }
    }

    private function insertAdjustmentRows(Request $request, string $transactionNo): void
    {
        $details = [];
        $executions = [];
        $bukuStockRows = [];
        $serialStockDeltas = [];
        $transactionDate = $this->generalService->formatDate($request->input('TransactionDate'));
        $warehouseId = $request->input('WarehouseID');

        foreach ($this->decodeDetails($request) as $i => $detail) {
            $partId = $this->detailValue($detail, 'PartID', 'part');
            $part = MsPart::where('PartID', $partId)->first();
            $withSerialNo = (int) ($part->WithSerialNo ?? 0) === 1;
            $stockFilters = $this->submittedStockFilters($detail, $withSerialNo);
            $stockAttributes = $this->stockFiltersForInsert($stockFilters);

            $unitId = $detail['UnitID'] ?? $detail['unit'] ?? $this->lowestUnit($partId);
            $qtyStock = $request->input('Type') === 'manual'
                ? BukuStockHelper::calculateCurrentStock($partId, $warehouseId, $stockFilters['BatchNo'], $stockFilters['SerialNo'], $stockFilters['ExpDate'], $stockFilters['BIN'], $stockFilters['LOC'])
                : (float) ($detail['QtyStock'] ?? $detail['stock'] ?? 0);
            $qtyOpname = (float) ($detail['QtyOpname'] ?? $detail['opname'] ?? 0);
            $difference = $qtyOpname - $qtyStock;

            $details[] = [
                'TransactionNo' => $transactionNo,
                'PartID' => $partId,
                'UnitID' => $unitId,
                'QtyStock' => $qtyStock,
                'QtyOpname' => $qtyOpname,
                'BatchNo' => $stockAttributes['BatchNo'],
                'SerialNo' => $stockAttributes['SerialNo'],
                'ExpDate' => $stockAttributes['ExpDate'],
                'BIN' => $stockAttributes['BIN'],
                'LOC' => $stockAttributes['LOC'],
                'Notes' => '',
            ];

            $executions[] = [
                'TransactionNo' => $transactionNo,
                'PartID' => $partId,
                'UnitID' => $unitId,
                'Qty' => $difference,
                'BatchNo' => $stockAttributes['BatchNo'],
                'SerialNo' => $stockAttributes['SerialNo'],
                'ExpDate' => $stockAttributes['ExpDate'],
                'BIN' => $stockAttributes['BIN'],
                'LOC' => $stockAttributes['LOC'],
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
            ];

            $bukuStockRow = [
                'TransactionNo' => $transactionNo,
                'TransactionDate' => $transactionDate,
                'PartID' => $partId,
                'WarehouseID' => $warehouseId,
                'Sequence' => $i,
                'UnitID' => $unitId,
                'Qty' => $difference,
                'BatchNo' => $stockAttributes['BatchNo'],
                'SerialNo' => $stockAttributes['SerialNo'],
                'ExpDate' => $stockAttributes['ExpDate'],
                'BIN' => $stockAttributes['BIN'],
                'LOC' => $stockAttributes['LOC'],
                'TransactionType' => 'INVENTORY_ADJUSTMENT',
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
            ];

            $bukuStockRows[] = array_merge($bukuStockRow, DualQuantityHelper::bukuStockSecondaryColumns(
                $difference,
                $partId,
                $warehouseId,
                $stockAttributes['BatchNo'],
                $stockAttributes['SerialNo'],
                $stockAttributes['ExpDate'],
                $stockAttributes['BIN'],
                $stockAttributes['LOC']
            ));
        }

        BukuStockHelper::validateSerialStockDoesNotExceedOne($serialStockDeltas, false);

        $this->insertInChunks(TransInventoryAdjustmentDT::class, $details);
        $this->insertInChunks(TransInventoryAdjustmentExecution::class, $executions);
        $this->insertInChunks(BukuStock::class, $bukuStockRows);

        $checkers = [];
        foreach ($request->input('employee', []) as $i => $employee) {
            $checkers[] = [
                'TransactionNo' => $transactionNo,
                'EmployeeID' => $employee,
                'Status' => $request->input('status')[$i] ?? 'STAFF',
            ];
        }

        $this->insertInChunks(TransInventoryAdjustmentCheckers::class, $checkers);
    }

    /**
     * Delta=0 placeholders for an existing transaction's serial-controlled
     * details, used to re-validate remaining serial stock (from other
     * transactions) after this transaction's BukuStock rows are removed
     * but before it is re-inserted (update) or gone for good (delete).
     */
    private function serialStockDeltasFromExistingDetails(TransInventoryAdjustmentHD $adjust): array
    {
        $serialStockDeltas = [];
        foreach ($adjust->details as $detail) {
            $serialNo = $this->generalService->nullableDetailValue($detail->SerialNo);
            if ($serialNo === null) {
                continue;
            }

            $serialStockDeltas[] = [
                'warehouse_id' => $adjust->WarehouseID,
                'part_id' => $detail->PartID,
                'serial_no' => $serialNo,
                'delta' => 0,
            ];
        }

        return $serialStockDeltas;
    }

    private function decodeDetails(Request $request): array
    {
        $details = json_decode($request->input('DetailsJson', '[]'), true);

        return is_array($details) ? array_values($details) : [];
    }

    private function submittedStockFilters(array $detail, bool $withSerialNo): array
    {
        return [
            'BatchNo' => $this->submittedStockValue($detail['BatchNo'] ?? $detail['batch_no'] ?? null),
            'SerialNo' => null,
            'ExpDate' => null,
            'BIN' => null,
            'LOC' => null,
        ];
    }

    private function submittedStockValue($value): ?string
    {
        return $this->generalService->nullableDetailValue($value);
    }

    private function submittedDateValue($value): ?string
    {
        $value = $this->generalService->nullableDetailValue($value);
        if ($value === null || $value === '__NULL__') {
            return $value;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return $this->generalService->nullableDateValue($value);
    }

    private function stockFiltersForInsert(array $stockFilters): array
    {
        $attributes = [];
        foreach ($stockFilters as $key => $value) {
            $attributes[$key] = $value === '__NULL__' ? null : $value;
        }

        return $attributes;
    }

    private function detailValue(array $detail, string $primary, string $fallback): string
    {
        return trim((string) ($detail[$primary] ?? $detail[$fallback] ?? ''));
    }

    private function buildDetailPayload(TransInventoryAdjustmentHD $adjust): array
    {
        return $adjust->details->map(function ($detail) {
            $secondaryStock = DualQuantityHelper::currentStockSecondaryQuantity(
                $detail->PartID,
                $adjust->WarehouseID,
                $detail->BatchNo,
                $detail->SerialNo,
                $detail->ExpDate ? Carbon::parse($detail->ExpDate)->format('Y-m-d') : null,
                $detail->BIN,
                $detail->LOC
            );

            return [
                'id' => generateRandomString(10),
                'PartID' => $detail->PartID,
                'PartName' => $detail->part->PartName ?? '',
                'WithSerialNo' => (int) ($detail->part->WithSerialNo ?? 0),
                'UnitID' => $detail->UnitID,
                'QtyStock' => (float) $detail->QtyStock,
                'QtyOpname' => (float) $detail->QtyOpname,
                'QtyStock2' => $secondaryStock['qty2'] !== null ? (float) $secondaryStock['qty2'] : null,
                'UnitID2' => $secondaryStock['unit_id2'],
                'BatchNo' => $detail->BatchNo,
                'CoilNo' => $detail->BatchNo ? CoilNoHelper::get($detail->PartID, $detail->BatchNo) : null,
                'SerialNo' => $detail->SerialNo,
                'ExpDate' => $detail->ExpDate ? Carbon::parse($detail->ExpDate)->format('Y-m-d') : null,
                'BIN' => $detail->BIN,
                'LOC' => $detail->LOC,
            ];
        })->values()->all();
    }

    private function buildCheckerPayload(TransInventoryAdjustmentHD $adjust): array
    {
        return $adjust->checkers->map(function ($checker) {
            return [
                'id' => generateRandomString(10),
                'employee' => $checker->EmployeeID,
                'status' => $checker->Status,
            ];
        })->values()->all();
    }

    private function applyNullableBukuStockFilter($query, string $column, $value): void
    {
        if ($value === null || trim((string) $value) === '') {
            $query->whereNull($column);
            return;
        }

        $query->where($column, $value);
    }

    private function buildOpnameDetailPayload(TransStockOpnameHD $opname): array
    {
        return $opname->details->map(function ($detail) {
            $secondaryStock = DualQuantityHelper::currentStockSecondaryQuantity(
                $detail->PartID,
                $opname->WarehouseID,
                $detail->BatchNo,
                $detail->SerialNo,
                $detail->ExpDate ? Carbon::parse($detail->ExpDate)->format('Y-m-d') : null,
                $detail->BIN,
                $detail->LOC
            );

            return [
                'id' => generateRandomString(10),
                'PartID' => $detail->PartID,
                'PartName' => $detail->part->PartName ?? '',
                'WithSerialNo' => (int) ($detail->part->WithSerialNo ?? 0),
                'UnitID' => $detail->UnitID,
                'QtyStock' => (float) $detail->QtyStock,
                'QtyOpname' => (float) $detail->QtyOpname,
                'QtyStock2' => $secondaryStock['qty2'] !== null ? (float) $secondaryStock['qty2'] : null,
                'UnitID2' => $secondaryStock['unit_id2'],
                'BatchNo' => $detail->BatchNo,
                'CoilNo' => $detail->BatchNo ? CoilNoHelper::get($detail->PartID, $detail->BatchNo) : null,
                'SerialNo' => $detail->SerialNo,
                'ExpDate' => $detail->ExpDate ? Carbon::parse($detail->ExpDate)->format('Y-m-d') : null,
                'BIN' => $detail->BIN,
                'LOC' => $detail->LOC,
            ];
        })->values()->all();
    }

    private function buildOpnameCheckerPayload(TransStockOpnameHD $opname): array
    {
        return $opname->checkers->map(function ($checker) {
            return [
                'id' => generateRandomString(10),
                'employee' => $checker->EmployeeID,
                'status' => $checker->Status,
            ];
        })->values()->all();
    }

    private function resolveTransactionNo(Request $request): string
    {
        if (!$request->input('automatic')) {
            $request->merge(['_last_digit' => null]);
            return trim($request->input('TransactionNo'));
        }

        $masterAuto = MsAutoNumber::find('1');
        $digit = 1;
        $checkLast = TransInventoryAdjustmentHD::where('IsAuto', 1)
            ->whereMonth('TransactionDate', Carbon::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('m'))
            ->whereYear('TransactionDate', Carbon::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y'))
            ->orderBy('LastDigit', 'desc')
            ->first();

        if ($checkLast) {
            $digit = $checkLast->LastDigit + 1;
        }

        do {
            $id = $masterAuto->Inventory04 . '/' . Carbon::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y')
                . '/' . Carbon::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('m')
                . '/' . str_pad($digit, 4, '0', STR_PAD_LEFT);

            $exists = TransInventoryAdjustmentHD::where('TransactionNo', $id)->exists();
            if ($exists) {
                $digit++;
            }
        } while ($exists);

        $request->merge(['_last_digit' => $digit]);

        return $id;
    }

    private function lowestUnit(string $partId): ?string
    {
        $unit = MsPartUnit::where('PartID', $partId)
            ->where('Conversion', 1)
            ->orderBy('Sequence')
            ->first();

        if ($unit) {
            return $unit->UnitID2;
        }

        $fallback = MsPartUnit::where('PartID', $partId)
            ->orderBy('Sequence')
            ->first();

        return $fallback ? ($fallback->UnitID1 ?? $fallback->UnitID2) : null;
    }

    private function insertInChunks(string $modelClass, array $rows): void
    {
        foreach (array_chunk($rows, 10) as $chunk) {
            if (count($chunk) > 0) {
                $modelClass::insert($chunk);
            }
        }
    }

    private function markOpnameDone(?string $transactionNo): void
    {
        if (!$transactionNo) {
            return;
        }

        TransStockOpnameHD::where('TransactionNo', $transactionNo)->update([
            'Status' => 'DONE',
            'Editable' => false,
        ]);
    }

    private function releaseOpname(?string $transactionNo): void
    {
        if (!$transactionNo) {
            return;
        }

        TransStockOpnameHD::where('TransactionNo', $transactionNo)->update([
            'Status' => 'ADJUSTED',
            'Editable' => true,
        ]);
    }

    private function guardAdvancedAccess(): void
    {
    }
}
