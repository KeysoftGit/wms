<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransItemTransferRequestDT extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Trans_ItemTransferRequestDT';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'Qty' => 'double',
    ];

    public function parent()
    {
        return $this->belongsTo(TransItemTransferRequestHD::class, 'TransactionNo', 'TransactionNo');
    }

    public function header()
    {
        return $this->belongsTo(TransItemTransferRequestHD::class, 'TransactionNo', 'TransactionNo');
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
