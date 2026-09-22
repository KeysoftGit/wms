<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransInventoryAdjustmentDT extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_InventoryAdjustmentDT';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'QtyStock' => 'double',
        'QtyOpname' => 'double',
    ];

    public function parent()
    {
        return $this->belongsTo(TransInventoryAdjustmentHD::class, 'TransactionNo');
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
