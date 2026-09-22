<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MsDynamicReportHD extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'Ms_DynamicReportHD';
    protected $guarded = ['created_at', 'updated_at'];

    public function columns()
    {
        return $this->hasMany(MsDynamicReportDTColumn::class, 'HeaderID', 'id')->orderBy('Sequence');
    }

    public function filters()
    {
        return $this->hasMany(MsDynamicReportDTFilter::class, 'HeaderID', 'id');
    }

    public function variables()
    {
        return $this->hasMany(MsDynamicReportDTVariable::class, 'HeaderID', 'id');
    }
}
