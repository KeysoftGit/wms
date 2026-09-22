<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransQualityControlReceivingHD extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_QualityControlReceivingHD';

    protected $primaryKey = 'TransactionNo';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    public function po()
    {
        return $this->belongsTo(TransPurchaseOrderHD::class, 'PONumber');
    }

    public function warehouse()
    {
        return $this->belongsTo(MsWarehouse::class, 'WarehouseID');
    }
}
