<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
            'platform' => ['nullable', 'string', 'in:android,ios,web'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_id' => ['nullable', 'string', 'max:255'],
        ]);

        $deviceToken = DB::transaction(function () use ($user, $validated) {
            // An FCM token belongs to only one logged-in account at a time.
            DeviceToken::query()
                ->where('token', $validated['token'])
                ->where('user_id', '!=', $user->id)
                ->delete();

            $values = [
                'user_id' => $user->id,
                'platform' => $validated['platform'] ?? 'android',
                'device_name' => $validated['device_name'] ?? null,
                'device_id' => $validated['device_id'] ?? null,
                'last_used_at' => now(),
            ];

            if (!empty($validated['device_id'])) {
                return DeviceToken::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'device_id' => $validated['device_id'],
                    ],
                    $values + ['token' => $validated['token']]
                );
            }

            return DeviceToken::query()->updateOrCreate(
                ['token' => $validated['token']],
                $values
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Device token registered successfully.',
            'device_token' => $deviceToken,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'token' => ['required_without:device_id', 'nullable', 'string', 'max:4096'],
            'device_id' => ['required_without:token', 'nullable', 'string', 'max:255'],
        ]);

        $query = DeviceToken::query()->where('user_id', $user->id);

        if (!empty($validated['device_id'])) {
            $query->where('device_id', $validated['device_id']);
        } else {
            $query->where('token', $validated['token']);
        }

        $deleted = $query->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device token removed successfully.',
            'deleted' => $deleted,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'device_tokens' => DeviceToken::query()
                ->where('user_id', $request->user()->id)
                ->latest('last_used_at')
                ->get(),
        ]);
    }
}
