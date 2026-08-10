<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeownerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class HomeownerProfileSectionController extends Controller
{
    /**
     * Update the homeowner's personal and contact information.
     */
    public function personal(Request $request): JsonResponse
    {
        $user = $request->user();

        $roleError = $this->validateHomeownerAccount($user);

        if ($roleError !== null) {
            return $roleError;
        }

        $validated = $request->validate([
            'full_name' => [
                'required',
                'string',
                'min:3',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(
                    'users',
                    'email'
                )->ignore($user->id),
            ],

            'profile_photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $newPhoto = null;
        $oldPhoto = null;

        try {
            $result = DB::transaction(function () use (
                $request,
                $validated,
                $user,
                &$newPhoto,
                &$oldPhoto
            ): array {
                $profile = HomeownerProfile::query()
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if ($profile === null) {
                    throw new \RuntimeException(
                        'Homeowner profile could not be found.'
                    );
                }

                $photoPath = $user->profile_photo;

                if ($request->hasFile('profile_photo')) {
                    $newPhoto = $request
                        ->file('profile_photo')
                        ->store(
                            'homeowner/profile-photos',
                            'public'
                        );

                    if (
                        $photoPath !== null
                        && $photoPath !== $newPhoto
                    ) {
                        $oldPhoto = $photoPath;
                    }

                    $photoPath = $newPhoto;
                }

                $user->update([
                    'full_name' =>
                        $validated['full_name'],

                    'email' =>
                        $validated['email'],

                    'profile_photo' =>
                        $photoPath,
                ]);

                return [
                    'profile' => $profile->fresh(),
                    'user' => $user->fresh(),
                ];
            });

            if ($oldPhoto !== null) {
                Storage::disk('public')->delete(
                    $oldPhoto
                );
            }

            return response()->json([
                'success' => true,
                'message' =>
                    'Personal information updated successfully.',
                'profile' => $result['profile'],
                'user' => $result['user'],
            ]);
        } catch (Throwable $exception) {
            if ($newPhoto !== null) {
                Storage::disk('public')->delete(
                    $newPhoto
                );
            }

            report($exception);

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to update personal information.',
            ], 500);
        }
    }

    /**
     * Update the homeowner's address and location information.
     */
    public function location(Request $request): JsonResponse
    {
        $user = $request->user();

        $roleError = $this->validateHomeownerAccount($user);

        if ($roleError !== null) {
            return $roleError;
        }

        $profile = HomeownerProfile::query()
            ->where('user_id', $user->id)
            ->first();

        if ($profile === null) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Homeowner profile could not be found.',
            ], 404);
        }

        $validated = $request->validate([
            'address' => [
                'required',
                'string',
                'max:255',
            ],

            'city' => [
                'nullable',
                'string',
                'max:150',
            ],

            'district' => [
                'required',
                'string',
                'max:150',
            ],

            'country' => [
                'nullable',
                'string',
                'max:100',
            ],

            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],
        ]);

        try {
            DB::transaction(function () use (
                $profile,
                $validated,
                $user
            ): void {
                $profile->update([
                    'address' =>
                        $validated['address'],

                    'city' =>
                        $validated['city'] ?? null,

                    'district' =>
                        $validated['district'],

                    'country' =>
                        $validated['country'] ?? 'Uganda',

                    'latitude' =>
                        $validated['latitude'] ?? null,

                    'longitude' =>
                        $validated['longitude'] ?? null,

                    'profile_completed' =>
                        true,
                ]);

                $user->update([
                    'location' =>
                        $validated['district'],
                ]);
            });

            return response()->json([
                'success' => true,
                'message' =>
                    'Location information updated successfully.',
                'profile' => $profile->fresh(),
                'user' => $user->fresh(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to update location information.',
            ], 500);
        }
    }

    /**
     * Update the homeowner's communication preference.
     */
    public function preferences(Request $request): JsonResponse
    {
        $user = $request->user();

        $roleError = $this->validateHomeownerAccount($user);

        if ($roleError !== null) {
            return $roleError;
        }

        $profile = HomeownerProfile::query()
            ->where('user_id', $user->id)
            ->first();

        if ($profile === null) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Homeowner profile could not be found.',
            ], 404);
        }

        $validated = $request->validate([
            'preferred_contact' => [
                'required',
                Rule::in([
                    'phone',
                    'whatsapp',
                    'email',
                ]),
            ],
        ]);

        try {
            $profile->update([
                'preferred_contact' =>
                    $validated['preferred_contact'],
            ]);

            return response()->json([
                'success' => true,
                'message' =>
                    'Contact preference updated successfully.',
                'profile' => $profile->fresh(),
                'user' => $user->fresh(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to update contact preference.',
            ], 500);
        }
    }

    /**
     * Validate that the authenticated account is a homeowner.
     */
    private function validateHomeownerAccount(
        mixed $user
    ): ?JsonResponse {
        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'homeowner') {
            return response()->json([
                'success' => false,
                'message' =>
                    'Only homeowner accounts can manage this profile.',
            ], 403);
        }

        return null;
    }
}
