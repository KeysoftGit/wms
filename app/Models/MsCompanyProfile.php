<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MsCompanyProfile extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Ms_CompanyProfile';

    protected $primaryKey = 'CompanyID';
    public $incrementing = false;
    protected $keyType = 'integer';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    public function country()
    {
        return $this->belongsTo(MsCountry::class, 'CountryID');
    }

    public function currency()
    {
        return $this->belongsTo(MsCurrency::class, 'CurrencyID');
    }
}
