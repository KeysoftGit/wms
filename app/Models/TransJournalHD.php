<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransJournalHD extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_JournalHD';

    protected $primaryKey = 'TransactionNo';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    public function customer()
    {
        return $this->belongsTo(MsCustomer::class, 'CustomerID');
    }

    public function supplier()
    {
        return $this->belongsTo(MsSupplier::class, 'SupplierID');
    }

    public function details()
    {
        return $this->hasMany(TransJournalDT::class, 'TransactionNo');
    }
}
