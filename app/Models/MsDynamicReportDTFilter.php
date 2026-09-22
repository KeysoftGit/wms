<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MsDynamicReportDTFilter extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'Ms_DynamicReportDT_Filter';
    protected $guarded = ['created_at', 'updated_at'];
}
