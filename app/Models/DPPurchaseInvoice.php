<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DPPurchaseInvoice extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'DP_PurchaseInvoice';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'Amount' => 'double',
        'Rate' => 'double',
    ];
}
