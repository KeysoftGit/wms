<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Ms_User
 *
 * @property string $DivisionID
 * @property string $DivisionName
 * @property string $Notes
 * @property bool $Active
 * @property string $CreatedBy
 * @property string $EntryTime
 * @property string $LastUpdateBy
 * @property string $LastUpdate
 *
 * @package App\Models
 */

class MsDivision extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Ms_Division';

    protected $primaryKey = 'DivisionID';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    public function subDivision()
    {
        return $this->belongsTo(MsDivision::class, 'SubDivisionID');
    }

    public function warehouse()
    {
        return $this->hasOne(MsWarehouse::class, 'DivisionID');
    }
}
