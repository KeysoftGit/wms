<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Ms_User
 *
 * @property string $VariantID
 * @property string $VariantName
 * @property string $Notes
 * @property string $CreatedBy
 * @property string $EntryTime
 *
 * @package App\Models
 */

class MsPartVariant extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Ms_PartVariant';

    protected $primaryKey = 'VariantID';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];
}
