<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 *
 * @package App\Models
 */

class MsPartUnit extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Ms_Part_Unit';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'Conversion' => 'double',
    ];

    public function unit1()
    {
        return $this->belongsTo(MsUnit::class, 'UnitID1');
    }

    public function unit2()
    {
        return $this->belongsTo(MsUnit::class, 'UnitID2');
    }
}
