<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Ms_User
 *
 * @property string $EmployeeID
 * @property bool $Active
 * @property string $CreatedBy
 * @property string $EntryTime
 * @property string $LastUpdateBy
 * @property string $LastUpdate
 *
 * @package App\Models
 */

class MsEmployee extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Ms_Employee';

    protected $primaryKey = 'EmployeeID';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $appends = ['EmployeeName'];

    public function getEmployeeNameAttribute()
    {
        return trim(($this->FirstName ?? '') . ' ' . ($this->LastName ?? ''));
    }

    public function division()
    {
        return $this->belongsTo(MsDivision::class, 'DivisionID');
    }

    public function country()
    {
        return $this->belongsTo(MsCountry::class, 'CountryID');
    }

    public function reference()
    {
        return $this->belongsTo(MsEmployee::class, 'ReferenceID');
    }

    public function supervisor()
    {
        return $this->belongsTo(MsEmployee::class, 'SupervisorID');
    }
}
