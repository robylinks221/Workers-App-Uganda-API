<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\WorkerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class WorkerInvitationController extends Controller
{
    public function accept(Request $request, JobApplication $application): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if ($user->role !== 'worker') {
            return response()->json(['success' => false, 'message' => 'Only worker accounts can accept invitations.'], 403);
        }

        if ($application->worker_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'You cannot respond to this invitation.'], 403);
        }

        if (!$application->invited_by_homeowner) {
            return response()->json(['success' => false, 'message' => 'This application is not a homeowner invitation.'], 422);
        }

        if ($application->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Only pending invitations can be accepted.'], 422);
        }

        try {
            $result = DB::transaction(function () use ($application, $user) {
                $application = JobApplication::query()
                    ->whereKey($application->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $job = Job::query()
                    ->whereKey($application->job_id)
                    ->lockForUpdate()
                    ->first();

                if ($job === null || $job->status !== 'open' || $job->accepted_worker_id !== null) {
                    return ['success' => false, 'message' => 'This job is no longer available.'];
                }

                $workerProfile = WorkerProfile::query()
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if ($workerProfile === null || !$workerProfile->profile_completed) {
                    return ['success' => false, 'message' => 'Complete your worker profile first.'];
                }

                if ($workerProfile->availability !== 'available') {
                    return ['success' => false, 'message' => 'You are currently unavailable.'];
                }

                $application->update([
                    'status' => 'accepted',
                    'responded_at' => now(),
                ]);

                JobApplication::query()
                    ->where('job_id', $job->id)
                    ->where('id', '!=', $application->id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'declined',
                        'responded_at' => now(),
                    ]);

                $job->update([
                    'accepted_worker_id' => $user->id,
                    'status' => 'accepted',
                    'accepted_at' => now(),
                ]);


                return [
                    'success' => true,
                    'application' => $application->fresh(['job.homeowner']),
                    'job' => $job->fresh(),
                ];
            });

            if ($result['success'] !== true) {
                return response()->json(['success' => false, 'message' => $result['message']], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Invitation accepted successfully.',
                'application' => $result['application'],
                'job' => $result['job'],
            ]);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['success' => false, 'message' => 'Unable to accept the invitation.'], 500);
        }
    }

    public function decline(Request $request, JobApplication $application): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if ($user->role !== 'worker') {
            return response()->json(['success' => false, 'message' => 'Only worker accounts can decline invitations.'], 403);
        }

        if ($application->worker_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'You cannot respond to this invitation.'], 403);
        }

        if (!$application->invited_by_homeowner) {
            return response()->json(['success' => false, 'message' => 'This application is not a homeowner invitation.'], 422);
        }

        if ($application->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Only pending invitations can be declined.'], 422);
        }

        $application->update([
            'status' => 'declined',
            'responded_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invitation declined.',
            'application' => $application->fresh(['job.homeowner']),
        ]);
    }
}
