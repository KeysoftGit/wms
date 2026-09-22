<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Ms_User
 *
 * @property string $AutoNumberID
 * @property string $CreatedBy
 * @property string $EntryTime
 *
 * @package App\Models
 */

class MsAutoNumber extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Ms_AutoNumber';

    protected $primaryKey = 'AutoNumberID';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    public static function generate($columnName, $tableName, $columnID, $date = null)
    {
        $masterAuto = self::firstOrFail();
        $prefix = $masterAuto->$columnName;
        $date = $date ? \Carbon\Carbon::parse($date) : now();
        $year = $date->format('Y');
        $month = $date->format('m');

        $checkLast = \Illuminate\Support\Facades\DB::table($tableName)
            ->whereYear('TransactionDate', $year)
            ->whereMonth('TransactionDate', $month)
            ->orderBy($columnID, 'desc')
            ->first();

        $digit = 1;
        if ($checkLast) {
            $parts = explode('/', $checkLast->$columnID);
            if (count($parts) === 4) {
                $digit = (int) $parts[3] + 1;
            }
        }

        do {
            $id = $prefix . '/' . $year . '/' . $month . '/' . str_pad($digit, 4, '0', STR_PAD_LEFT);
            $checkExist = \Illuminate\Support\Facades\DB::table($tableName)
                ->where($columnID, $id)
                ->exists();
            $digit++;
        } while ($checkExist);

        return $id;
    }
}
