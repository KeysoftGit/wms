<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransFixedAssetDisposal extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_FixedAssetDisposal';

    protected $primaryKey = 'TransactionNo';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'DisposalValue' => 'double',
    ];

    public function fixedAsset()
    {
        return $this->belongsTo(MsFixedAsset::class, 'FixedAssetCode');
    }

    public function account()
    {
        return $this->belongsTo(MsCOA::class, 'AccountNo');
    }
}
