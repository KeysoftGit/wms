<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransSalesOrderHD extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_SalesOrderHD';

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
        'Term' => 'double',
        'PPH' => 'double',
        'ServiceTax' => 'double',
        'PercentageDisc' => 'double',
        'Discount' => 'double',
        'RevCount' => 'int',
    ];

    public function division()
    {
        return $this->belongsTo(MsDivision::class, 'DivisionID');
    }

    public function customer()
    {
        return $this->belongsTo(MsCustomer::class, 'CustomerID');
    }

    public function salesman()
    {
        return $this->belongsTo(MsEmployee::class, 'SalesmanID');
    }

    public function currency()
    {
        return $this->belongsTo(MsCurrency::class, 'CurrencyID');
    }

    public function details()
    {
        return $this->hasMany(TransSalesOrderDT::class, 'TransactionNo');
    }
}
