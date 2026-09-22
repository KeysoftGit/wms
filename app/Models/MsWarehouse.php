<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Ms_User
 *
 * @property string $WarehouseID
 * @property string $WarehouseName
 * @property string $Notes
 * @property bool $Active
 * @property string $CreatedBy
 * @property string $EntryTime
 * @property string $LastUpdateBy
 * @property string $LastUpdate
 *
 * @package App\Models
 */

class MsWarehouse extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Ms_Warehouse';

    protected $primaryKey = 'WarehouseID';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    /**
     * Restrict warehouses to the user's latest authorization mapping.
     */
    public function scopeAccessibleTo($query, $user)
    {
        if (!$user || $user->hasPermissionTo('admin')) {
            return $query;
        }

        $allowedWarehouseIds = [];

        $header = TransUserWarehouseHD::where('UserID', $user->UserID)
            ->orderBy('EntryTime', 'desc')
            ->first();

        if ($header) {
            $effectiveDate = \Carbon\Carbon::parse($header->EffectiveDate);

            if (\Carbon\Carbon::now()->gte($effectiveDate)) {
                $allowedWarehouseIds = TransUserWarehouseDT::where('UserID', $user->UserID)
                    ->pluck('WarehouseID')
                    ->toArray();
            }
        }

        return $query->whereIn($this->qualifyColumn('WarehouseID'), $allowedWarehouseIds);
    }

    public function parent()
    {
        return $this->belongsTo(MsWarehouse::class, 'ParentID');
    }

    public function staff()
    {
        return $this->belongsTo(MsEmployee::class, 'StaffInChargeID');
    }

    public function division()
    {
        return $this->belongsTo(MsDivision::class, 'DivisionID');
    }
}
