<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransInventoryAdjustmentHD extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_InventoryAdjustmentHD';

    protected $primaryKey = 'TransactionNo';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    public function warehouse()
    {
        return $this->belongsTo(MsWarehouse::class, 'WarehouseID');
    }

    public function division()
    {
        return $this->belongsTo(MsDivision::class, 'DivisionID');
    }

    public function type()
    {
        return $this->belongsTo(MsInventoryType::class, 'InventoryTypeID');
    }

    public function details()
    {
        return $this->hasMany(TransInventoryAdjustmentDT::class, 'TransactionNo');
    }

    public function checkers()
    {
        return $this->hasMany(TransInventoryAdjustmentCheckers::class, 'TransactionNo');
    }
}
