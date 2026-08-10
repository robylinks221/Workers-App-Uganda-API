<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountAppeal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountAppealController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'account_status' => $user->account_status ?? 'active',
            'account_status_reason' => $user->account_status_reason,
            'account_status_changed_at' => $user->account_status_changed_at,
            'latest_appeal' => AccountAppeal::query()
                ->where('user_id', $user->id)
                ->latest()
                ->first(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (($user->account_status ?? 'active') !== 'suspended') {
            return response()->json([
                'success' => false,
                'message' => 'Only suspended accounts can submit a suspension appeal.',
            ], 422);
        }

        $pending = AccountAppeal::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($pending !== null) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an appeal waiting for administrator review.',
                'appeal' => $pending,
            ], 422);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:20', 'max:2000'],
        ]);

        $appeal = AccountAppeal::query()->create([
            'user_id' => $user->id,
            'message' => trim($validated['message']),
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your appeal has been submitted. An administrator will review it.',
            'appeal' => $appeal,
        ], 201);
    }
}
