<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Ms_User
 *
 * @property string $FixedAssetLocationID
 * @property string $FixedAssetLocationName
 * @property string $Notes
 * @property bool $Active
 * @property string $CreatedBy
 * @property string $EntryTime
 * @property string $LastUpdateBy
 * @property string $LastUpdate
 *
 * @package App\Models
 */

class MsFixedAssetLocation extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Ms_FixedAssetLocation';

    protected $primaryKey = 'LocationID';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];


    public function staff()
    {
        return $this->belongsTo(MsEmployee::class, 'StaffInChargeID');
    }
}
