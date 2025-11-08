<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportFolder extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function reports()
    {
        return $this->hasMany(Report::class, 'folder_id');
    }
}
