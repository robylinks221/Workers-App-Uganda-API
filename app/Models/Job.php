<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'homeowner_id',
        'accepted_worker_id',

        'title',
        'category',
        'description',

        'address',
        'district',
        'latitude',
        'longitude',

        'start_date',
        'start_time',
        'duration',
        'work_arrangement',
        'contract_duration',

        'budget_type',
        'budget_amount',

        'accommodation_provided',
        'meals_provided',
        'transport_allowance',
        'medical_support',
        'uniform_provided',
        'other_benefits',

        'status',
        'visibility',
        'is_urgent',
        'accepted_at',
        'started_at',
        'completion_requested_at',
        'completed_at',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'cancellation_note',
    ];
public function hiringRequests(): HasMany
{
    return $this->hasMany(HiringRequest::class);
}
    protected $casts = [
        'is_urgent' => 'boolean',
        'budget_amount' => 'decimal:2',
        'start_date' => 'date',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'completion_requested_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',

        'accommodation_provided' => 'boolean',
        'meals_provided' => 'boolean',
        'transport_allowance' => 'boolean',
        'medical_support' => 'boolean',
        'uniform_provided' => 'boolean',
    ];

    public function homeowner()
    {
        return $this->belongsTo(User::class, 'homeowner_id');
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'accepted_worker_id');
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    public function serviceCategories()
    {
        return $this->belongsToMany(
            ServiceCategory::class,
            'job_service_category',
            'job_id',
            'service_category_id'
        )->withTimestamps();
    }
}
