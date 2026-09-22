<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    public function index(){
        return view('password');
    }

    public function update(Request $request){
        $this->validate($request, [
            'old_password' => 'required',
            'new_password' => 'required|max:255|min:6|same:confirm_password',
            'confirm_password' => 'required|max:255|min:6',
        ]);

        try {
            $user = Auth::user();

            if(!Hash::check($request->input('old_password'), $user->WebPassword)){
                return \redirect()->back()->withErrors([
                    'Old Password is invalid!'
                ]);
            }

            $user->update([
                'Password' => $request->input('new_password'),
                'WebPassword' => Hash::make($request->input('new_password'))
            ]);

            return redirect()->route('password')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Password successfully updated!'
                ]);
        } catch (\Exception $exception){
            return redirect()->back()->withErrors([
               'Something Went Wrong'
            ]);
        }
    }
}
