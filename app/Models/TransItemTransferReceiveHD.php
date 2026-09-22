<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransItemTransferReceiveHD extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Trans_ItemTransferReceiveHD';

    protected $primaryKey = 'TransactionNo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function executeHD()
    {
        return $this->belongsTo(TransItemTransferExecuteHD::class, 'ExecuteNo', 'TransactionNo');
    }

    public function staffInChargeTo()
    {
        return $this->belongsTo(MsEmployee::class, 'StaffInChargeTo', 'EmployeeID');
    }

    public function warehouseTo()
    {
        return $this->belongsTo(MsWarehouse::class, 'WarehouseIDTo', 'WarehouseID');
    }

    public function createdByUser()
    {
        return $this->belongsTo(MsUser::class, 'CreatedBy', 'UserID');
    }

    public function lastUpdatedByUser()
    {
        return $this->belongsTo(MsUser::class, 'LastUpdateBy', 'UserID');
    }

    public function details()
    {
        return $this->hasMany(TransItemTransferReceiveDT::class, 'TransactionNo', 'TransactionNo');
    }
}
