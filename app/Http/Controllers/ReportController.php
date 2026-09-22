<?php

namespace App\Http\Controllers;

use App\Models\DocPrint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function index()
    {
        return view('report.index');
    }

    public function getTypes(Request $request)
    {
        $types = DocPrint::where('ModuleCode', $request->get('module'))
            ->where('TypeStr', 'report')
            ->get();

        $data = [];
        foreach ($types as $type) {
            $data[] = [
                'code' => $type->Code,
                'name' => $type->ModuleName,
                'params' => $type->Params,
                'FastReport' => $type->FastReport,
                'Path' => $type->Path
            ];
        }

        return response([
            'data' => $data
        ]);
    }

    public function getMaterialCost(Request $request)
    {
        $date = $request->input('date');

        // Query langsung dengan year() dan month() SQL functions
        $query = "
        SELECT
            c.Period,
            b2.CategoryName,
            a.PartID,
            a.PartName,
            b.UnitID1,
            ISNULL(c.BeginningBalance, 0) as BeginningBalance,
            ISNULL(c.EndingBalance, 0) as EndingBalance
        FROM Ms_Part a
        INNER JOIN Ms_Part_Unit b ON b.PartID = a.PartID
        INNER JOIN Ms_PartCategory b2 ON b2.CategoryID = a.CategoryID
        LEFT JOIN Material_Cost c ON c.PartID = a.PartID
            AND LEFT(c.Period, 4) = YEAR(?)
            AND RIGHT(c.Period, 2) = MONTH(?)
        WHERE b.Sequence = 1
        ORDER BY a.PartID ASC
    ";

        $results = DB::select($query, [$date, $date]);

        // Hitung summary
        $summary = [
            'total_beginning' => collect($results)->sum('BeginningBalance'),
            'total_ending' => collect($results)->sum('EndingBalance'),
            'total_records' => count($results),
            'period' => date('F Y', strtotime($date)),
            'date' => $date
        ];

        return view('report.material-cost', [
            'data' => $results,
            'summary' => $summary,
            'date' => $date
        ]);
    }
}
