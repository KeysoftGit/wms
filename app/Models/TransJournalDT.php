<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransJournalDT extends Model
{
    protected $connection= 'sqlsrv';
    protected $table = 'Trans_JournalDT';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'Debit' => 'double',
        'Credit' => 'double',
        'Rate' => 'double',
        'OriginalAmount' => 'double',
    ];

    public function parent()
    {
        return $this->belongsTo(TransJournalHD::class, 'TransactionNo');
    }

    public function account()
    {
        return $this->belongsTo(MsCOA::class, 'AccountNo');
    }
    public function currency()
    {
        return $this->belongsTo(MsCurrency::class, 'CurrencyID');
    }
    public function division()
    {
        return $this->belongsTo(MsCurrency::class, 'DivisionID');
    }
}
