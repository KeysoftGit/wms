<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Ms_User
 *
 * @property string $CreatedBy
 * @property string $EntryTime
 *
 * @package App\Models
 */

class MsAccountMappingType extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Ms_AccountMapping_Type';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    public function account()
    {
        return $this->belongsTo(MsCOA::class, 'AccountNo');
    }
}
