<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransUserWarehouseHD extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Trans_UserWarehouseHD';
    protected $primaryKey = 'UserID';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'EffectiveDate' => 'datetime',
        'EntryTime' => 'datetime',
        'LastUpdate' => 'datetime',
    ];
}
