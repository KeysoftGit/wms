<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransDirectItemTransferHD extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_DirectItemTransferHD';

    protected $primaryKey = 'TransactionNo';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

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

    public function supplier()
    {
        return $this->belongsTo(MsSupplier::class, 'SupplierID');
    }

    public function vehicle()
    {
        return $this->belongsTo(MsVehicle::class, 'VehicleID');
    }

    public function driver()
    {
        return $this->belongsTo(MsEmployee::class, 'DriverID');
    }

    public function details()
    {
        return $this->hasMany(TransDirectItemTransferDT::class, 'TransactionNo');
    }
}
