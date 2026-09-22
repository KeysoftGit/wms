<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransDeliveryOrderAmount extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_DeliveryOrderAmount';

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
        return $this->belongsTo(TransSalesOrderHD::class, 'TransactionNo');
    }
}
