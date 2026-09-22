<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MsFixedAsset extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Ms_FixedAsset';

    protected $primaryKey = 'FixedAssetCode';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'OriginalCostValue' => 'double',
        'OriginalRate' => 'double',
        'MinimumResidualPercentage' => 'double'
    ];

    public function division()
    {
        return $this->belongsTo(MsDivision::class, 'DivisionID');
    }

    public function location()
    {
        return $this->belongsTo(MsFixedAssetLocation::class, 'CurrentLocationID');
    }

    public function category()
    {
        return $this->belongsTo(MsFixedAssetCategory::class, 'CategoryID');
    }

    public function currency()
    {
        return $this->belongsTo(MsCurrency::class, 'OriginalCurrency');
    }

    public function part()
    {
        return $this->belongsTo(MsPart::class, 'PartID');
    }
}
