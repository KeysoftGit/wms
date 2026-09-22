<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransDirectPurchaseHD extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_DirectPurchaseHD';

    protected $primaryKey = 'TransactionNo';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'Freight' => 'double',
        'VATValue' => 'double',
        'SubTotal' => 'double',
        'GrandTotal' => 'double',
        'Rate' => 'double',
        'FiscalRate' => 'double',
        'PercentageServiceTax' => 'double',
        'ServiceTax' => 'double',
        'PercentageDisc' => 'double',
        'Discount' => 'double',
        'RevCount' => 'int',
    ];

    public function division()
    {
        return $this->belongsTo(MsDivision::class, 'DivisionID');
    }

    public function supplier()
    {
        return $this->belongsTo(MsSupplier::class, 'SupplierID');
    }

    public function currency()
    {
        return $this->belongsTo(MsCurrency::class, 'CurrencyID');
    }

    public function warehouse()
    {
        return $this->belongsTo(MsWarehouse::class, 'WarehouseID');
    }

    public function details()
    {
        return $this->hasMany(TransDirectPurchaseDT::class, 'TransactionNo');
    }
}
