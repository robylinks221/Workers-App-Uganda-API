<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkerProfile;
use App\Services\AppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminWorkerVerificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'all'])],
        ]);

        $status = $validated['status'] ?? 'pending';

        $query = WorkerProfile::query()
            ->with([
                'user:id,full_name,phone,email,role,profile_photo,location,is_verified,created_at',
                'services:id,name,slug,icon',
            ])
            ->where('profile_completed', true);

        if ($status !== 'all') {
            $query->where('verification_status', $status);
        }

        $profiles = $query
            ->orderByRaw("CASE WHEN verification_submitted_at IS NULL THEN 1 ELSE 0 END")
            ->orderBy('verification_submitted_at')
            ->orderBy('id')
            ->get()
            ->map(fn (WorkerProfile $profile) => $this->summary($profile));

        return response()->json([
            'success' => true,
            'status' => $status,
            'workers' => $profiles,
        ]);
    }

    public function show(WorkerProfile $workerProfile): JsonResponse
    {
        $workerProfile->load([
            'user:id,full_name,phone,email,role,profile_photo,location,is_verified,created_at',
            'services:id,name,slug,icon,description',
            'galleryImages:id,worker_profile_id,image_path,position',
        ]);

        return response()->json([
            'success' => true,
            'worker' => $this->details($workerProfile),
        ]);
    }

    public function approve(Request $request, WorkerProfile $workerProfile): JsonResponse
    {
        $admin = $request->user();

        DB::transaction(function () use ($admin, $workerProfile) {
            $workerProfile->forceFill([
                'identity_verified' => true,
                'verification_status' => 'approved',
                'verification_rejection_reason' => null,
                'verification_reviewed_at' => now(),
                'verification_reviewed_by' => $admin->id,
            ])->save();

            $workerProfile->user()->update([
                'is_verified' => true,
            ]);
        });

        AppNotificationService::send(
            $workerProfile->user_id,
            'worker_verification_approved',
            'system',
            'Profile verified',
            'Your worker profile and identity documents have been approved. Your verified badge is now active.',
            'worker_profile',
            $workerProfile->user_id,
            ['verification_status' => 'approved']
        );

        return response()->json([
            'success' => true,
            'message' => 'Worker approved successfully.',
        ]);
    }

    public function reject(Request $request, WorkerProfile $workerProfile): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $admin = $request->user();

        DB::transaction(function () use ($admin, $workerProfile, $validated) {
            $workerProfile->forceFill([
                'identity_verified' => false,
                'verification_status' => 'rejected',
                'verification_rejection_reason' => trim($validated['reason']),
                'verification_reviewed_at' => now(),
                'verification_reviewed_by' => $admin->id,
            ])->save();

            $workerProfile->user()->update([
                'is_verified' => false,
            ]);
        });

        AppNotificationService::send(
            $workerProfile->user_id,
            'worker_verification_rejected',
            'system',
            'Verification needs attention',
            'Your worker verification was not approved. Open your profile to review the reason and correct your details.',
            'worker_profile',
            $workerProfile->user_id,
            [
                'verification_status' => 'rejected',
                'reason' => trim($validated['reason']),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Worker verification rejected.',
        ]);
    }

    private function summary(WorkerProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'user_id' => $profile->user_id,
            'full_name' => $profile->user?->full_name ?? 'Worker',
            'phone' => $profile->user?->phone,
            'district' => $profile->district,
            'profile_photo' => $profile->profile_photo ?: $profile->user?->profile_photo,
            'verification_status' => $profile->verification_status,
            'submitted_at' => optional($profile->verification_submitted_at)->toIso8601String(),
            'services' => $profile->services->pluck('name')->values(),
        ];
    }

    private function details(WorkerProfile $profile): array
    {
        return [
            ...$this->summary($profile),
            'email' => $profile->user?->email,
            'location' => $profile->user?->location,
            'age' => $profile->age,
            'religion' => $profile->religion,
            'gender' => $profile->gender,
            'work_type' => $profile->work_type,
            'availability' => $profile->availability,
            'bio' => $profile->bio,
            'experience_years' => $profile->experience_years,
            'has_national_id_front' =>
                !empty($profile->national_id_front_document),
            'has_national_id_back' =>
                !empty($profile->national_id_back_document),
            'background_checked' => (bool) $profile->background_checked,
            'police_clearance' => (bool) $profile->police_clearance,
            'medical_clearance' => (bool) $profile->medical_clearance,
            'identity_verified' => (bool) $profile->identity_verified,
            'user_is_verified' => (bool) $profile->user?->is_verified,
            'rejection_reason' => $profile->verification_rejection_reason,
            'reviewed_at' => optional($profile->verification_reviewed_at)->toIso8601String(),
            'services' => $profile->services->map(fn ($service) => [
                'id' => $service->id,
                'name' => $service->name,
            ])->values(),
            'gallery' => $profile->galleryImages->map(fn ($image) => [
                'id' => $image->id,
                'path' => $image->image_path,
                'position' => $image->position,
            ])->values(),
        ];
    }
}
