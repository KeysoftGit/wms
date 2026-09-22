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

class Menu extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'menus';

    protected $fillable = [
        'Name',
        'DisplayName',
        'actions',
    ];
}
