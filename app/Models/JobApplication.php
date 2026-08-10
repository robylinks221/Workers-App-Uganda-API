<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'worker_id',
        'invited_by_homeowner',
        'message',
        'expected_salary',
        'status',
        'responded_at',
    ];

    protected $casts = [
        'invited_by_homeowner' => 'boolean',
        'expected_salary' => 'decimal:2',
        'responded_at' => 'datetime',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }
}