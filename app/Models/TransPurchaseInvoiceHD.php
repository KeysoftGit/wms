<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransPurchaseInvoiceHD extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_PurchaseInvoiceHD';

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
        'PPH22' => 'double',
        'PBBKB' => 'double',
        'PercentageDisc' => 'double',
        'Discount' => 'double',
        'RevCount' => 'int',
    ];

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
        return $this->hasMany(TransPurchaseInvoiceDT::class, 'TransactionNo');
    }

    public function dt3()
    {
        return $this->hasMany(TransPurchaseInvoiceDT3::class, 'TransactionNo');
    }

    public function dp()
    {
        return $this->hasMany(DPPurchaseInvoice::class, 'InvoiceNo');
    }
}
