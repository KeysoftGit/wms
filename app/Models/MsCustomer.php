<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Ms_User
 *
 * @property string $CustomerID
 * @property bool $Active
 * @property string $CreatedBy
 * @property string $EntryTime
 * @property string $LastUpdateBy
 * @property string $LastUpdate
 *
 * @package App\Models
 */

class MsCustomer extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Ms_Customer';

    protected $primaryKey = 'CustomerID';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'CreditLimit' => 'double',
        'InvoiceLimit' => 'double',
    ];

    public function division()
    {
        return $this->belongsTo(MsDivision::class, 'DivisionID');
    }

    public function country()
    {
        return $this->belongsTo(MsCountry::class, 'CountryID');
    }

    public function salesman()
    {
        return $this->belongsTo(MsEmployee::class, 'SalesmanID');
    }

    public function subdistrict()
    {
        return $this->belongsTo(MsSubDistrict::class, 'SubDistrictID');
    }

    public function shipments()
    {
        return $this->hasMany(MsCustomerShipment::class, 'CustomerID');
    }
}
