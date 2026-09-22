<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MsBatchNo extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Ms_BatchNo';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    protected $guarded = [];

    public function part()
    {
        return $this->belongsTo(MsPart::class, 'PartID', 'PartID');
    }
}
