<?php

namespace App\Http\Controllers\Admin;

use App\Models\ControlPanel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

class ControlPanelController extends Controller
{
    private const REMOVED_SETTINGS = ['is_buku_stock'];

    public function index()
    {
        return view('admin.control_panel.index');
    }

    public function datatable(Request $request)
    {
        $query = ControlPanel::query()
            ->whereNotIn('SettingKey', self::REMOVED_SETTINGS);

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                $icon = $row->SettingValue == 1
                    ? '<i class="fas fa-toggle-on fa-xl text-success toggle-btn" style="cursor:pointer" data-id="' . $row->id . '"></i>'
                    : '<i class="fas fa-toggle-off fa-xl text-secondary toggle-btn" style="cursor:pointer" data-id="' . $row->id . '"></i>';

                return $icon;
            })
            ->rawColumns(['action'])
            ->make(true);
    }


    public function toggle(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:ControlPanel,id',
        ]);

        try {
            $cp = ControlPanel::findOrFail($request->id);
            if (in_array($cp->SettingKey, self::REMOVED_SETTINGS, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This control panel setting has been removed.',
                ], 422);
            }

            $cp->SettingValue = $cp->SettingValue == 1 ? 0 : 1;
            $cp->save();

            $statusText = $cp->SettingValue == 1 ? 'ON' : 'OFF';

            return response()->json([
                'success' => true,
                'message' => "Control Panel successfully updated. Status: {$statusText}",
                'new_value' => $cp->SettingValue,
                'status_text' => $statusText
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Control Panel: ' . $e->getMessage(),
            ], 500);
        }
    }
}
