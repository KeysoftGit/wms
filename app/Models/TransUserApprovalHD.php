<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransUserApprovalHD extends Model
{
    use HasFactory;

    protected $connection= 'sqlsrv';
    protected $table = 'Trans_UserApprovalHD';

    protected $primaryKey = 'TransactionNo';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    public function division()
    {
        return $this->belongsTo(MsDivision::class, 'DivisionID');
    }

    public function details()
    {
        return $this->hasMany(TransUserApprovalDT::class, 'TransactionNo');
    }
}
