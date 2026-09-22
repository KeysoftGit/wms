<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Ms_User
 *
 * @property string $PartID
 * @property string $PartName
 * @property string $Notes
 * @property bool $Active
 * @property string $CreatedBy
 * @property string $EntryTime
 * @property string $LastUpdateBy
 * @property string $LastUpdate
 *
 * @package App\Models
 */

class MsPart extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Ms_Part';

    protected $primaryKey = 'PartID';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'MaximumStockBuffer' => 'double',
        'MinimumStockBuffer' => 'double',
        'VAT2' => 'double'
    ];

    public function warehouse()
    {
        return $this->belongsTo(MsWarehouse::class, 'DeferedWarehouseID');
    }

    public function category()
    {
        return $this->belongsTo(MsPartCategory::class, 'CategoryID');
    }

    public function specification()
    {
        return $this->belongsTo(MsPartSpecification::class, 'SpecificationID');
    }

    public function variant()
    {
        return $this->belongsTo(MsPartVariant::class, 'VariantID');
    }

    public function type()
    {
        return $this->belongsTo(MsInventoryType::class, 'InventoryTypeID');
    }

    public function units()
    {
        return $this->hasMany(MsPartUnit::class, 'PartID');
    }

}
