<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Ms_User
 *
 * @property string $SubDistrictID
 * @property string $SubDistrictName
 * @property string $Notes
 * @property string $DistrictID
 * @property string $CreatedBy
 * @property string $EntryTime
 *
 * @package App\Models
 */

class MsSubDistrict extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Ms_SubDistrict';

    protected $primaryKey = 'SubDistrictID';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];
}
