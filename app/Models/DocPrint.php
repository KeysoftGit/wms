<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 *
 * @package App\Models
 */

class DocPrint extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Doc_Print';

    // Jika ada timestamps
    public $timestamps = false; // karena ada CreatedAt dan UpdatedAt sebagai kolom biasa

    // Jika kolom CreatedAt dan UpdatedAt bukan timestamp Laravel
    const CREATED_AT = 'CreatedAt';
    const UPDATED_AT = 'UpdatedAt';

    protected $fillable = [
        'Id',
        'Code',
        'ModuleCode',
        'ModuleName',
        'Type',
        'TypeStr',
        'Path',
        'Filename',
        'CreatedAt',
        'UpdatedAt',
        'Params',
        'FastReport'
    ];
}
