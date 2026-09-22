<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransOtherRevenueHD extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_OtherRevenueHD';

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
        'GrandTotal' => 'double',
        'Rate' => 'double',
        'FiscalRate' => 'double',
    ];

    public function division()
    {
        return $this->belongsTo(MsDivision::class, 'DivisionID');
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
        return $this->hasMany(TransOtherRevenueDT::class, 'TransactionNo');
    }
}
