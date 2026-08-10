<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AccountControlController extends Controller
{
    public function deactivate(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (!Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => [
                    'The password is incorrect.',
                ],
            ]);
        }

        if (($user->account_status ?? 'active') === 'suspended') {
            return response()->json([
                'success' => false,
                'message' => 'A suspended account cannot be deactivated by the user. Please use the appeal option.',
            ], 422);
        }

        $user->forceFill([
            'account_status' => 'deactivated',
            'account_status_source' => 'user',
            'account_status_reason' => 'Temporarily deactivated by user.',
            'account_status_changed_at' => now(),
            'account_status_changed_by' => null,
            'deletion_requested_at' => null,
            'deletion_scheduled_for' => null,
        ])->save();

        $this->hideMarketplacePresence($user);

        $user->deviceTokens()->delete();
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Your account is now deactivated. Log in again whenever you want to reactivate it.',
            'account_status' => 'deactivated',
        ]);
    }

    public function requestDeletion(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'password' => ['required', 'string'],
            'confirmation' => ['required', 'in:DELETE'],
        ]);

        if (!Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => [
                    'The password is incorrect.',
                ],
            ]);
        }

        if (($user->account_status ?? 'active') === 'suspended') {
            return response()->json([
                'success' => false,
                'message' => 'A suspended account cannot start self-service deletion. Please contact support or use the appeal option.',
            ], 422);
        }

        $deleteAt = now()->addDays(30);

        $user->forceFill([
            'account_status' => 'pending_deletion',
            'account_status_source' => 'user',
            'account_status_reason' => 'Account deletion requested by user.',
            'account_status_changed_at' => now(),
            'account_status_changed_by' => null,
            'deletion_requested_at' => now(),
            'deletion_scheduled_for' => $deleteAt,
        ])->save();

        $this->hideMarketplacePresence($user);

        $user->deviceTokens()->delete();
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Your account is scheduled for deletion in 30 days. Log in during that time to restore it.',
            'account_status' => 'pending_deletion',
            'deletion_scheduled_for' => $deleteAt->toIso8601String(),
        ]);
    }

    private function hideMarketplacePresence($user): void
    {
        if ($user->role === 'worker' && $user->workerProfile !== null) {
            $user->workerProfile()->update([
                'active' => false,
            ]);
        }

        if ($user->role === 'homeowner') {
            $user->jobsPosted()
                ->where('status', 'open')
                ->update([
                    'visibility' => 'private',
                ]);
        }
    }
}
