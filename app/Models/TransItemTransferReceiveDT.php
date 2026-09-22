<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransItemTransferReceiveDT extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Trans_ItemTransferReceiveDT';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'Qty' => 'double',
    ];

    public function parent()
    {
        return $this->belongsTo(TransItemTransferReceiveHD::class, 'TransactionNo', 'TransactionNo');
    }

    public function part()
    {
        return $this->belongsTo(MsPart::class, 'PartID');
    }

    public function unit()
    {
        return $this->belongsTo(MsUnit::class, 'UnitID');
    }
}
