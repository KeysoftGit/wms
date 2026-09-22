<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransStockOpnameHD extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_StockOpnameHD';

    protected $primaryKey = 'TransactionNo';
    public $incrementing = false;
    protected $keyType = 'string';

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

    public function details()
    {
        return $this->hasMany(TransStockOpnameDT::class, 'TransactionNo');
    }

    public function checkers()
    {
        return $this->hasMany(TransStockOpnameChecker::class, 'TransactionNo');
    }
}
