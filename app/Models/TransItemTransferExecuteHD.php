<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransItemTransferExecuteHD extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Trans_ItemTransferExecuteHD';

    protected $primaryKey = 'TransactionNo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function requestHD()
    {
        return $this->belongsTo(TransItemTransferRequestHD::class, 'RequestNo', 'TransactionNo');
    }

    public function staffInChargeFrom()
    {
        return $this->belongsTo(MsEmployee::class, 'StaffInChargeFrom', 'EmployeeID');
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
        return $this->hasMany(TransItemTransferExecuteDT::class, 'TransactionNo', 'TransactionNo');
    }

    public function receives()
    {
        return $this->hasMany(TransItemTransferReceiveHD::class, 'ExecuteNo', 'TransactionNo');
    }

    public function getIsEditableAttribute()
    {
        if (array_key_exists('receives_count', $this->attributes)) {
            return (int) $this->attributes['receives_count'] == 0;
        }
        return !$this->receives()->exists();
    }
}
