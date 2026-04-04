<?php

namespace App\Models;

use App\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReportFolder extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $fillable = ['name'];

    public function reports()
    {
        return $this->hasMany(Report::class, 'folder_id');
    }
}
