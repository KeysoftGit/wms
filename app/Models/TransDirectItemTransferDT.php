<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransDirectItemTransferDT extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_DirectItemTransferDT';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'Qty' => 'double',
        'Conversion' => 'double',
    ];

    public function parent()
    {
        return $this->belongsTo(TransDirectItemTransferHD::class, 'TransactionNo');
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
