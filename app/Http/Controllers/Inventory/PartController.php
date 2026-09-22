<?php

namespace App\Http\Controllers\Inventory;

use App\Models\MsPart;
use App\Models\MsPartUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\ControlPanel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use App\Exports\PartExport;
use Maatwebsite\Excel\Facades\Excel;

class PartController extends Controller
{
    public function index()
    {
        return view('inventory.part.index');
    }

    public function export()
    {
        $data = MsPart::with(['category', 'specification', 'variant', 'type'])->get();
        return Excel::download(new PartExport($data), 'Inventory_Part_' . date('d_m_Y_H_i_s') . '.xlsx');
    }

    public function datatable(Request $request)
    {
        $data = MsPart::select(
            'Ms_Part.id',
            'PartID',
            'Ms_Part.Active',
            'PartName',
            DB::raw("concat(Ms_PartCategory.CategoryID, ' - ', Ms_PartCategory.CategoryName) as Category"),
            DB::raw("concat(Ms_PartSpecification.SpecificationID, ' - ', Ms_PartSpecification.SpecificationName) as Specification"),
            DB::raw("concat(Ms_PartVariant.VariantID, ' - ', Ms_PartVariant.VariantName) as Variant"),
            DB::raw("concat(Ms_InventoryType.InventoryTypeID, ' - ', Ms_InventoryType.InventoryTypeName) as Type"),
            'Ms_Part.created_at'
        )
            ->leftJoin('Ms_PartCategory', 'Ms_Part.CategoryID', '=', 'Ms_PartCategory.CategoryID')
            ->leftJoin('Ms_PartSpecification', 'Ms_Part.SpecificationID', '=', 'Ms_PartSpecification.SpecificationID')
            ->leftJoin('Ms_PartVariant', 'Ms_Part.VariantID', '=', 'Ms_PartVariant.VariantID')
            ->leftJoin('Ms_InventoryType', 'Ms_Part.InventoryTypeID', '=', 'Ms_InventoryType.InventoryTypeID');

        return DataTables::of($data)
            ->addColumn('action', function ($row) {
                $btn = '<div class="btn-group">';

                $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Show" href="' . route('inventory.part.show', $row->id) . '"><i class="fa fa-fw fa-eye"></i></a>';
                // $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Generate QR" target="_blank" href="' . route('inventory.part.qr', ['ids' => $row->PartID]) . '"><i class="fa fa-fw fa-qrcode"></i></a>';
                if (Auth::user()->hasAnyPermission(['admin', 'part.edit'])) {
                    $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Edit" href="' . route('inventory.part.edit', $row->id) . '"><i class="fa fa-fw fa-edit"></i></a>';
                }
                if (Auth::user()->hasAnyPermission(['admin', 'part.delete'])) {
                    $btn .= '<button class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled delete-btn" data-bs-toggle="tooltip" title="Delete" data-url="' . route('inventory.part.delete', $row->id) . '"><i class="fa fa-fw fa-trash"></i></button>';
                }

                return $btn;
            })
            ->filterColumn('Category', function ($query, $keyword) {
                $sql = "concat(Ms_PartCategory.CategoryID, ' - ', Ms_PartCategory.CategoryName)  like ?";
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->filterColumn('Specification', function ($query, $keyword) {
                $sql = "concat(Ms_PartSpecification.SpecificationID, ' - ', Ms_PartSpecification.SpecificationName)  like ?";
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->filterColumn('Variant', function ($query, $keyword) {
                $sql = "concat(Ms_PartVariant.VariantID, ' - ', Ms_PartVariant.VariantName)  like ?";
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->filterColumn('Type', function ($query, $keyword) {
                $sql = "concat(Ms_InventoryType.InventoryTypeID, ' - ', Ms_InventoryType.InventoryTypeName)  like ?";
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->make(true);
    }

    public function add()
    {
        return view('inventory.part.add');
    }


    public function store(Request $request)
    {
        $rules = [
            'PartID' => 'required_without:automatic|string|max:50|unique:Ms_Part,PartID',
            'PartName' => 'nullable|string|max:255',
            'OtherID' => 'nullable|string|max:255',
            'CategoryID' => 'required',
            'SpecificationID' => 'required',
            'VariantID' => 'required',
            'InventoryTypeID' => 'required',
            'Notes' => 'nullable|string',
            'MinimumStockBuffer' => 'nullable|numeric',
            'MaximumStockBuffer' => 'nullable|numeric',
            'VAT2' => 'nullable|numeric',
        ];

        $messages = [
            'PartID.unique' => 'Part ID has already been taken!',
            'PartID.max' => 'Part ID maximum characters is 50!',
            'PartName.max' => 'Part Name maximum characters is 255!',
            'OtherID.max' => 'Contact Person maximum characters is 255!',
        ];

        // Cek setting part_name_unique menggunakan isEnabled()
        if (ControlPanel::isEnabled('part_name_unique')) {
            // Tambahkan rule unique ke PartName
            $rules['PartName'] .= '|unique:Ms_Part,PartName';
            $messages['PartName.unique'] = 'Part Name has already been taken!';
        }

        $this->validate($request, $rules, $messages);

        $id = trim($request->input('PartID'));

        try {
            DB::beginTransaction();
            $image = '';

            if ($request->file('Image')) {
                $file = $request->file('Image');
                $image = str_replace('/', '', $id) . '_image_' . date('d_m_Y_H_i_s') . '.' . $file->extension();

                $file->storeAs('', $image, 'inventory_part');
            }

            MsPart::create([
                'PartID' => $id,
                'PartName' => $request->input('PartName') ? trim($request->input('PartName')) : null,
                'OtherID' => $request->input('OtherID') ? trim($request->input('OtherID')) : null,
                'CategoryID' => $request->input('CategoryID') ?? null,
                'SpecificationID' => $request->input('SpecificationID') ?? null,
                'VariantID' => $request->input('VariantID') ?? null,
                'InventoryTypeID' => $request->input('InventoryTypeID') ?? null,
                'PartType' => $request->input('PartType') ?? null,
                'WithSerialNo' => 0,
                'MaximumStockBuffer' => $request->input('MaximumStockBuffer') ?? 0,
                'MinimumStockBuffer' => $request->input('MinimumStockBuffer') ?? 0,
                'VAT2' => $request->input('VAT2') ?? 11,
                'DeferedWarehouseID' => $request->input('DeferedWarehouseID') ?? null,
                'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                'Image2' => $request->file('Image') ? $image : null,
                'Pricing' => $request->input('Pricing') ?? null,
                'TypeOfGuarantee' => $request->input('TypeOfGuarantee') ?? null,
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
                'Active' => $request->input('Active') ?? 0,
            ]);

            if ($request->input('Unit')) {
                $units = $request->input('Unit');
                $conversions = $request->input('Conversion');
                foreach ($units as $i => $unit) {
                    if ($unit != null) {
                        if ($i == 0) {
                            MsPartUnit::create([
                                'PartID' => $id,
                                'UnitID1' => $unit,
                                'UnitID2' => $unit,
                                'Conversion' => 1,
                                'Sequence' => 1
                            ]);
                        } else {
                            MsPartUnit::create([
                                'PartID' => $id,
                                'UnitID1' => $units[0],
                                'UnitID2' => $unit,
                                'Conversion' => $conversions[$i - 1],
                                'Sequence' => ($i + 1)
                            ]);
                        }
                    }
                }
            }

            // CUSTOM PERMINTAAN KO HERRIE
            DB::select("exec sp_AutoCreate_Part @PartID ='" . $id . "'");
            DB::commit();
            clear_form_preservation('inventory_part_add');
            return redirect()->route('inventory.part')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Part successfully added!'
                ]);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            $part = MsPart::find($id);
            if ($part) {
                MsPartUnit::where('PartID', $id)->delete();
                $part->delete();
            }

            return redirect()->back()->withInput()->withErrors([
                $e->getMessage() ?: 'Something went wrong!'
            ]);
        }
    }

    public function show($id)
    {
        $part = MsPart::where('id', $id)->first();

        return view('inventory.part.show', compact('part'));
    }

    public function edit($id)
    {
        $part = MsPart::where('id', $id)->first();
        return view('inventory.part.edit', compact('part'));
    }

    public function update(Request $request)
    {
        $rules = [
            'PartName' => 'nullable|string|max:255',
            'OtherID' => 'nullable|string|max:255',
            'CategoryID' => 'required',
            'SpecificationID' => 'required',
            'VariantID' => 'required',
            'InventoryTypeID' => 'required',
            'Notes' => 'nullable|string',
            'MinimumStockBuffer' => 'nullable|numeric',
            'MaximumStockBuffer' => 'nullable|numeric',
            'VAT2' => 'nullable|numeric',
        ];

        $messages = [
            'PartName.max' => 'Part Name maximum characters is 255!',
            'OtherID.max' => 'Contact Person maximum characters is 255!',
        ];

        // Cek setting part_name_unique
        if (ControlPanel::isEnabled('part_name_unique')) {
            // Untuk update, tambahkan pengecualian untuk ID yang sedang di-update
            $partId = $request->input('id'); // atau $request->input('id') tergantung cara kirim

            $rules['PartName'] .= '|unique:Ms_Part,PartName,' . $partId . ',PartID';
            $messages['PartName.unique'] = 'Part Name has already been taken!';
        }

        $this->validate($request, $rules, $messages);

        try {
            $part = MsPart::find($request->input('id'));
            $image = '';

            if ($request->file('Image')) {
                if ($part->Image != null && $part->Image != '') {
                    Storage::disk('inventory_part')->delete($part->Image);
                }

                $file = $request->file('Image');
                $image = str_replace('/', '', $request->input('id')) . '_image_' . date('d_m_Y_H_i_s') . '.' . $file->extension();

                $file->storeAs('', $image, 'inventory_part');
            }

            DB::beginTransaction();

            $part->update([
                'PartName' => $request->input('PartName') ? trim($request->input('PartName')) : null,
                'OtherID' => $request->input('OtherID') ? trim($request->input('OtherID')) : null,
                'CategoryID' => $request->input('CategoryID') ?? null,
                'SpecificationID' => $request->input('SpecificationID') ?? null,
                'VariantID' => $request->input('VariantID') ?? null,
                'InventoryTypeID' => $request->input('InventoryTypeID') ?? null,
                'PartType' => $request->input('PartType') ?? null,
                'WithSerialNo' => 0,
                'MaximumStockBuffer' => $request->input('MaximumStockBuffer') ?? 0,
                'MinimumStockBuffer' => $request->input('MinimumStockBuffer') ?? 0,
                'VAT2' => $request->input('VAT2') ?? 11,
                'DeferedWarehouseID' => $request->input('DeferedWarehouseID') ?? null,
                'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                'Image2' => $request->file('Image') ? $image : $part->Image2,
                'Pricing' => $request->input('Pricing') ?? null,
                'TypeOfGuarantee' => $request->input('TypeOfGuarantee') ?? null,
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
                'Active' => $request->input('Active') ?? 0,
            ]);


            MsPartUnit::where('PartID', $request->input('id'))->delete();
            if ($request->input('Unit')) {
                $units = $request->input('Unit');
                $conversions = $request->input('Conversion');
                foreach ($units as $i => $unit) {
                    if ($unit != null) {
                        if ($i == 0) {
                            MsPartUnit::create([
                                'PartID' => $request->input('id'),
                                'UnitID1' => $unit,
                                'UnitID2' => $unit,
                                'Conversion' => 1,
                                'Sequence' => 1
                            ]);
                        } else {
                            MsPartUnit::create([
                                'PartID' => $request->input('id'),
                                'UnitID1' => $units[0],
                                'UnitID2' => $unit,
                                'Conversion' => $conversions[$i - 1],
                                'Sequence' => ($i + 1)
                            ]);
                        }
                    }
                }
            }
            DB::commit();
            return redirect()->route('inventory.part')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Part successfully updated!'
                ]);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors([
                'Something went wrong!'
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $part = MsPart::where('id', $id)->first();

            MsPartUnit::where('PartID', $part->PartID)->delete();
            $part->delete();
            DB::commit();
            return response([
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            return response([
                'status' => 'failed',
            ]);
        }
    }



    public function updateNonBatch(Request $request)
    {
        $this->validate($request, [
            'PartName' => 'nullable|string|max:255',
            'OtherID' => 'nullable|string|max:255',
            'CategoryID' => 'required',
            'VariantID' => 'required',
            'InventoryTypeID' => 'required',
            'Notes' => 'nullable|string',
            'MinimumStockBuffer' => 'nullable|numeric',
            'MaximumStockBuffer' => 'nullable|numeric',
            'VAT2' => 'nullable|numeric',
        ], [
            'PartName.max' => 'Part Name maximum characters is 255!',
            'OtherID.max' => 'Contact Person maximum characters is 255!',
        ]);

        try {
            $part = MsPart::find($request->input('id'));
            $image = '';

            if ($request->file('Image')) {
                if ($part->Image != null && $part->Image != '') {
                    Storage::disk('inventory_part')->delete($part->Image);
                }

                $file = $request->file('Image');
                $image = str_replace('/', '', $request->input('id')) . '_image_' . date('d_m_Y_H_i_s') . '.' . $file->extension();

                $file->storeAs('', $image, 'inventory_part');
            }

            DB::beginTransaction();

            $part->update([
                'PartName' => $request->input('PartName') ? trim($request->input('PartName')) : null,
                'OtherID' => $request->input('OtherID') ? trim($request->input('OtherID')) : null,
                'CategoryID' => $request->input('CategoryID') ?? null,
                'VariantID' => $request->input('VariantID') ?? null,
                'InventoryTypeID' => $request->input('InventoryTypeID') ?? null,
                'PartType' => $request->input('PartType') ?? null,
                'WithSerialNo' => 0,
                'MaximumStockBuffer' => $request->input('MaximumStockBuffer') ?? 0,
                'MinimumStockBuffer' => $request->input('MinimumStockBuffer') ?? 0,
                'VAT2' => $request->input('VAT2') ?? 11,
                'DeferedWarehouseID' => $request->input('DeferedWarehouseID') ?? null,
                'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                'Image2' => $request->file('Image') ? $image : $part->Image2,
                'Pricing' => $request->input('Pricing') ?? null,
                'TypeOfGuarantee' => $request->input('TypeOfGuarantee') ?? null,
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
                'Active' => $request->input('Active') ?? 0,
            ]);


            MsPartUnit::where('PartID', $request->input('id'))->delete();
            if ($request->input('Unit')) {
                $units = $request->input('Unit');
                $conversions = $request->input('Conversion');
                foreach ($units as $i => $unit) {
                    if ($unit != null) {
                        if ($i == 0) {
                            MsPartUnit::create([
                                'PartID' => $request->input('id'),
                                'UnitID1' => $unit,
                                'UnitID2' => $unit,
                                'Conversion' => 1,
                                'Sequence' => 1
                            ]);
                        } else {
                            MsPartUnit::create([
                                'PartID' => $request->input('id'),
                                'UnitID1' => $units[0],
                                'UnitID2' => $unit,
                                'Conversion' => $conversions[$i - 1],
                                'Sequence' => ($i + 1)
                            ]);
                        }
                    }
                }
            }
            DB::commit();
            return redirect()->route('inventory.part')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Part successfully updated!'
                ]);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors([
                'Something went wrong!'
            ]);
        }
    }

    public function qr(Request $request)
    {
        $ids = $request->input('ids');
        if (!$ids) {
            return redirect()->back()->withErrors(['message' => 'Please select at least one part!']);
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $parts = MsPart::whereIn('PartID', $ids)->get();

        return view('inventory.part.qr', compact('parts'));
    }
}
