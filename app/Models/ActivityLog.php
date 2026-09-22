<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'Activity_Log';
    protected $primaryKey = null; // Karena tabel Anda tidak punya primary key
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'UserID',
        'ReffID',
        'ReffDate',
        'FrmName',
        'Action',
        'EntryTime',
        'RoutePath',
        'Payload',
        'Method',
        'ResponseStatus'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'ReffDate' => 'date',
        'EntryTime' => 'datetime',
        'ResponseStatus' => 'integer',
    ];

    protected $dates = [
        'EntryTime',
        'ReffDate'
    ];

    /**
     * Default attribute values
     */
    protected $attributes = [
        'FrmName' => '', // Default empty string untuk NOT NULL
        'UserID' => 'GUEST', // Default untuk user tidak login
        'ReffID' => 'N/A', // Default reference ID
        'Action' => 'UNKNOWN',
    ];

    /**
     * Scope untuk filter berdasarkan tanggal
     */
    public function scopeToday($query)
    {
        return $query->whereDate('EntryTime', today());
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('EntryTime', [$startDate, $endDate]);
    }

    /**
     * Scope untuk filter berdasarkan user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('UserID', $userId);
    }

    /**
     * Scope untuk filter berdasarkan module/form
     */
    public function scopeByModule($query, $module)
    {
        return $query->where('FrmName', $module);
    }

    /**
     * Relationship dengan User (jika ada model User)
     */
    public function user()
    {
        return $this->belongsTo(MsUser::class, 'UserID', 'UserID');
    }
}
