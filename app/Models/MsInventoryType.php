<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Ms_User
 *
 * @property string $InventoryTypeID
 * @property string $InventoryTypeName
 * @property string $Notes
 * @property string $CreatedBy
 * @property string $EntryTime
 *
 * @package App\Models
 */

class MsInventoryType extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Ms_InventoryType';

    protected $primaryKey = 'InventoryTypeID';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];
}
