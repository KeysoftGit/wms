<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MsSupplier extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Ms_Supplier';

    protected $primaryKey = 'SupplierID';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'AccountPayableLimit' => 'double',
    ];


    public function country()
    {
        return $this->belongsTo(MsCountry::class, 'CountryID');
    }

        public function division()
    {
        return $this->belongsTo(MsDivision::class, 'DivisionID');
    }
}
