<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransGoodsReceivingDT extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_GoodsReceivingDT';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'Qty' => 'double',
        'RateBeaMasuk' => 'double',
        'AntiDumping' => 'double',
    ];

    public function parent()
    {
        return $this->belongsTo(TransGoodsReceivingHD::class, 'TransactionNo');
    }

    public function part()
    {
        return $this->belongsTo(MsPart::class, 'PartID');
    }

    public function unit()
    {
        return $this->belongsTo(MsUnit::class, 'UnitID');
    }

    public function rateBeaAccount()
    {
        return $this->belongsTo(MsCOA::class, 'RateBeaAccount');
    }

    public function antiDumpingAccount()
    {
        return $this->belongsTo(MsCOA::class, 'AntiDumpingAccount');
    }
}
