<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransCustomerReceivedDT extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_CustomerReceivedDT';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'Amount' => 'double',
        'BankAmount' => 'double',
        'DiscAmount1' => 'double',
        'DiscAmount2' => 'double',
        'DiscAmount3' => 'double',
    ];

    public function parent()
    {
        return $this->belongsTo(TransCustomerReceivedHD::class, 'TransactionNo');
    }

    public function division()
    {
        return $this->belongsTo(MsDivision::class, 'DivisionID');
    }
}
