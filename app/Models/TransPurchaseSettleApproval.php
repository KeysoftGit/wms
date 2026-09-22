<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransPurchaseSettleApproval extends Model
{
    use HasFactory;

    protected $connection= 'sqlsrv';
    protected $table = 'Trans_PurchaseSettleApproval';

    protected $primaryKey = 'TransactionNo';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    public function supplier()
    {
        return $this->belongsTo(MsSupplier::class, 'SupplierID');
    }

    public function user()
    {
        return $this->belongsTo(MsUser::class, 'UserID');
    }
}
