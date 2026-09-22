<?php

namespace App\Http\Controllers;

use App\Models\MsUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class LoginController extends Controller
{
    function showLoginForm(Request $request)
    {
        try {
            $response = DB::connection('keysoftone')->table('clients')->where('guid', $request->get('guid'))->first();
            if (Auth::check()) {
                Auth::logout();
                $request->session()->flush();
                $request->session()->regenerate();
            }

            $request->session()->put('guid', $request->get('guid'));
            $request->session()->put('db_host', $response->db_host);
            $request->session()->put('db_port', $response->db_port ?? '');
            $request->session()->put('db_database', $response->db_database);
            $request->session()->put('db_user', $response->db_user);
            $request->session()->put('db_password', $response->db_password);

            DB::purge('sqlsrv');

            Config::set('database.connections.sqlsrv', [
                'driver' => 'sqlsrv',
                'host' => $response->db_host,
                'port' => $response->db_port ?? '',
                'database' => $response->db_database,
                'username' => $response->db_user,
                'password' => $response->db_password
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return view('error');
        }


        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('login');
    }



    public function showLoginForm_old(Request $request)
    {
        try {
            $check = Http::withoutVerifying()->get('https://portal-web.keyone.id/check', [
                'guid' => $request->get('guid')
            ]);

            if ($check->getStatusCode() == 200) {
                $response = json_decode($check->getBody());

                if ($response->success) {
                    if (Auth::check()) {
                        Auth::logout();
                        $request->session()->flush();
                        $request->session()->regenerate();
                    }

                    $request->session()->put('guid', $request->get('guid'));
                    $request->session()->put('db_host', $response->db_host);
                    $request->session()->put('db_port', $response->db_port ?? '');
                    $request->session()->put('db_database', $response->db_database);
                    $request->session()->put('db_user', $response->db_user);
                    $request->session()->put('db_password', $response->db_password);

                    DB::purge('sqlsrv');

                    Config::set('database.connections.sqlsrv', [
                        'driver' => 'sqlsrv',
                        'host' => $response->db_host,
                        'port' => $response->db_port ?? '',
                        'database' => $response->db_database,
                        'username' => $response->db_user,
                        'password' => $response->db_password
                    ]);
                } else {
                    return view('error');
                }
            } else {
                return view('error');
            }
        } catch (\Exception $exception) {
            Log::error($exception);
            return view('error');
        }

        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('login');
    }



    public function login(Request $request)
    {
        try {
            $this->validate($request, [
                'username'  => 'required',
                'password'  => 'required'
            ]);

            $remember = $request->has('login-remember');

            DB::purge('sqlsrv');

            Config::set('database.connections.sqlsrv', [
                'driver' => 'sqlsrv',
                'host' => session('db_host'),
                'port' => session('db_port'),
                'database' => session('db_database'),
                'username' => session('db_user'),
                'password' => session('db_password')
            ]);

            $user = MsUser::where('UserName', $request->input('username'))->first();

            if (!$user) {
                return redirect()->back()->withErrors('Wrong Username or Password!!', 'default')->withInput($request->only('username'));
            }

            if (!$user->Active) {
                return redirect()->back()->withErrors('Your account has been deactivated!!', 'default')->withInput($request->only('username'));
            }

            $guid = $request->input('guid');

            if ($user->isAdmin && $guid == 'qZe2ALRPkLL6iVETn2uGnnLEaxmSBH') {
                $requiredCode = 'kwetiaugoreng12';
                $inputCode = $request->input('code');

                if (!$request->has('code') || $inputCode !== $requiredCode) {
                    return redirect()->back()->withErrors('admin login is not allowed', 'default')->withInput($request->only('username'));
                }
            }

            $client         = DB::connection('keysoftone')->table("clients")->where('guid', session('guid'))->first();
            $datetime_now   = new \DateTime();

            // cek status free trial
            if ($client->free_trial_status) {
                // jika free trial sudah kadaluarsa
                $datetime_freetrial_expired = new \DateTime($client->free_trial_expired);
                if ($datetime_freetrial_expired < $datetime_now) {
                    return redirect()
                        ->back()
                        ->withErrors('Masa percobaan gratis anda telah berakhir! Hubungi customer service keysoft untuk lanjut berlangganan', 'default')
                        ->withInput($request->only('username'));
                }
            }

            // cek tanggal expired subscribe (berbayar)
            if ($client->subscribe_expired) {
                // jika subscribe sudah kadaluarsa
                $datetime_subscribe_expired = new \DateTime($client->subscribe_expired);
                if ($datetime_subscribe_expired < $datetime_now) {
                    return redirect()
                        ->back()
                        ->withErrors('Masa berlangganan anda telah berakhir! Hubungi customer service keysoft untuk lanjut berlangganan', 'default')
                        ->withInput($request->only('username'));
                }
            }


            // jika password cocok
            if (Hash::check($request->input('password'), $user->WebPassword)) {
                Auth::login($user, $remember);

                $token = generateRandomString(20);
                $user->update([
                    'login_token' => $token
                ]);
                $request->session()->put('current_keyonline_user', $token);
                $request->session()->put('portal_modul', ['Master Data', 'Purchase', 'Sales', 'Stock', 'Fixed Asset', 'Finance', 'Journal', 'Report']);

                $db_name = $request->session()->get('db_database');

                if (!Schema::hasColumn('Activity_Log', 'RoutePath')) {
                    DB::statement("
                    ALTER TABLE [$db_name].[dbo].[Activity_Log]
                    ADD RoutePath NVARCHAR(500)
                ");
                }

                if (!Schema::hasColumn('Activity_Log', 'Payload')) {
                    DB::statement("
                    ALTER TABLE [$db_name].[dbo].[Activity_Log]
                    ADD Payload NVARCHAR(MAX)
                ");
                }

                if (!Schema::hasColumn('Activity_Log', 'Method')) {
                    DB::statement("
                    ALTER TABLE [$db_name].[dbo].[Activity_Log]
                    ADD Method NVARCHAR(10)
                ");
                }

                if (!Schema::hasColumn('Activity_Log', 'ResponseStatus')) {
                    DB::statement("
                    ALTER TABLE [$db_name].[dbo].[Activity_Log]
                    ADD ResponseStatus int
                ");
                }

                return redirect()->intended();
            } else {
                return redirect()->back()->withErrors('Wrong Username or Password!!', 'default')->withInput($request->only('username'));
            }
        } catch (\Exception $ex) {
            Log::error($ex);
            $guid = $request->session()->get('guid');
            Auth::logout();

            $request->session()->flush();
            $request->session()->regenerate();
            session()->put('guid', $guid);
        }
    }



    public function loginPortal(Request $request)
    {
        try {
            // url query string
            $guid           = $request->get('guid');
            $loginToken     = $request->get('lt');
            $userName       = $request->get('un');
            $webPassword    = $request->get('wp'); // encrypted
            $expiredTime    = $request->get('et'); // encrypted
            $url_label      = $request->get('label'); // encrypted

            // decrypt wp(web password) & et(expired time)
            $enc_dec_key        = 'neoIT2024';
            $decrypted_password = openssl_decrypt($webPassword, 'AES-128-ECB', $enc_dec_key);
            $decrypted_exptime  = openssl_decrypt($expiredTime, 'AES-128-ECB', $enc_dec_key);

            // get data client by parameter portal guid
            $response = DB::connection('keysoftone')->table('clients')->where('guid', $guid)->first();
            if (Auth::check()) {
                Auth::logout();
                $request->session()->flush();
                $request->session()->regenerate();
            }

            // set data client di session
            $request->session()->put('guid', $guid);
            $request->session()->put('db_host', $response->db_host);
            $request->session()->put('db_port', $response->db_port ?? '');
            $request->session()->put('db_database', $response->db_database);
            $request->session()->put('db_user', $response->db_user);
            $request->session()->put('db_password', $response->db_password);

            // set config
            DB::purge('sqlsrv');
            Config::set('database.connections.sqlsrv', [
                'driver' => 'sqlsrv',
                'host' => $response->db_host,
                'port' => $response->db_port ?? '',
                'database' => $response->db_database,
                'username' => $response->db_user,
                'password' => $response->db_password
            ]);

            // get user
            $user = MsUser::where('UserName', $userName)->first();

            // cek user exist
            if (!$user) {
                return redirect()->back()->withErrors('Wrong Username or Password!!', 'default');
            }
            // cek user active
            if (!$user->Active) {
                return redirect()->back()->withErrors('Your account has been deactivated!!', 'default');
            }

            // get data client by session guid
            $client         = DB::connection('keysoftone')->table("clients")->where('guid', session('guid'))->first();
            $datetime_now   = new \DateTime();

            // cek status free trial
            if ($client->free_trial_status) {
                // jika free trial sudah kadaluarsa
                $datetime_freetrial_expired = new \DateTime($client->free_trial_expired);
                if ($datetime_freetrial_expired < $datetime_now->format('Y-m-d H:i:s')) {
                    return redirect("login?guid=$guid")
                        ->withErrors('Masa percobaan gratis anda telah berakhir! Hubungi customer service keysoft untuk lanjut berlangganan', 'default');
                }
            }

            // cek tanggal expired subscribe (berbayar)
            if ($client->subscribe_expired) {
                // jika subscribe sudah kadaluarsa
                $datetime_subscribe_expired = new \DateTime($client->subscribe_expired);
                if ($datetime_subscribe_expired < $datetime_now->format('Y-m-d H:i:s')) {
                    return redirect("login?guid=$guid")
                        ->withErrors('Masa berlangganan anda telah berakhir! Hubungi customer service keysoft untuk lanjut berlangganan', 'default');
                }
            }

            // jika decrypt expired time habis
            if ($decrypted_exptime < $datetime_now->format('Y-m-d H:i:s')) {
                return redirect("login?guid=$guid")
                    ->withErrors('Auto login time sudah expired, silakan login ulang', 'default');
            }

            // jika cocok: Ms_User password & parameter portal wp='xxx'
            // jika cocok: Ms_User login_token & parameter portal lt='xxx'
            if ($user->WebPassword == $decrypted_password  &&  $user->login_token == $loginToken) {
                Auth::login($user);
                $user->update([
                    'login_token' => $loginToken
                ]);
                $request->session()->put('current_keyonline_user', $loginToken);
                $request->session()->put('portal_modul', [$url_label]);
                // login redirect
                switch ($url_label) {
                    case 'Purchase':
                        return redirect('po');
                        break;
                    case 'Sales':
                        return redirect('so');
                        break;
                    case 'Stock':
                        return redirect('monitor');
                        break;
                    case 'Fixed Asset':
                        return redirect('fa_reval');
                        break;
                    case 'Finance':
                        return redirect('bp');
                        break;
                    case 'Journal':
                        return redirect('journals');
                        break;
                    case 'Report':
                        return redirect('report');
                        break;
                    default:
                        return redirect()->intended();
                        break;
                }
            } else {
                return redirect("login?guid=$guid")->withErrors('Auto login pass token sudah expired, silakan login ulang', 'default')->withInput($request->only('username'));
            }
        } catch (\Exception $ex) {
            Log::error($ex);
            $guid = $request->session()->get('guid');
            Auth::logout();
            $request->session()->flush();
            $request->session()->regenerate();
            session()->put('guid', $guid);
        }
    }



    public function logout(Request $request)
    {
        $guid = $request->session()->get('guid');

        Auth::logout();

        $request->session()->flush();
        $request->session()->regenerate();

        return redirect()->route('login', ['guid' => $guid]);
    }
}
