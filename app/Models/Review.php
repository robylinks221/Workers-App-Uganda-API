<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [

        'job_id',
        'worker_id',
        'homeowner_id',
        'rating',
        'comment',

    ];

    public function worker()
    {
        return $this->belongsTo(User::class,'worker_id');
    }

    public function homeowner()
    {
        return $this->belongsTo(User::class,'homeowner_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
