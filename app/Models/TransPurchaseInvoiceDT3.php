<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransPurchaseInvoiceDT3 extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_PurchaseInvoiceDT3';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'Amount' => 'double',
    ];

    public function parent()
    {
        return $this->belongsTo(TransPurchaseInvoiceHD::class, 'TransactionNo');
    }
}
