<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransDirectVendorPaymentDT extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_DirectVendorPaymentDT';

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
        return $this->belongsTo(TransDirectVendorPaymentHD::class, 'TransactionNo');
    }

    public function division()
    {
        return $this->belongsTo(MsDivision::class, 'DivisionID');
    }
}
