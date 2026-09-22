<?php

namespace App\Http\Middleware;

use App\Models\MsUser;
use Illuminate\Http\Request;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class CustomAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next) {
        if(Config::get('database.connections.sqlsrv.host') != session('db_host') || Config::get('database.connections.sqlsrv.database') != session('db_database') || Config::get('database.connections.sqlsrv.port') != session('db_port')
            || Config::get('database.connections.sqlsrv.username') != session('db_user') || Config::get('database.connections.sqlsrv.password') != session('db_password')){
            DB::purge('sqlsrv');

            Config::set('database.connections.sqlsrv', [
                'driver' => 'sqlsrv',
                'host' => session('db_host'),
                'port' => session('db_port'),
                'database' => session('db_database'),
                'username' => session('db_user'),
                'password' => session('db_password')
            ]);
        }

        if (!Auth::check())  {
            $guid = session()->get('guid') ?? '';

            Auth::logout();

            session()->flush();
            session()->regenerate();

            return redirect()->route('login', ['guid' => $guid]);
        }

        return $next($request);
    }
}
