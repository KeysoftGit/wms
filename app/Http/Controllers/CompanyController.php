<?php

namespace App\Http\Controllers;

use App\Models\MsCompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function index(){
        $company = MsCompanyProfile::find(1);
        if(!$company){
            $company = MsCompanyProfile::create([
                'CompanyID' => 1,
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
            ]);
        }

        return view('company.index', compact('company'));
    }

    public function edit(){
        $company = MsCompanyProfile::find(1);

        return view('company.edit', compact('company'));
    }

    public function update(Request $request){
        $this->validate($request, [
            'CompanyName' => 'nullable|string|max:255',
            'City' => 'nullable|string|max:50',
            'PostalCode' => 'nullable|string|max:50',
            'Phone' => 'nullable|string|max:50',
            'Email' => 'nullable|string|max:50',
            'Website' => 'nullable|string|max:50',
            'NPWP' => 'nullable|string|max:50',
            'SIUP' => 'nullable|string|max:50',
            'API' => 'nullable|string|max:50',
            'TypeOfBusiness' => 'nullable|string|max:255',
            'ContactPerson' => 'nullable|string|max:50',
            'RegistrationCompanyName' => 'nullable|string|max:100',
        ], [
            'CompanyName.max' => 'Compnay Name maximum characters is 255!',
            'NPWP.max' => 'NPWP maximum characters is 50!',
            'City.max' => 'City maximum characters is 50!',
            'PostalCode.max' => 'Postal Code maximum characters is 50!',
            'Phone.max' => 'Phone maximum characters is 50!',
            'Email.max' => 'Email maximum characters is 50!',
            'Website.max' => 'Website maximum characters is 50!',
            'SIUP.max' => 'SIUP No maximum characters is 50!',
            'API.max' => 'API No maximum characters is 50!',
            'ContactPerson.max' => 'Contact Person maximum characters is 50!',
            'TypeOfBusiness.max' => 'Type Of Business maximum characters is 255!',
            'RegistrationCompanyName.max' => 'Contact Person maximum characters is 100!',
        ]);

        try {
            $company = MsCompanyProfile::find(1);

            $filename = '';

            if($request->file('Logo')){
                if($company->Logo != null && $company->Logo != ''){
                    Storage::disk('company_logo')->delete($company->Logo);
                }

                $file = $request->file('Logo');
                $filename = '1_logo_' . date('d_m_Y_H_i_s') . '.' . $file->extension();

                $file->storeAs('', $filename, 'company_logo');
            }

            $company->update([
                'CompanyName' => $request->input('CompanyName') ? trim($request->input('CompanyName')) : null,
                'ContactPerson' => $request->input('ContactPerson') ? trim($request->input('ContactPerson')) : null,
                'NPWP' => $request->input('NPWP') ? trim($request->input('NPWP')) : null,
                'Address' => $request->input('Address') ? trim($request->input('Address')) : null,
                'City' => $request->input('City') ? trim($request->input('City')) : null,
                'CountryID' => $request->input('CountryID') ?? null,
                'CurrencyID' => $request->input('CurrencyID') ?? null,
                'PostalCode' => $request->input('PostalCode') ? trim($request->input('PostalCode')) : null,
                'Phone' => $request->input('Phone') ? trim($request->input('Phone')) : null,
                'Email' => $request->input('Email') ? trim($request->input('Email')) : null,
                'Website' => $request->input('Website') ? trim($request->input('Website')) : null,
                'SIUP' => $request->input('SIUP') ? trim($request->input('SIUP')) : null,
                'API' => $request->input('Email') ? trim($request->input('API')) : null,
                'TypeOfBusiness' => $request->input('Email') ? trim($request->input('TypeOfBusiness')) : null,
                'RegistrationCompanyName' => $request->input('Email') ? trim($request->input('RegistrationCompanyName')) : null,
                'FreezePrice' => $request->input('FreezePrice') ?? null,
                'Logo2' => $request->file('Logo') ? $filename : $company->Logo2
            ]);

            return redirect()->route('company')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Company Profile successfully updated!'
                ]);
        } catch (\Exception $e){
            Log::error($e);

            return redirect()->back()->withInput()->withErrors([
                'Something went wrong!'
            ]);
        }
    }
}
