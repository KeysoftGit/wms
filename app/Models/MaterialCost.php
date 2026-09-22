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

class MaterialCost extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Material_Cost';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'BeginningBalance' => 'double',
        'EndingBalance' => 'double',
        'EndingBalanceMKT' => 'double'
    ];

    public function part()
    {
        return $this->belongsTo(MsPart::class, 'PartID');
    }
}
