<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MsDynamicReportDTVariable extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'Ms_DynamicReportDT_Variable';
    protected $guarded = ['created_at', 'updated_at'];
}
