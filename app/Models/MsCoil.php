<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MsCoil extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Ms_Coil';
    protected $primaryKey = 'CoilID';
    public $timestamps = false;
    protected $guarded = [];
}
