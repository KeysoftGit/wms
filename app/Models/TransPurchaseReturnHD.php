<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransPurchaseReturnHD extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_PurchaseReturnHD';

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
        'VATValue' => 'double',
        'SubTotal' => 'double',
        'GrandTotal' => 'double',
        'Rate' => 'double',
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

    public function details()
    {
        return $this->hasMany(TransPurchaseReturnDT::class, 'TransactionNo');
    }
}
