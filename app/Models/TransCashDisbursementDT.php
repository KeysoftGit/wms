<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransCashDisbursementDT extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_CashDisbursementDT';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'Amount' => 'double',
    ];

    public function parent()
    {
        return $this->belongsTo(TransCashDisbursementHD::class, 'TransactionNo');
    }

    public function division()
    {
        return $this->belongsTo(MsDivision::class, 'DivisionID');
    }

    public function employee()
    {
        return $this->belongsTo(MsEmployee::class, 'EmployeeID');
    }

    public function currency()
    {
        return $this->belongsTo(MsCurrency::class, 'CurrencyID');
    }
}
