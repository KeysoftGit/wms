<?php

namespace App\Http\Controllers\Purchase;

use App\Services\WarehouseAccessCriteria;
use App\Http\Controllers\Controller;
use App\Models\BukuHutang;
use App\Models\BukuStock;
use App\Models\DocPrint;
use App\Models\MsAutoNumber;
use App\Models\MsCompanyProfile;
use App\Models\MsRevAlias;
use App\Models\TransDirectPurchaseDT;
use App\Models\TransDirectPurchaseHD;
use App\Models\TransJournalDT;
use App\Models\TransJournalHD;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class DirectPurchaseController extends Controller
{
    public function getRev()
    {
        $revs = MsRevAlias::where('TransactionType', 'PURCHASING')->first();

        $revData = [
            [
                'name' => $revs->ItemRevDT01,
                'type' => $revs->ItemRevDT01Type
            ],
            [
                'name' => $revs->ItemRevDT02,
                'type' => $revs->ItemRevDT02Type
            ],
            [
                'name' => $revs->ItemRevDT03,
                'type' => $revs->ItemRevDT03Type
            ],
            [
                'name' => $revs->ItemRevDT04,
                'type' => $revs->ItemRevDT04Type
            ],
            [
                'name' => $revs->ItemRevDT05,
                'type' => $revs->ItemRevDT05Type
            ],
            [
                'name' => $revs->ItemRevDT06,
                'type' => $revs->ItemRevDT06Type
            ],
            [
                'name' => $revs->ItemRevDT07,
                'type' => $revs->ItemRevDT07Type
            ],
            [
                'name' => $revs->ItemRevDT08,
                'type' => $revs->ItemRevDT08Type
            ],
            [
                'name' => $revs->ItemRevDT09,
                'type' => $revs->ItemRevDT09Type
            ],
            [
                'name' => $revs->ItemRevDT10,
                'type' => $revs->ItemRevDT10Type
            ],
            [
                'name' => $revs->ItemRevDT11,
                'type' => $revs->ItemRevDT11Type
            ],
            [
                'name' => $revs->ItemRevDT12,
                'type' => $revs->ItemRevDT12Type
            ],
            [
                'name' => $revs->ItemRevDT13,
                'type' => $revs->ItemRevDT13Type
            ],
            [
                'name' => $revs->ItemRevDT14,
                'type' => $revs->ItemRevDT14Type
            ],
            [
                'name' => $revs->ItemRevDT15,
                'type' => $revs->ItemRevDT15Type
            ],
        ];

        return $revData;
    }

    public function index()
    {
        return view('purchase.dp.index');
    }

    public function datatable(Request $request)
    {
        $data = TransDirectPurchaseHD::select('id', 'TransactionNo', 'TransactionDate', 'DivisionID', 'SupplierID', 'GrandTotal', 'Editable')
            ->with(['division', 'supplier']);
        WarehouseAccessCriteria::applyDetails($data);

        if ($request->get('date')) {
            $dates = explode(' to ', $request->get('date'));
            if (count($dates) > 1) {
                $data->whereDate('TransactionDate', '>=', \DateTime::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d'));
                $data->whereDate('TransactionDate', '<=', \DateTime::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d'));
            } else {
                $data->whereDate('TransactionDate', \DateTime::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d'));
            }
        }

        if ($request->get('outstanding')) {
            $data->where('Outstanding', 1);
        }

        return DataTables::of($data)
            ->editColumn('DivisionID', function ($row) {
                return optional($row->division)->DivisionName ?? $row->DivisionID;
            })
            ->editColumn('SupplierID', function ($row) {
                return optional($row->supplier)->SupplierName ?? $row->SupplierID;
            })
            ->addColumn('action', function ($row) {
                $btn = '<div class="btn-group">';

                $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Show" href="' . route('dp.show', $row->id) . '"><i class="fa fa-fw fa-eye"></i></a>';
                if ($row->Editable == 1) {
                    if (Auth::user()->hasAnyPermission(['admin', 'dp.edit'])) {
                        $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Edit" href="' . route('dp.edit', $row->id) . '"><i class="fa fa-fw fa-edit"></i></a>';
                    }
                    if (Auth::user()->hasAnyPermission(['admin', 'dp.delete'])) {
                        $btn .= '<button class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled delete-btn" data-bs-toggle="tooltip" title="Delete" data-url="' . route('dp.delete', $row->id) . '"><i class="fa fa-fw fa-trash"></i></button>';
                    }
                }

                return $btn;
            })
            ->make(true);
    }

    public function add()
    {
        $company = MsCompanyProfile::find(1);
        $currency = $company->CurrencyID;
        $revData = $this->getRev();

        return view('purchase.dp.add', compact('currency', 'revData'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'TransactionNo' => 'required_without:automatic|string|max:50|unique:Trans_DirectPurchaseHD,TransactionNo',
            'TransactionDate' => 'required',
            'DueDate' => 'required',
            'SupplierID' => 'required',
            'DivisionID' => 'required',
            'CurrencyID' => 'required',
            'Rate' => 'required',
            'FiscalRate' => 'required',
            'VAT' => 'required',
            'Notes' => 'nullable|string',
            "part" => "required|array|min:1"
        ], [
            'TransactionNo.unique' => 'Transaction No has already been taken!',
            'TransactionNo.max' => 'Transaction No maximum characters is 50!',
            'part.min' => 'Need at least one detail to make an order!'
        ]);

        $id = null;

        try {
            DB::transaction(function () use ($request, &$id) {
                $masterAuto = MsAutoNumber::find("1");

                $digit = null;

                if ($request->input('automatic')) {
                    $checkLast = TransDirectPurchaseHD::where('IsAuto', 1)
                        ->whereMonth('TransactionDate', \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('m'))
                        ->whereYear('TransactionDate', \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y'))
                        ->orderBy('LastDigit', 'desc')
                        ->first();

                    if (!$checkLast) {
                        $digit = 1;
                    } else {
                        $digit = $checkLast->LastDigit + 1;
                    }

                    $id = $masterAuto->Purchase01 . '/' . \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y')
                        . '/' . \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('m')
                        . '/' . str_pad($digit, 4, "0", STR_PAD_LEFT);

                    $checkExist = TransDirectPurchaseHD::where('TransactionNo', $id)->first();
                    if ($checkExist) {
                        throw new \Exception("Transaction No has already been taken!");
                    }
                } else {
                    $id = trim($request->input('TransactionNo'));
                }

                $qty = $request->input('qty');
                $unit = $request->input('unit');
                $conversion = $request->input('conversion');
                $price = $request->input('price');
                $disc = $request->input('discount');
                $disc1 = $request->input('discount1');
                $disc1p = $request->input('discount1p');
                $disc2 = $request->input('discount2');
                $disc2p = $request->input('discount2p');
                $division = $request->input('division');
                $warehouse = $request->input('warehouse');

                $discount = 0;
                foreach ($disc as $d) {
                    $discount += $d;
                }

                TransDirectPurchaseHD::create([
                    'TransactionNo' => $id,
                    'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                    'DueDate' => \DateTime::createFromFormat('d/m/Y', $request->input('DueDate'))->format('Y-m-d'),
                    'SupplierID' => $request->input('SupplierID'),
                    'DivisionID' => $request->input('DivisionID'),
                    'CurrencyID' => $request->input('CurrencyID'),
                    'Rate' => $request->input('Rate') ?? 1,
                    'FiscalRate' => $request->input('FiscalRate') ?? 1,
                    'VAT' => $request->input('VAT'),
                    'ServiceTax' => $request->input('ServiceTax') ?? 0,
                    'PercentageServiceTax' => $request->input('PercentageServiceTax') ?? 0,
                    'Freight' => $request->input('Freight') ?? 0,
                    'VATValue' => $request->input('VATValue') ?? 0,
                    'Discount' => $discount,
                    'SubTotal' => $request->input('SubTotal') ?? 0,
                    'GrandTotal' => $request->input('GrandTotal') ?? 0,
                    'Editable' => 1,
                    'RevCount' => $request->input('rev') ?? 0,
                    'CreatedBy' => Auth::user()->UserID,
                    'EntryTime' => date('Y-m-d H:i:s'),
                    'LastUpdateBy' => Auth::user()->UserID,
                    'LastUpdate' => date('Y-m-d H:i:s'),
                    'IsAuto' => $request->input('automatic') ?? 0,
                    'LastDigit' => $digit,
                ]);

                $details = [];
                $BSDetails = [];

                foreach ($request->input('part') as $i => $part) {
                    $newDetail = [
                        'TransactionNo' => $id,
                        'PartID' => $part,
                        'Qty' => $qty[$i],
                        'Sequence' => $i,
                        'UnitID' => $unit[$i],
                        'Conversion' => $conversion[$i],
                        'UnitPrice' => $price[$i],
                        'PercentageDisc1' => $disc1p[$i],
                        'Discount1' => $disc1[$i],
                        'PercentageDisc2' => $disc2p[$i],
                        'Discount2' => $disc2[$i],
                        'Discount' => $disc[$i],
                        'DivisionID' => $division[$i],
                        'WarehouseID' => $warehouse[$i],
                    ];

                    for ($j = 1; $j <= $request->input('rev'); $j++) {
                        if ($j < 10) {
                            $newDetail['ItemRevDT0' . $j] = $request->input('rev' . $j)[$i];
                        } else {
                            $newDetail['ItemRevDT' . $j] = $request->input('rev' . $j)[$i];
                        }
                    }

                    $details[] = $newDetail;

                    // INSERT BUKU STOK
                    $BSDetails[] = [
                        'TransactionNo' => $id,
                        'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                        'PartID' => $part,
                        'WarehouseID' => $warehouse[$i],
                        'Sequence' => $i,
                        'UnitID' => $unit[$i],
                        'Qty' => (float) ($qty[$i] * $conversion[$i]),
                        'TransactionType' => 'DIRECT_PURCHASE',
                        'CreatedBy' => Auth::user()->UserID,
                        'EntryTime' => date('Y-m-d H:i:s'),
                    ];
                }

                TransDirectPurchaseDT::insert($details);
                BukuStock::insert($BSDetails);


                // INSERT BUKU HUTANG
                BukuHutang::create([
                    'TransactionNo' => $id,
                    'BalanceNo' => $id,
                    'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                    'DueDate' => \DateTime::createFromFormat('d/m/Y', $request->input('DueDate'))->format('Y-m-d'),
                    'SupplierID' => $request->input('SupplierID'),
                    'CurrencyID' => $request->input('CurrencyID'),
                    'Rate' => $request->input('Rate') ?? 1,
                    'Amount' => $request->input('GrandTotal') ?? 0,
                    'isVoucher' => 0,
                    'TransactionType' => 'DIRECT_PURCHASE',
                    'CreatedBy' => Auth::user()->UserID,
                    'EntryTime' => date('Y-m-d H:i:s'),
                ]);

                // INSERT JOURNAL
                DB::select("exec sp_jurnal_direct_purchase @Transactionno ='" . $id . "'");
            });
        } catch (\Exception $e) {
            Log::error($e);

            return redirect()->back()->withInput()->withErrors([
                $e->getMessage() ?? 'Something went wrong'
            ]);
        }

        return redirect()->route('dp')
            ->with([
                'type' => 'success',
                'icon' => 'fa fa-fw fa-circle-check',
                'message' => 'Direct Purchase successfully added!'
            ]);
    }

    public function show($id)
    {
        $dp = TransDirectPurchaseHD::where('id', $id)->first();
        $details = [];

        foreach ($dp->details as $detail) {
            $rev = [];
            for ($i = 0; $i < $dp->RevCount; $i++) {
                switch ($i) {
                    case 0:
                        $rev[] = $detail->ItemRevDT01 ?? '';
                        break;
                    case 1:
                        $rev[] = $detail->ItemRevDT02 ?? '';
                        break;
                    case 2:
                        $rev[] = $detail->ItemRevDT03 ?? '';
                        break;
                    case 3:
                        $rev[] = $detail->ItemRevDT04 ?? '';
                        break;
                    case 4:
                        $rev[] = $detail->ItemRevDT05 ?? '';
                        break;
                    case 5:
                        $rev[] = $detail->ItemRevDT06 ?? '';
                        break;
                    case 6:
                        $rev[] = $detail->ItemRevDT07 ?? '';
                        break;
                    case 7:
                        $rev[] = $detail->ItemRevDT08 ?? '';
                        break;
                    case 8:
                        $rev[] = $detail->ItemRevDT09 ?? '';
                        break;
                    case 9:
                        $rev[] = $detail->ItemRevDT10 ?? '';
                        break;
                    case 10:
                        $rev[] = $detail->ItemRevDT11 ?? '';
                        break;
                    case 11:
                        $rev[] = $detail->ItemRevDT12 ?? '';
                        break;
                    case 12:
                        $rev[] = $detail->ItemRevDT13 ?? '';
                        break;
                    case 13:
                        $rev[] = $detail->ItemRevDT14 ?? '';
                        break;
                    case 14:
                        $rev[] = $detail->ItemRevDT15 ?? '';
                        break;
                }
            }

            $details[] = [
                'part' =>  $detail->PartID . ($detail->part->PartName ? ' - ' . $detail->part->PartName : ''),
                'vat' =>  $detail->part->VAT2,
                'qty' =>  $detail->Qty,
                'unitUrl' =>  '',
                'unit' =>  $detail->UnitID . ($detail->unit->UnitName ? ' - ' . $detail->unit->UnitName : ''),
                'conversion' =>  $detail->Conversion,
                'price' =>  $detail->UnitPrice,
                'discount1' =>  $detail->Discount1 ?? '',
                'discount1p' =>  $detail->PercentageDisc1 ?? '',
                'discount2' =>  $detail->Discount2 ?? '',
                'discount2p' =>  $detail->PercentageDisc2 ?? '',
                'totalPrice' =>  0,
                'totalDiscount' =>  0,
                'division' =>  $detail->DivisionID . ($detail->division->DivisionName ? ' - ' . $detail->division->DivisionName : ''),
                'warehouse' =>  $detail->WarehouseID . ($detail->warehouse->WarehouseName ? ' - ' . $detail->warehouse->WarehouseName : ''),
                'image' =>  $detail->part->Image2 ? asset('storage/part/' . $detail->part->Image2) : '',
                'rev' =>  $rev,
                'initiated' => false,
            ];
        }

        $revData = $this->getRev();

        $options = DocPrint::where('ModuleCode', 'DIRECTPURC')
            ->where('TypeStr', 'print')
            ->get();

        return view('purchase.dp.show', compact('dp', 'details', 'options', 'revData'));
    }

    public function edit($id)
    {
        $dp = TransDirectPurchaseHD::where('id', $id)->first();
        $details = [];

        foreach ($dp->details as $detail) {
            $rev = [];
            for ($i = 0; $i < $dp->RevCount; $i++) {
                switch ($i) {
                    case 0:
                        $rev[] = $detail->ItemRevDT01 ?? '';
                        break;
                    case 1:
                        $rev[] = $detail->ItemRevDT02 ?? '';
                        break;
                    case 2:
                        $rev[] = $detail->ItemRevDT03 ?? '';
                        break;
                    case 3:
                        $rev[] = $detail->ItemRevDT04 ?? '';
                        break;
                    case 4:
                        $rev[] = $detail->ItemRevDT05 ?? '';
                        break;
                    case 5:
                        $rev[] = $detail->ItemRevDT06 ?? '';
                        break;
                    case 6:
                        $rev[] = $detail->ItemRevDT07 ?? '';
                        break;
                    case 7:
                        $rev[] = $detail->ItemRevDT08 ?? '';
                        break;
                    case 8:
                        $rev[] = $detail->ItemRevDT09 ?? '';
                        break;
                    case 9:
                        $rev[] = $detail->ItemRevDT10 ?? '';
                        break;
                    case 10:
                        $rev[] = $detail->ItemRevDT11 ?? '';
                        break;
                    case 11:
                        $rev[] = $detail->ItemRevDT12 ?? '';
                        break;
                    case 12:
                        $rev[] = $detail->ItemRevDT13 ?? '';
                        break;
                    case 13:
                        $rev[] = $detail->ItemRevDT14 ?? '';
                        break;
                    case 14:
                        $rev[] = $detail->ItemRevDT15 ?? '';
                        break;
                }
            }

            $details[] = [
                'id' => generateRandomString(10),
                'part' =>  $detail->PartID,
                'vat' =>  $detail->part->VAT2,
                'qty' =>  $detail->Qty,
                'unitUrl' =>  route('misc.partunit2', ['id' => $detail->PartID]),
                'unit' =>  $detail->UnitID,
                'conversion' =>  $detail->Conversion,
                'price' =>  $detail->UnitPrice,
                'discount1' =>  $detail->Discount1 ?? '',
                'discount1p' =>  $detail->PercentageDisc1 ?? '',
                'discount2' =>  $detail->Discount2 ?? '',
                'discount2p' =>  $detail->PercentageDisc2 ?? '',
                'totalPrice' =>  0,
                'totalDiscount' =>  0,
                'division' =>  $detail->DivisionID,
                'warehouse' =>  $detail->WarehouseID,
                'image' =>  $detail->part->Image2 ? asset('storage/part/' . $detail->part->Image2) : '',
                'rev' =>  $rev,
                'initiated' => false,
            ];
        }

        $revData = $this->getRev();

        return view('purchase.dp.edit', compact('dp', 'details', 'revData'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
            'TransactionDate' => 'required',
            'DueDate' => 'required',
            'SupplierID' => 'required',
            'DivisionID' => 'required',
            'CurrencyID' => 'required',
            'Rate' => 'required',
            'FiscalRate' => 'required',
            'VAT' => 'required',
            'Notes' => 'nullable|string',
            "part" => "required|array|min:1"
        ], [
            'part.min' => 'Need at least one detail to make an order!'
        ]);

        try {
            DB::transaction(function () use ($request) {

                $dp = TransDirectPurchaseHD::find($request->input('id'));

                $qty = $request->input('qty');
                $unit = $request->input('unit');
                $conversion = $request->input('conversion');
                $price = $request->input('price');
                $disc = $request->input('discount');
                $disc1 = $request->input('discount1');
                $disc1p = $request->input('discount1p');
                $disc2 = $request->input('discount2');
                $disc2p = $request->input('discount2p');
                $division = $request->input('division');
                $warehouse = $request->input('warehouse');

                $discount = 0;
                foreach ($disc as $d) {
                    $discount += $d;
                }

                $dp->update([
                    'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                    'DueDate' => \DateTime::createFromFormat('d/m/Y', $request->input('DueDate'))->format('Y-m-d'),
                    'SupplierID' => $request->input('SupplierID'),
                    'DivisionID' => $request->input('DivisionID'),
                    'CurrencyID' => $request->input('CurrencyID'),
                    'Rate' => $request->input('Rate') ?? 1,
                    'FiscalRate' => $request->input('FiscalRate') ?? 1,
                    'VAT' => $request->input('VAT'),
                    'ServiceTax' => $request->input('ServiceTax') ?? 0,
                    'PercentageServiceTax' => $request->input('PercentageServiceTax') ?? 0,
                    'PPH22' => $request->input('PPH22') ?? 0,
                    'Freight' => $request->input('Freight') ?? 0,
                    'VATValue' => $request->input('VATValue') ?? 0,
                    'Discount' => $discount,
                    'SubTotal' => $request->input('SubTotal') ?? 0,
                    'GrandTotal' => $request->input('GrandTotal') ?? 0,
                    'Editable' => 1,
                    'RevCount' => $request->input('rev') ?? 0,
                    'LastUpdateBy' => Auth::user()->UserID,
                    'LastUpdate' => date('Y-m-d H:i:s'),
                ]);

                //DELETE DETAIL AND BUKU STOCK
                TransDirectPurchaseDT::where('TransactionNo', $request->input('id'))->delete();
                BukuStock::where('TransactionNo', $request->input('id'))->delete();

                $details = [];
                $BSDetails = [];

                foreach ($request->input('part') as $i => $part) {
                    $newDetail = [
                        'TransactionNo' => $request->input('id'),
                        'PartID' => $part,
                        'Qty' => $qty[$i],
                        'Sequence' => $i,
                        'UnitID' => $unit[$i],
                        'Conversion' => $conversion[$i],
                        'UnitPrice' => $price[$i],
                        'PercentageDisc1' => $disc1p[$i],
                        'Discount1' => $disc1[$i],
                        'PercentageDisc2' => $disc2p[$i],
                        'Discount2' => $disc2[$i],
                        'Discount' => $disc[$i],
                        'DivisionID' => $division[$i],
                        'WarehouseID' => $warehouse[$i],
                    ];

                    for ($j = 1; $j <= $request->input('rev'); $j++) {
                        if ($j < 10) {
                            $newDetail['ItemRevDT0' . $j] = $request->input('rev' . $j)[$i];
                        } else {
                            $newDetail['ItemRevDT' . $j] = $request->input('rev' . $j)[$i];
                        }
                    }

                    $details[] = $newDetail;

                    // INSERT BUKU STOK
                    $BSDetails[] = [
                        'TransactionNo' => $request->input('id'),
                        'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                        'PartID' => $part,
                        'WarehouseID' => $warehouse[$i],
                        'Sequence' => $i,
                        'UnitID' => $unit[$i],
                        'Qty' => (float)($qty[$i] * $conversion[$i]),
                        'TransactionType' => 'DIRECT_PURCHASE',
                        'CreatedBy' => Auth::user()->UserID,
                        'EntryTime' => date('Y-m-d H:i:s'),
                    ];
                }

                TransDirectPurchaseDT::insert($details);
                BukuStock::insert($BSDetails);


                // INSERT BUKU HUTANG
                BukuHutang::where('TransactionNo', $request->input('id'))->delete();
                BukuHutang::create([
                    'TransactionNo' => $request->input('id'),
                    'BalanceNo' => $request->input('id'),
                    'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                    'DueDate' => \DateTime::createFromFormat('d/m/Y', $request->input('DueDate'))->format('Y-m-d'),
                    'SupplierID' => $request->input('SupplierID'),
                    'CurrencyID' => $request->input('CurrencyID'),
                    'Rate' => $request->input('Rate') ?? 0,
                    'Amount' => $request->input('GrandTotal') ?? 0,
                    'isVoucher' => 0,
                    'TransactionType' => 'DIRECT_PURCHASE',
                    'CreatedBy' => Auth::user()->UserID,
                    'EntryTime' => date('Y-m-d H:i:s'),
                ]);

                // INSERT JOURNAL
                TransJournalDT::where('TransactionNo', $request->input('id'))->delete();
                TransJournalHD::where('TransactionNo', $request->input('id'))->delete();
                DB::select("exec sp_jurnal_direct_purchase @Transactionno ='" . $request->input('id') . "'");
            });

            return redirect()->route('dp')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Direct Purchase successfully updated!'
                ]);
        } catch (\Exception $e) {
            Log::error($e);

            return redirect()->back()->withInput()->withErrors([
                $e->getMessage() ?? 'Something went wrong'
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $dp = TransDirectPurchaseHD::where('id', $id)->first();

                BukuHutang::where('TransactionNo', $dp->TransactionNo)->delete();
                BukuStock::where('TransactionNo', $dp->TransactionNo)->delete();

                TransJournalDT::where('TransactionNo', $dp->TransactionNo)->delete();
                TransJournalHD::where('TransactionNo', $dp->TransactionNo)->delete();

                TransDirectPurchaseDT::where('TransactionNo', $dp->TransactionNo)->delete();
                $dp->delete();
            });

            return response([
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return response([
                'status' => 'failed',
            ]);
        }
    }
}
