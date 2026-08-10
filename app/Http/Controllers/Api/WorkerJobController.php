<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\HiringRequest;
use App\Models\WorkerProfile;
use App\Services\AppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkerJobController extends Controller
{
    /**
     * Show one open job to the authenticated worker.
     */
    public function show(Request $request, Job $job): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'worker') {
            return response()->json([
                'success' => false,
                'message' => 'Only worker accounts can view worker job details.',
            ], 403);
        }

        $canViewPrivate = $job->visibility !== 'private' || HiringRequest::query()
            ->where('job_id', $job->id)
            ->where('worker_id', $user->id)
            ->exists();

        if (
            $job->status !== 'open'
            || $job->accepted_worker_id !== null
            || !$canViewPrivate
        ) {
            return response()->json([
                'success' => false,
                'message' => 'This job is no longer available.',
            ], 404);
        }

        $job->load([
            'homeowner:id,full_name,profile_photo,location,is_verified',
        ]);

        $application = JobApplication::query()
            ->where('job_id', $job->id)
            ->where('worker_id', $user->id)
            ->first();

        return response()->json([
            'success' => true,
            'job' => [
                'id' => $job->id,
                'title' => $job->title,
                'category' => $job->category,
                'description' => $job->description,

                'address' => $job->address,
                'district' => $job->district,
                'latitude' => $job->latitude,
                'longitude' => $job->longitude,

                'start_date' => $job->start_date,
                'start_time' => $job->start_time,
                'duration' => $job->duration,

                'budget_type' => $job->budget_type,
                'budget_amount' => $job->budget_amount,

                'status' => $job->status,
                'is_urgent' => $job->is_urgent,
                'posted_at' => $job->created_at,

                'homeowner' => $job->homeowner === null
                    ? null
                    : [
                        'id' => $job->homeowner->id,
                        'full_name' => $job->homeowner->full_name,
                        'profile_photo' => $job->homeowner->profile_photo,
                        'location' => $job->homeowner->location,
                        'is_verified' => $job->homeowner->is_verified,
                    ],
            ],
            'has_applied' => $application !== null,
            'application' => $application,
        ]);
    }

    /**
     * Apply for an open job.
     */
    public function apply(Request $request, Job $job): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'worker') {
            return response()->json([
                'success' => false,
                'message' => 'Only worker accounts can apply for jobs.',
            ], 403);
        }

        $profile = WorkerProfile::query()
            ->where('user_id', $user->id)
            ->first();

        if ($profile === null || !$profile->profile_completed) {
            return response()->json([
                'success' => false,
                'message' => 'Complete your worker profile before applying.',
            ], 422);
        }

        if ($profile->verification_status !== 'approved' || !$profile->identity_verified || !$user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Your worker profile must be approved before you can apply for jobs.',
            ], 403);
        }

        if (
            $job->status !== 'open'
            || $job->accepted_worker_id !== null
        ) {
            return response()->json([
                'success' => false,
                'message' => 'This job is no longer accepting applications.',
            ], 422);
        }

        $existingApplication = JobApplication::query()
            ->where('job_id', $job->id)
            ->where('worker_id', $user->id)
            ->first();

        if (
            $existingApplication !== null
            && in_array($existingApplication->status, ['pending', 'accepted'], true)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active application for this job.',
            ], 422);
        }

        $validated = $request->validate([
            'message' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'expected_salary' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ]);

        if ($existingApplication !== null) {
            $existingApplication->update([
                'message' => $validated['message'] ?? null,
                'expected_salary' => $validated['expected_salary'] ?? null,
                'status' => 'pending',
                'responded_at' => null,
            ]);

            $application = $existingApplication->fresh();
        } else {
            $application = JobApplication::query()->create([
                'job_id' => $job->id,
                'worker_id' => $user->id,
                'message' => $validated['message'] ?? null,
                'expected_salary' => $validated['expected_salary'] ?? null,
                'status' => 'pending',
            ]);
        }

        AppNotificationService::send(
            $job->homeowner_id, 'job_application', 'applications',
            'New job application', $user->full_name . ' applied for ' . $job->title . '.',
            'job_applications', $job->id, ['application_id' => $application->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully.',
            'application' => $application->load('job'),
        ], 201);
    }

    /**
     * List applications submitted by the authenticated worker.
     */
    public function applications(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'worker') {
            return response()->json([
                'success' => false,
                'message' => 'Only worker accounts can view applications.',
            ], 403);
        }

        $applications = JobApplication::query()
            ->with([
                'job.homeowner:id,full_name,profile_photo,location,is_verified',
            ])
            ->where('worker_id', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'total_applications' => $applications->count(),
            'applications' => $applications,
        ]);
    }

    /**
     * Withdraw a pending application.
     */
    public function withdraw(
        Request $request,
        JobApplication $application
    ): JsonResponse {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'worker') {
            return response()->json([
                'success' => false,
                'message' => 'Only worker accounts can withdraw applications.',
            ], 403);
        }

        if ($application->worker_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot withdraw this application.',
            ], 403);
        }

        if ($application->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending applications can be withdrawn.',
            ], 422);
        }

        $application->update([
            'status' => 'withdrawn',
        ]);

        $application->loadMissing('job:id,title,homeowner_id');
        if ($application->job !== null) {
            AppNotificationService::send(
                $application->job->homeowner_id,
                'application_withdrawn',
                'applications',
                'Application withdrawn',
                $user->full_name . ' withdrew their application for ' . $application->job->title . '.',
                'job_applications',
                $application->job_id,
                ['application_id' => $application->id]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Application withdrawn successfully.',
            'application' => $application->fresh(),
        ]);
    }
}
