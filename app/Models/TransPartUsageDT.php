<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransPartUsageDT extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'Trans_PartUsageDT';

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

    public function part()
    {
        return $this->belongsTo(MsPart::class, 'PartID');
    }

    public function unit()
    {
        return $this->belongsTo(MsUnit::class, 'UnitID');
    }

    public function account()
    {
        return $this->belongsTo(MsCOA::class, 'AccountNo');
    }

    public function header()
    {
        return $this->belongsTo(TransPartUsageHD::class, 'TransactionNo');
    }
}
