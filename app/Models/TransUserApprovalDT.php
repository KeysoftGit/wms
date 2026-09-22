<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransUserApprovalDT extends Model
{
    use HasFactory;

    protected $connection= 'sqlsrv';
    protected $table = 'Trans_UserApprovalDT';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'AmountTo' => 'double',
        'AmountFrom' => 'double',
    ];

    public function parent()
    {
        return $this->belongsTo(TransUserApprovalHD::class, 'TransactionNo');
    }

    public function user()
    {
        return $this->belongsTo(MsUser::class, 'UserID');
    }
}
