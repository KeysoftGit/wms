<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransGoodsReceivingHD extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_GoodsReceivingHD';

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
        'Rate' => 'double',
        'RevCount' => 'int',
    ];

    public function qc()
    {
        return $this->belongsTo(TransQualityControlReceivingHD::class, 'QCNumber');
    }
    public function warehouse()
    {
        return $this->belongsTo(MsWarehouse::class, 'WarehouseID');
    }

    public function currency()
    {
        return $this->belongsTo(MsCurrency::class, 'CurrencyID');
    }

    public function details()
    {
        return $this->hasMany(TransGoodsReceivingDT::class, 'TransactionNo');
    }
}
