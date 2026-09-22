<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ControlPanel extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'ControlPanel';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];


    public static function getValue($key, $default = null)
    {
        if (!Schema::hasTable('ControlPanel')) {
            return $default;
        }

        $setting = self::where('SettingKey', $key)->first();

        return $setting ? $setting->SettingValue : $default;
    }

    public static function isEnabled($key)
    {
        if (!Schema::hasTable('ControlPanel')) {
            return false;
        }

        return self::where('SettingKey', $key)
            ->where('SettingValue', '1')
            ->exists();
    }
}
