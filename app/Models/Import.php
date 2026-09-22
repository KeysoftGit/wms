<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 *
 * @package App\Models
 */

class Import extends Model
{
    protected $connection= 'sqlsrv';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];
}
