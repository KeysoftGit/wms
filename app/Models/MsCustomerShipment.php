<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 *
 * @package App\Models
 */

class MsCustomerShipment extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Ms_Customer_Shipment';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];
}
