<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IdentityAccessRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class IdentityAccessController extends Controller
{
    /**
     * Homeowner checks whether temporary ID access exists.
     */
    public function status(
        Request $request,
        User $worker
    ): JsonResponse {
        $homeowner = $request->user();

        $roleError = $this->validateHomeowner($homeowner);

        if ($roleError !== null) {
            return $roleError;
        }

        $profile = $this->verifiedProfile($worker);

        if ($profile === null) {
            return response()->json([
                'success' => false,
                'eligible' => false,
                'message' => 'This worker does not have a verified ID available.',
            ], 404);
        }

        $access = IdentityAccessRequest::query()
            ->where('homeowner_id', $homeowner->id)
            ->where('worker_id', $worker->id)
            ->first();

        return response()->json([
            'success' => true,
            'eligible' => true,
            'access' => $access === null
                ? null
                : $this->serializeAccess($access),
        ]);
    }

    /**
     * Homeowner requests temporary access before hiring.
     *
     * No worker approval is required. The homeowner must be authenticated,
     * and the worker must already be identity-verified by an administrator.
     */
    public function requestAccess(
        Request $request,
        User $worker
    ): JsonResponse {
        $homeowner = $request->user();

        $roleError = $this->validateHomeowner($homeowner);

        if ($roleError !== null) {
            return $roleError;
        }

        $profile = $this->verifiedProfile($worker);

        if ($profile === null) {
            return response()->json([
                'success' => false,
                'message' => 'This worker does not have a complete verified ID available.',
            ], 422);
        }

        $access = IdentityAccessRequest::query()
            ->firstOrNew([
                'homeowner_id' => $homeowner->id,
                'worker_id' => $worker->id,
            ]);

        $access->forceFill([
            'requested_at' => now(),
            'expires_at' => now()->addHours(24),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Verified ID access granted for 24 hours.',
            'access' => $this->serializeAccess($access),
        ]);
    }

    /**
     * Homeowner views one protected ID side.
     */
    public function homeownerDocument(
        Request $request,
        User $worker,
        string $side
    ): BinaryFileResponse|JsonResponse {
        $homeowner = $request->user();

        $roleError = $this->validateHomeowner($homeowner);

        if ($roleError !== null) {
            return $roleError;
        }

        if (!in_array($side, ['front', 'back'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid ID side.',
            ], 404);
        }

        $access = IdentityAccessRequest::query()
            ->where('homeowner_id', $homeowner->id)
            ->where('worker_id', $worker->id)
            ->first();

        if ($access === null || !$access->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Your ID access has expired. Request access again.',
            ], 403);
        }

        $profile = $this->verifiedProfile($worker);

        if ($profile === null) {
            return response()->json([
                'success' => false,
                'message' => 'This worker no longer has a verified ID available.',
            ], 403);
        }

        $path = $side === 'front'
            ? $profile->national_id_front_document
            : $profile->national_id_back_document;

        $file = $this->resolvePrivateOrLegacyFile($path);

        if ($file === null) {
            return response()->json([
                'success' => false,
                'message' => 'ID image not found.',
            ], 404);
        }

        $access->forceFill([
            'last_viewed_at' => now(),
            'view_count' => $access->view_count + 1,
        ])->save();

        return response()->file(
            $file,
            [
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Admin-only document viewer used during worker verification.
     */
    public function adminDocument(
        WorkerProfile $profile,
        string $side
    ): BinaryFileResponse|JsonResponse {
        if (!in_array($side, ['front', 'back'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid ID side.',
            ], 404);
        }

        $path = $side === 'front'
            ? $profile->national_id_front_document
            : $profile->national_id_back_document;

        $file = $this->resolvePrivateOrLegacyFile($path);

        if ($file === null) {
            return response()->json([
                'success' => false,
                'message' => 'ID image not found.',
            ], 404);
        }

        return response()->file(
            $file,
            [
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    private function validateHomeowner(
        ?User $homeowner
    ): ?JsonResponse {
        if ($homeowner === null) {
            return response()->json([
                'success' => false,
                'message' => 'Please sign in to continue.',
            ], 401);
        }

        if ($homeowner->role !== 'homeowner') {
            return response()->json([
                'success' => false,
                'message' => 'Only homeowner accounts can request worker ID access.',
            ], 403);
        }

        return null;
    }

    private function verifiedProfile(
        User $worker
    ): ?WorkerProfile {
        if (
            $worker->role !== 'worker'
            || !$worker->is_verified
        ) {
            return null;
        }

        return WorkerProfile::query()
            ->where('user_id', $worker->id)
            ->where('active', true)
            ->where('profile_completed', true)
            ->where('verification_status', 'approved')
            ->where('identity_verified', true)
            ->whereNotNull('national_id_front_document')
            ->whereNotNull('national_id_back_document')
            ->first();
    }

    private function serializeAccess(
        IdentityAccessRequest $access
    ): array {
        return [
            'id' => $access->id,
            'requested_at' => optional(
                $access->requested_at
            )->toIso8601String(),
            'expires_at' => optional(
                $access->expires_at
            )->toIso8601String(),
            'last_viewed_at' => optional(
                $access->last_viewed_at
            )->toIso8601String(),
            'view_count' => $access->view_count,
            'active' => $access->isActive(),
        ];
    }

    /**
     * New files are private. Public fallback exists temporarily for IDs
     * uploaded before private storage was introduced.
     */
    private function resolvePrivateOrLegacyFile(
        ?string $path
    ): ?string {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->path($path);
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->path($path);
        }

        return null;
    }
}
