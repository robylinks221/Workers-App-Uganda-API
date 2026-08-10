<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerGalleryImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_profile_id',
        'image_path',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    /**
     * Each gallery image belongs to one worker profile.
     */
    public function workerProfile()
    {
        return $this->belongsTo(WorkerProfile::class);
    }
}
