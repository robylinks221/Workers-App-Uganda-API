<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerService extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_profile_id',
        'service_category_id',
    ];

    public function workerProfile()
    {
        return $this->belongsTo(
            WorkerProfile::class,
            'worker_profile_id'
        );
    }

    public function category()
    {
        return $this->belongsTo(
            ServiceCategory::class,
            'service_category_id'
        );
    }
}
