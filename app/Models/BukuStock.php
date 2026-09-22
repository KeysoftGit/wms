<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 *
 * @package App\Models
 */

class BukuStock extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Buku_Stock';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'Qty' => 'double',
        'Qty2' => 'double',
    ];

    protected static function booted()
    {
        static::creating(function (BukuStock $bukuStock) {
            $now = now();

            $bukuStock->created_at = $bukuStock->created_at ?: $now;
            $bukuStock->updated_at = $bukuStock->updated_at ?: $now;
        });
    }

    public static function insert(array $values): bool
    {
        if (empty($values)) {
            return true;
        }

        return (new static)->newQuery()->insert(static::withTimestampsForInsert($values));
    }

    private static function withTimestampsForInsert(array $values): array
    {
        $firstRow = reset($values);
        $now = now();

        if (!is_array($firstRow)) {
            return static::withTimestampsForInsertRow($values, $now);
        }

        return array_map(function (array $row) use ($now) {
            return static::withTimestampsForInsertRow($row, $now);
        }, $values);
    }

    private static function withTimestampsForInsertRow(array $row, $now): array
    {
        $row['created_at'] = $row['created_at'] ?? $now;
        $row['updated_at'] = $row['updated_at'] ?? $now;

        return $row;
    }

    public function part()
    {
        return $this->belongsTo(MsPart::class, 'PartID', 'PartID');
    }

    public function warehouse()
    {
        return $this->belongsTo(MsWarehouse::class, 'WarehouseID', 'WarehouseID');
    }
}
