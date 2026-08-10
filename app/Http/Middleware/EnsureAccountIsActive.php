<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $status = $user->account_status ?? 'active';

        if ($status === 'active') {
            return $next($request);
        }

        if ($status === 'deactivated') {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Reason: '.($user->account_status_reason ?: 'Please contact support.'),
                'account_status' => 'deactivated',
                'account_status_reason' => $user->account_status_reason,
            ], 403);
        }

        if ($status === 'pending_deletion') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is scheduled for deletion.',
                'account_status' => 'pending_deletion',
                'deletion_scheduled_for' =>
                    optional(
                        $user->deletion_scheduled_for
                    )->toIso8601String(),
            ], 403);
        }

        if ($status === 'deleted') {
            return response()->json([
                'success' => false,
                'message' => 'This account has been deleted.',
                'account_status' => 'deleted',
            ], 403);
        }

        // Suspended users can still enter their account, read notifications,
        // edit their own profile, log out, and submit/view an appeal.
        $path = trim($request->path(), '/');

        $allowed = (
            $path === 'api/me'
            || $path === 'api/logout'
            || str_starts_with($path, 'api/notifications')
            || str_starts_with($path, 'api/device-tokens')
            || str_starts_with($path, 'api/worker/profile')
            || $path === 'api/account/status'
            || str_starts_with($path, 'api/account/appeals')
        );

        if ($allowed) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Your account is currently suspended. You can review the reason and submit an appeal from your Account page.',
            'account_status' => 'suspended',
            'account_status_reason' => $user->account_status_reason,
        ], 403);
    }
}
