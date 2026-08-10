<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountAppeal;
use App\Services\AppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAccountAppealController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', 'pending');

        $query = AccountAppeal::query()
            ->with('user:id,full_name,phone,email,role,account_status,account_status_reason,account_status_changed_at')
            ->latest();

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        return response()->json([
            'success' => true,
            'appeals' => $query->get(),
        ]);
    }

    public function approve(Request $request, AccountAppeal $appeal): JsonResponse
    {
        if ($appeal->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'This appeal has already been reviewed.'], 422);
        }

        $validated = $request->validate([
            'response' => ['nullable', 'string', 'max:1500'],
        ]);

        $appeal->forceFill([
            'status' => 'approved',
            'admin_response' => $validated['response'] ?? 'Your appeal was approved and your account has been restored.',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();

        $appeal->user->forceFill([
            'account_status' => 'active',
            'account_status_reason' => null,
            'account_status_changed_at' => now(),
            'account_status_changed_by' => $request->user()->id,
        ])->save();

        AppNotificationService::send(
            $appeal->user_id,
            'appeal_approved',
            'system',
            'Appeal approved',
            $appeal->admin_response,
            'account_status'
        );

        return response()->json(['success' => true, 'message' => 'Appeal approved and account restored.']);
    }

    public function reject(Request $request, AccountAppeal $appeal): JsonResponse
    {
        if ($appeal->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'This appeal has already been reviewed.'], 422);
        }

        $validated = $request->validate([
            'response' => ['required', 'string', 'min:5', 'max:1500'],
        ]);

        $appeal->forceFill([
            'status' => 'rejected',
            'admin_response' => trim($validated['response']),
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();

        AppNotificationService::send(
            $appeal->user_id,
            'appeal_rejected',
            'system',
            'Appeal reviewed',
            $appeal->admin_response,
            'account_status'
        );

        return response()->json(['success' => true, 'message' => 'Appeal rejected. The account remains suspended.']);
    }
}
