<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedWorker extends Model
{
    use HasFactory;

    protected $fillable = [
        'homeowner_id',
        'worker_id',
    ];

    public function homeowner()
    {
        return $this->belongsTo(User::class, 'homeowner_id');
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }
}
