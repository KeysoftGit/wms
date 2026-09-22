<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 *
 * @package App\Models
 */

class MsRevAlias extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Ms_RevAlias';
}
