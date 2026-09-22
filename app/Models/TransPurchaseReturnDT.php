<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransPurchaseReturnDT extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_PurchaseReturnDT';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'Qty' => 'double',
        'Conversion' => 'double',
        'UnitPrice' => 'double',
        'PercentageDisc1' => 'double',
        'Discount1' => 'double',
        'PercentageDisc2' => 'double',
        'Discount2' => 'double',
        'PercentageDisc' => 'double',
        'Discount' => 'double',
        'RateBeaMasuk' => 'double',
        'AntiDumping' => 'double',
    ];

    public function parent()
    {
        return $this->belongsTo(TransPurchaseReturnHD::class, 'TransactionNo');
    }

    public function part()
    {
        return $this->belongsTo(MsPart::class, 'PartID');
    }

    public function unit()
    {
        return $this->belongsTo(MsUnit::class, 'UnitID');
    }

    public function division()
    {
        return $this->belongsTo(MsDivision::class, 'DivisionID');
    }

    public function warehouse()
    {
        return $this->belongsTo(MsWarehouse::class, 'WarehouseID');
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
