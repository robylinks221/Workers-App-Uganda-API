<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountAppeal;
use App\Models\Job;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'admin' => [
                'id' => $request->user()->id,
                'full_name' => $request->user()->full_name,
                'phone' => $request->user()->phone,
            ],
            'stats' => [
                'pending_verifications' => WorkerProfile::query()
                    ->where('profile_completed', true)
                    ->where('verification_status', 'pending')
                    ->count(),

                'pending_appeals' => AccountAppeal::query()
                    ->where('status', 'pending')
                    ->count(),

                'suspended_users' => User::query()
                    ->where('account_status', 'suspended')
                    ->count(),

                'approved_workers' => WorkerProfile::query()
                    ->where('verification_status', 'approved')
                    ->count(),

                'rejected_workers' => WorkerProfile::query()
                    ->where('verification_status', 'rejected')
                    ->count(),

                'workers' => User::query()
                    ->where('role', 'worker')
                    ->count(),

                'homeowners' => User::query()
                    ->where('role', 'homeowner')
                    ->count(),

                'open_jobs' => Job::query()
                    ->where('status', 'open')
                    ->count(),

                'active_jobs' => Job::query()
                    ->whereIn('status', [
                        'accepted',
                        'in_progress',
                        'awaiting_confirmation',
                    ])
                    ->count(),

                'completed_jobs' => Job::query()
                    ->where('status', 'completed')
                    ->count(),
            ],
        ]);
    }
}
