<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransDeliveryOrderDT extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_DeliveryOrderDT';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'Qty' => 'double',
    ];

    public function parent()
    {
        return $this->belongsTo(TransDeliveryOrderHD::class, 'TransactionNo');
    }

    public function part()
    {
        return $this->belongsTo(MsPart::class, 'PartID');
    }

    public function unit()
    {
        return $this->belongsTo(MsUnit::class, 'UnitID');
    }

    public function unit2()
    {
        return $this->belongsTo(MsUnit::class, 'UnitID2');
    }

    public function division()
    {
        return $this->belongsTo(MsDivision::class, 'DivisionID');
    }

    public function warehouse()
    {
        return $this->belongsTo(MsWarehouse::class, 'WarehouseID');
    }
}
