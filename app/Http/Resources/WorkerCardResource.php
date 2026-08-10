<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerCardResource extends JsonResource
{
    /**
     * Transform the worker into marketplace-safe data.
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;

        return [
            'id' => $user?->id,

            'full_name' => $user?->full_name,

            'profile_photo' =>
                $user?->profile_photo
                ?? $this->profile_photo,

            'district' => $this->district,

            'location' => $user?->location,

            'age' => $this->age,

            'gender' => $this->gender,

            'religion' => $this->religion,

            'work_type' => $this->work_type,

            'availability' => $this->availability,

            'bio' => $this->bio,

            'experience_years' =>
                $this->experience_years,

            'hourly_rate' => $this->hourly_rate,

            'monthly_rate' => $this->monthly_rate,

            'rating' => $this->rating,

            'total_reviews' => $this->total_reviews,

            'jobs_completed' => $this->jobs_completed,

            'identity_verified' =>
                $this->identity_verified,

            'background_checked' =>
                $this->background_checked,

            'police_clearance' =>
                $this->police_clearance,

            'medical_clearance' =>
                $this->medical_clearance,

            'featured' => $this->featured,

            'is_verified' =>
                $user?->is_verified ?? false,

            'services' => $this->services
                ->map(function ($service): array {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'slug' => $service->slug,
                        'icon' => $service->icon,
                    ];
                })
                ->values(),

            'is_saved' => (bool) (
                $this->is_saved ?? false
            ),

            'created_at' => $user?->created_at,
        ];
    }
}
