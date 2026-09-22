<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 *
 * @package App\Models
 */

class BukuHutang extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Buku_Hutang';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'Rate' => 'double',
        'Amount' => 'double',
    ];
}
