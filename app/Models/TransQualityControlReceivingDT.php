<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransQualityControlReceivingDT extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_QualityControlReceivingDT';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    public function parent()
    {
        return $this->belongsTo(TransQualityControlReceivingHD::class, 'TransactionNo');
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
