<?php

namespace App\Http\Controllers\Admin;

use App\Models\Menu;
use App\Models\MsUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Spatie\Permission\PermissionRegistrar;

class AccessController extends Controller
{
    public function index(){
        return view('admin.access.index');
    }

    public function getMenus(Request $request){
        $menus = Menu::orderBy('DisplayName')->get();
        $user = MsUser::where('UserID', $request->get('id'))->first();

        $data = [];
        $permissions = [];
        foreach ($menus as $i => $menu){
            $actions = explode(',', $menu->actions);

            $actionData = [];
            foreach ($actions as $action){
                $permission = $menu->Name . '.' . $action;
                $actionData[] = [
                    'name' => ucfirst($action),
                    'permission' => $permission,
                ];

                if($user->hasPermissionTo($permission)){
                    $permissions[] = $permission;
                }
            }

            $data[] = [
                'name' => $menu->DisplayName,
                'actions' => $actionData,
            ];
        }

        return response([
            'menus' => $data,
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request){
        try {
            $user = MsUser::where('UserID', $request->input('UserID'))->first();

            $user->syncPermissions($request->input('permissions'));

            return redirect()->back()->with([
                'type' => 'success',
                'icon' => 'fa fa-fw fa-circle-check',
                'message' => 'Menu access successfully added!'
            ]);
        } catch (\Exception $exception){
            Log::error($exception);

            return redirect()->back()->withInput()->withErrors([
                'Something went wrong!'
            ]);
        }
    }
}
