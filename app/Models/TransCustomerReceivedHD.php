<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransCustomerReceivedHD extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_CustomerReceivedHD';

    protected $primaryKey = 'TransactionNo';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'Rate' => 'double',
        'FiscalRate' => 'double',
    ];

    public function customer()
    {
        return $this->belongsTo(MsCustomer::class, 'CustomerID');
    }

    public function currency()
    {
        return $this->belongsTo(MsCurrency::class, 'CurrencyID');
    }

    public function account()
    {
        return $this->belongsTo(MsCOA::class, 'BankAccountNo');
    }

    public function details()
    {
        return $this->hasMany(TransCustomerReceivedDT::class, 'TransactionNo');
    }
}
