<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockReport extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'Stock_Report';

    protected $guarded = ['created_at', 'updated_at'];

    public function warehouse()
    {
        return $this->belongsTo(MsWarehouse::class, 'WarehouseID', 'WarehouseID');
    }

    public function part()
    {
        return $this->belongsTo(MsPart::class, 'PartID');
    }

    public function unit()
    {
        return $this->belongsTo(MsUnit::class, 'UnitID');
    }
}
