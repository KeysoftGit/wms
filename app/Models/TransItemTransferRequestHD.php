<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransItemTransferRequestHD extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Trans_ItemTransferRequestHD';

    public $timestamps = false;
    
    protected $primaryKey = 'TransactionNo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['id'];

    public function warehouseFrom()
    {
        return $this->belongsTo(MsWarehouse::class, 'WarehouseIDFrom');
    }

    public function warehouseTo()
    {
        return $this->belongsTo(MsWarehouse::class, 'WarehouseIDTo');
    }

    public function staffFrom()
    {
        return $this->belongsTo(MsEmployee::class, 'StaffInChargeIDFrom');
    }

    public function staffTo()
    {
        return $this->belongsTo(MsEmployee::class, 'StaffInChargeIDTo');
    }

    public function vehicle()
    {
        return $this->belongsTo(MsVehicle::class, 'VehicleID');
    }

    public function driver()
    {
        return $this->belongsTo(MsEmployee::class, 'DriverID');
    }

    public function supplier()
    {
        return $this->belongsTo(MsSupplier::class, 'SupplierID');
    }

    public function details()
    {
        return $this->hasMany(TransItemTransferRequestDT::class, 'TransactionNo', 'TransactionNo');
    }

    public function createdByUser()
    {
        return $this->belongsTo(MsUser::class, 'CreatedBy', 'UserID');
    }

    public function lastUpdatedByUser()
    {
        return $this->belongsTo(MsUser::class, 'LastUpdateBy', 'UserID');
    }

    public function executes()
    {
        return $this->hasMany(TransItemTransferExecuteHD::class, 'RequestNo', 'TransactionNo');
    }

    public function getIsEditableAttribute()
    {
        if (array_key_exists('executes_count', $this->attributes)) {
            return (int) $this->attributes['executes_count'] === 0;
        }

        return !$this->executes()->exists();
    }
}
