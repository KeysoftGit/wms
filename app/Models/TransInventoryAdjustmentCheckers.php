<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransInventoryAdjustmentCheckers extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_InventoryAdjustment_Checkers';

    protected $primaryKey = 'TransactionNo';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    public function parent()
    {
        return $this->belongsTo(TransInventoryAdjustmentHD::class, 'TransactionNo');
    }

    public function employee()
    {
        return $this->belongsTo(MsEmployee::class, 'EmployeeID');
    }
}
