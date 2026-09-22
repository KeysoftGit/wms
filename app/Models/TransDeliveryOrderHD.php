<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransDeliveryOrderHD extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_DeliveryOrderHD';

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
        'RevCount' => 'int',
    ];

    public function reff()
    {
        return $this->belongsTo(TransSalesOrderHD::class, 'ReffNumber');
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
        return $this->hasMany(TransDeliveryOrderDT::class, 'TransactionNo');
    }
}
