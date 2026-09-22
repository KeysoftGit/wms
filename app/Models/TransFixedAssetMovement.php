<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransFixedAssetMovement extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_FixedAssetMovement';

    protected $primaryKey = 'TransactionNo';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    public function fixedAsset()
    {
        return $this->belongsTo(MsFixedAsset::class, 'FixedAssetCode');
    }

    public function source()
    {
        return $this->belongsTo(MsFixedAssetLocation::class, 'SourceLocationID');
    }

    public function destination()
    {
        return $this->belongsTo(MsFixedAssetLocation::class, 'DestinationLocationID');
    }
}
