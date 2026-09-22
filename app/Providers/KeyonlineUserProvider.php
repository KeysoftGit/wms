<?php

namespace App\Providers;

use App\Models\MsUser;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class KeyonlineUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials)
    {
        DB::purge('sqlsrv');

        Config::set('database.connections.sqlsrv', [
            'driver' => 'sqlsrv',
            'host' => session('db_host'),
            'port' => session('db_port'),
            'database' => session('db_database'),
            'username' => session('db_user'),
            'password' => session('db_password')
        ]);

        $user = MsUser::where('UserName', $credentials['username'])->first();

        return $user;
    }

    public function validateCredentials(UserContract $user, array $credentials)
    {
        $check = true;

        DB::purge('sqlsrv');

        Config::set('database.connections.sqlsrv', [
            'driver' => 'sqlsrv',
            'host' => session('db_host'),
            'port' => session('db_port'),
            'database' => session('db_database'),
            'username' => session('db_user'),
            'password' => session('db_password')
        ]);

        $user = MsUser::where('UserName', $credentials['username'])->first();

        if(!$user){
            $check = false;
        }

        if(!Hash::check($credentials['password'], $user->WebPassword)){
            $check = false;
        }

        return $check;
    }


}
