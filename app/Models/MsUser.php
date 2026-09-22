<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

/**
 * Class Ms_User
 *
 * @property int $id
 * @property string $UserID
 * @property string $UserName
 * @property string $WebPassword
 * @property string $EmailUserName
 * @property string $EmailPassword
 * @property string $HostID
 * @property string $Language
 * @property string $HideQtyFormula
 * @property string $HideQtyNeeded
 * @property string $EditSOusedInTrans
 * @property string $EmployeeID
 * @property int $Active
 * @property string $CreatedBy
 * @property string $EntryTime
 * @property Carbon|null $LastUpdate
 * @property string $LastUpdateBy
 *
 * @package App\Models
 */

class MsUser extends Authenticatable
{
    protected $connection= 'sqlsrv';
    protected $table = 'Ms_User';
    use HasApiTokens, HasFactory, Notifiable, HasPermissions, HasRoles;

    protected $primaryKey = 'UserID';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->WebPassword;
    }

    public function employee()
    {
        return $this->belongsTo(MsEmployee::class, 'EmployeeID');
    }
}
