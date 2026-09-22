<?php

namespace App\Http\Controllers;

use App\Models\MsRevAlias;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PHPUnit\Exception;

class RevController extends Controller
{
    public function index(){
        return view('rev.index');
    }

    public function getRev(Request $request){
        $revs = MsRevAlias::where('TransactionType', $request->get('type'))->first();

        $data = [
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
            ],[
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

        return response([
            'data' => $data
        ]);
    }

    public function update(Request $request){
        try {
            MsRevAlias::where('TransactionType', $request->input('TransactionType'))->update([
                'ItemRevDT01' => $request->input('rev1'),
                'ItemRevDT02' => $request->input('rev2'),
                'ItemRevDT03' => $request->input('rev3'),
                'ItemRevDT04' => $request->input('rev4'),
                'ItemRevDT05' => $request->input('rev5'),
                'ItemRevDT06' => $request->input('rev6'),
                'ItemRevDT07' => $request->input('rev7'),
                'ItemRevDT08' => $request->input('rev8'),
                'ItemRevDT09' => $request->input('rev9'),
                'ItemRevDT10' => $request->input('rev10'),
                'ItemRevDT11' => $request->input('rev11'),
                'ItemRevDT12' => $request->input('rev12'),
                'ItemRevDT13' => $request->input('rev13'),
                'ItemRevDT14' => $request->input('rev14'),
                'ItemRevDT15' => $request->input('rev15'),
                'ItemRevDT01Type' => $request->input('revType1'),
                'ItemRevDT02Type' => $request->input('revType2'),
                'ItemRevDT03Type' => $request->input('revType3'),
                'ItemRevDT04Type' => $request->input('revType4'),
                'ItemRevDT05Type' => $request->input('revType5'),
                'ItemRevDT06Type' => $request->input('revType6'),
                'ItemRevDT07Type' => $request->input('revType7'),
                'ItemRevDT08Type' => $request->input('revType8'),
                'ItemRevDT09Type' => $request->input('revType9'),
                'ItemRevDT10Type' => $request->input('revType10'),
                'ItemRevDT11Type' => $request->input('revType11'),
                'ItemRevDT12Type' => $request->input('revType12'),
                'ItemRevDT13Type' => $request->input('revType13'),
                'ItemRevDT14Type' => $request->input('revType14'),
                'ItemRevDT15Type' => $request->input('revType15'),
            ]);

            return redirect()->route('rev')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Rev Configuration successfully updated!'
                ]);
        } catch (\Exception $exception){
            Log::error($exception);

            return redirect()->back()->withInput()->withErrors([
                'Something went wrong!'
            ]);
        }
    }

    public function selectData(Request $request){
        $rev = MsRevAlias::where('TransactionType', $request->get('type'))
            ->first();

        $data = [];
        $index = str_pad($request->get('index'), 2, "0", 0);
        $query = $rev->{"ItemRevDT".$index."Query"};

        if($query != null && $query != ""){
            try {
                $results = DB::select($query);

                foreach ($results as $result){
                    $data[] = [
                        'id' => $result->id,
                        'text' => $result->text
                    ];
                }
            } catch (Exception $exception){
                Log::error($exception);
            }
        }

        return response($data);
    }
}
