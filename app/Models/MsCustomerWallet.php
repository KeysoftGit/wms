<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MsCustomerWallet extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'Ms_Customer_Wallet';
    public $timestamps = false;

    protected $fillable = [
        'Phone',
        'Amount',
    ];

    protected $primaryKey = 'id';

}
