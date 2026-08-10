<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    public function workerServices()
    {
        return $this->hasMany(WorkerService::class);
    }
}
