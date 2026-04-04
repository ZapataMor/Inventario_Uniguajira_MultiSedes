<?php

namespace App\Models;

use App\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Report extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $fillable = [
        'folder_id',
        'name',
        'path',
    ];

    public function folder()
    {
        return $this->belongsTo(ReportFolder::class, 'folder_id');
    }
}
