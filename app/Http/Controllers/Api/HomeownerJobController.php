<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeownerJobController extends Controller
{
    public function index(Request $request): JsonResponse
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
                'message' => 'Only homeowner accounts can view homeowner jobs.',
            ], 403);
        }

        $jobs = Job::query()
            ->with([
                'serviceCategories:id,name,slug,icon',
                'worker:id,full_name,profile_photo,location,is_verified',
            ])
            ->where('homeowner_id', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'total_jobs' => $jobs->count(),
            'jobs' => $jobs,
        ]);
    }

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
                'message' => 'Only homeowner accounts can post jobs.',
            ], 403);
        }

        $validated = $this->validateJob($request);

        $categories = ServiceCategory::query()
            ->whereIn('id', $validated['service_category_ids'])
            ->where('active', true)
            ->orderBy('name')
            ->get();

        if ($categories->count() !== count($validated['service_category_ids'])) {
            return response()->json([
                'success' => false,
                'message' => 'One or more selected services are unavailable.',
            ], 422);
        }

        $job = DB::transaction(function () use ($validated, $categories, $user) {
            $job = Job::query()->create(
                $this->jobValues($validated, $categories->first()->name) + [
                    'homeowner_id' => $user->id,
                    'accepted_worker_id' => null,
                    'status' => 'open',
                ]
            );

            $job->serviceCategories()->sync(
                $categories->pluck('id')->all()
            );

            return $job;
        });

        return response()->json([
            'success' => true,
            'message' => 'Job posted successfully.',
            'job' => $job->load([
                'serviceCategories:id,name,slug,icon',
            ]),
        ], 201);
    }

    public function show(
        Request $request,
        Job $job
    ): JsonResponse {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($job->homeowner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'job' => $job->load([
                'serviceCategories:id,name,slug,icon',
                'worker:id,full_name,profile_photo,location,is_verified',
            ]),
        ]);
    }

    public function update(
        Request $request,
        Job $job
    ): JsonResponse {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($job->homeowner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot edit this job.',
            ], 403);
        }

        if (!in_array($job->status, ['open', 'cancelled'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only open or suspended jobs can be edited.',
            ], 422);
        }

        $validated = $this->validateJob($request);

        $categories = ServiceCategory::query()
            ->whereIn('id', $validated['service_category_ids'])
            ->where('active', true)
            ->orderBy('name')
            ->get();

        if ($categories->count() !== count($validated['service_category_ids'])) {
            return response()->json([
                'success' => false,
                'message' => 'One or more selected services are unavailable.',
            ], 422);
        }

        DB::transaction(function () use ($job, $validated, $categories) {
            $job->update(
                $this->jobValues(
                    $validated,
                    $categories->first()->name
                )
            );

            $job->serviceCategories()->sync(
                $categories->pluck('id')->all()
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Job updated successfully.',
            'job' => $job->fresh()->load([
                'serviceCategories:id,name,slug,icon',
                'worker:id,full_name,profile_photo,location,is_verified',
            ]),
        ]);
    }

    public function destroy(
        Request $request,
        Job $job
    ): JsonResponse {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($job->homeowner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if (in_array($job->status, [
            'accepted',
            'in_progress',
            'awaiting_confirmation',
        ], true)) {
            return response()->json([
                'success' => false,
                'message' => 'An active job cannot be deleted.',
            ], 422);
        }

        $job->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job deleted successfully.',
        ]);
    }

    private function validateJob(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'service_category_ids' => ['required', 'array', 'min:1', 'max:8'],
            'service_category_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:service_categories,id',
            ],
            'description' => ['required', 'string', 'max:5000'],
            'address' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'start_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'work_arrangement' => [
                'required',
                'in:full_time,part_time,one_time,temporary,live_in,weekend',
            ],
            'contract_duration' => ['required', 'string', 'max:100'],
            'budget_type' => ['required', 'in:fixed,daily,monthly'],
            'budget_amount' => ['required', 'numeric', 'min:1000'],
            'accommodation_provided' => ['nullable', 'boolean'],
            'meals_provided' => ['nullable', 'boolean'],
            'transport_allowance' => ['nullable', 'boolean'],
            'medical_support' => ['nullable', 'boolean'],
            'uniform_provided' => ['nullable', 'boolean'],
            'other_benefits' => ['nullable', 'string', 'max:3000'],
            'is_urgent' => ['nullable', 'boolean'],
        ]);
    }

    private function jobValues(
        array $validated,
        string $primaryCategory
    ): array {
        return [
            'title' => $validated['title'],
            'category' => $primaryCategory,
            'description' => $validated['description'],
            'address' => $validated['address'],
            'district' => $validated['district'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'start_date' => $validated['start_date'],
            'start_time' => $validated['start_time'] ?? null,
            'duration' => $validated['work_arrangement'],
            'work_arrangement' => $validated['work_arrangement'],
            'contract_duration' => $validated['contract_duration'],
            'budget_type' => $validated['budget_type'],
            'budget_amount' => $validated['budget_amount'],
            'accommodation_provided' =>
                $validated['accommodation_provided'] ?? false,
            'meals_provided' =>
                $validated['meals_provided'] ?? false,
            'transport_allowance' =>
                $validated['transport_allowance'] ?? false,
            'medical_support' =>
                $validated['medical_support'] ?? false,
            'uniform_provided' =>
                $validated['uniform_provided'] ?? false,
            'other_benefits' =>
                $validated['other_benefits'] ?? null,
            'is_urgent' => $validated['is_urgent'] ?? false,
        ];
    }
}
