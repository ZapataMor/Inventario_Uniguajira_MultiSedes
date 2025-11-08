<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false; // usamos solo created_at
    protected $fillable = [
        'user_id',
        'action',
        'table_name',
        'record_id',
        'details',
        'created_at'
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
