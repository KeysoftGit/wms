<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransUserWarehouseDT extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Trans_UserWarehouseDT';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
}
