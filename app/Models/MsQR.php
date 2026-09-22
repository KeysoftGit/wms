<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MsQR extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Ms_QR';

    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'json_value' => 'array',
        'json_display' => 'array',
        'show_content' => 'boolean',
    ];
}
