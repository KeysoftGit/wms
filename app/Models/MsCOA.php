<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Ms_User
 *
 * @property string $AccountNo
 * @property string $AccountName
 * @property string $Notes
 * @property bool $Active
 * @property string $CreatedBy
 * @property string $EntryTime
 * @property string $LastUpdateBy
 * @property string $LastUpdate
 *
 * @package App\Models
 */

class MsCOA extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Ms_COA';

    protected $primaryKey = 'AccountNo';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    public function currency()
    {
        return $this->belongsTo(MsCurrency::class, 'CurrencyID');
    }

    public function parent()
    {
        return $this->belongsTo(MsCOA::class, 'Parent');
    }
}
