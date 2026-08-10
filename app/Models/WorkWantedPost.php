<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkWantedPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_id', 'title', 'description', 'district', 'work_type',
        'living_preference', 'expected_salary_min', 'expected_salary_max',
        'available_from', 'available_immediately', 'willing_to_relocate', 'status',
    ];

    protected $casts = [
        'expected_salary_min' => 'decimal:2',
        'expected_salary_max' => 'decimal:2',
        'available_from' => 'date',
        'available_immediately' => 'boolean',
        'willing_to_relocate' => 'boolean',
    ];

    public function worker() { return $this->belongsTo(User::class, 'worker_id'); }

    public function services()
    {
        return $this->belongsToMany(
            ServiceCategory::class,
            'work_wanted_services',
            'work_wanted_post_id',
            'service_category_id'
        )->withTimestamps();
    }
}
