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

class HomeownerProfileController extends Controller
{
    /**
     * Return the authenticated homeowner's profile.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'homeowner') {
            return response()->json([
                'success' => false,
                'message' => 'Only homeowner accounts can access this profile.',
            ], 403);
        }

        $profile = HomeownerProfile::query()
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'success' => true,
            'profile_completed' => (bool) ($profile?->profile_completed ?? false),
            'profile' => $profile,
            'user' => $user,
        ]);
    }

    /**
     * Create or update the authenticated homeowner's profile.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'homeowner') {
            return response()->json([
                'success' => false,
                'message' => 'Only homeowner accounts can complete this profile.',
            ], 403);
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
                Rule::unique('users', 'email')->ignore($user->id),
            ],

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

            'preferred_contact' => [
                'required',
                Rule::in([
                    'phone',
                    'whatsapp',
                    'email',
                ]),
            ],

            'profile_photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
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

        $newFiles = [];
        $oldFilesToDelete = [];

        try {
            $profile = DB::transaction(function () use (
                $request,
                $validated,
                $user,
                &$newFiles,
                &$oldFilesToDelete
            ) {
                $profile = HomeownerProfile::query()
                    ->firstOrNew([
                        'user_id' => $user->id,
                    ]);

                $profilePhotoPath = $user->profile_photo;

                if ($request->hasFile('profile_photo')) {
                    $newProfilePhotoPath = $request
                        ->file('profile_photo')
                        ->store(
                            'homeowner/profile-photos',
                            'public'
                        );

                    $newFiles[] = $newProfilePhotoPath;

                    if (
                        $profilePhotoPath !== null
                        && $profilePhotoPath !== $newProfilePhotoPath
                    ) {
                        $oldFilesToDelete[] = $profilePhotoPath;
                    }

                    $profilePhotoPath = $newProfilePhotoPath;
                }

                $profile->fill([
                    'address' => $validated['address'],
                    'city' => $validated['city'] ?? null,
                    'district' => $validated['district'],
                    'country' => $validated['country'] ?? 'Uganda',
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                    'preferred_contact' => $validated['preferred_contact'],
                    'profile_completed' => true,
                ]);

                $profile->save();

                $user->update([
                    'full_name' => $validated['full_name'],
                    'email' => $validated['email'],
                    'location' => $validated['district'],
                    'profile_photo' => $profilePhotoPath,
                ]);

                return $profile->fresh();
            });

            foreach (array_unique($oldFilesToDelete) as $oldFile) {
                Storage::disk('public')->delete($oldFile);
            }

            return response()->json([
                'success' => true,
                'message' => 'Homeowner profile saved successfully.',
                'profile_completed' => true,
                'profile' => $profile,
                'user' => $user->fresh(),
            ]);
        } catch (Throwable $exception) {
            foreach (array_unique($newFiles) as $newFile) {
                Storage::disk('public')->delete($newFile);
            }

            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Unable to save the homeowner profile.',
            ], 500);
        }
    }
}
