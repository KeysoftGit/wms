<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Ms_User
 *
 * @property string $FixedAssetCategoryID
 * @property string $FixedAssetCategoryName
 * @property string $Notes
 * @property bool $Active
 * @property string $CreatedBy
 * @property string $EntryTime
 * @property string $LastUpdateBy
 * @property string $LastUpdate
 *
 * @package App\Models
 */

class MsFixedAssetCategory extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Ms_FixedAssetCategory';

    protected $primaryKey = 'CategoryID';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'AgeInYear' => 'int',
        'AgeInMonth' => 'int',
    ];


    public function assetAccount()
    {
        return $this->belongsTo(MsCOA::class, 'FixedAssetAccount');
    }

    public function deAccount()
    {
        return $this->belongsTo(MsCOA::class, 'DepreciationExpenseAccount');
    }

    public function adAccount()
    {
        return $this->belongsTo(MsCOA::class, 'AccumulatedDepreciationAccount');
    }
}
