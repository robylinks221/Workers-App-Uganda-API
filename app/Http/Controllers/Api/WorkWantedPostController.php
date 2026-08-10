<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use App\Models\WorkWantedPost;
use App\Models\WorkerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkWantedPostController extends Controller
{
    public function mine(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'worker') return $this->roleError('worker');

        $post = WorkWantedPost::with('services:id,name,slug,icon')
            ->where('worker_id', $request->user()->id)->latest()->first();

        return response()->json(['success' => true, 'post' => $post]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'worker') return $this->roleError('worker');
        if (!$this->isApprovedWorker($request->user()->id)) {
            return response()->json(['success' => false, 'message' => 'Your worker profile must be approved before you can post Looking for Work.'], 403);
        }
        $data = $this->validated($request);

        $existing = WorkWantedPost::where('worker_id', $request->user()->id)
            ->whereIn('status', ['active', 'paused'])->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a Looking for Work post. Edit or reactivate that post instead.',
            ], 422);
        }

        $post = DB::transaction(function () use ($request, $data) {
            $serviceIds = $data['service_ids']; unset($data['service_ids']);
            $data['worker_id'] = $request->user()->id;
            $data['status'] = 'active';
            $post = WorkWantedPost::create($data);
            $post->services()->sync($serviceIds);
            return $post;
        });

        return response()->json([
            'success' => true,
            'message' => 'Your Looking for Work post is now active.',
            'post' => $post->load('services:id,name,slug,icon'),
        ], 201);
    }

    public function update(Request $request, WorkWantedPost $post): JsonResponse
    {
        if ($request->user()->role !== 'worker' || $post->worker_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'You cannot edit this post.'], 403);
        }
        $data = $this->validated($request);
        DB::transaction(function () use ($post, $data): void {
            $serviceIds = $data['service_ids']; unset($data['service_ids']);
            $post->update($data);
            $post->services()->sync($serviceIds);
        });
        return response()->json([
            'success' => true, 'message' => 'Looking for Work post updated.',
            'post' => $post->fresh()->load('services:id,name,slug,icon'),
        ]);
    }

    public function status(Request $request, WorkWantedPost $post): JsonResponse
    {
        if ($request->user()->role !== 'worker' || $post->worker_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'You cannot change this post.'], 403);
        }
        $data = $request->validate(['status' => 'required|in:active,paused,hired,closed']);
        $post->update(['status' => $data['status']]);
        return response()->json(['success' => true, 'message' => 'Post status updated.', 'post' => $post->fresh()->load('services:id,name,slug,icon')]);
    }

    public function index(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'homeowner') return $this->roleError('homeowner');

        $query = WorkWantedPost::query()
            ->with([
                'services:id,name,slug,icon',
                'worker:id,full_name,profile_photo,location,is_verified',
                'worker.workerProfile:user_id,experience_years,rating,total_reviews,availability,district',
            ])
            ->where('status', 'active')
            ->whereHas('worker.workerProfile', function ($q): void {
                $q->where('profile_completed', true)
                    ->where('active', true)
                    ->where('verification_status', 'approved')
                    ->where('identity_verified', true);
            })
            ->whereHas('worker', fn ($q) => $q->where('is_verified', true));

        if ($request->filled('district')) $query->where('district', $request->string('district'));
        if ($request->filled('service_id')) {
            $serviceId = (int) $request->input('service_id');
            $query->whereHas('services', fn ($q) => $q->where('service_categories.id', $serviceId));
        }
        if ($request->filled('work_type')) $query->where('work_type', $request->string('work_type'));

        $posts = $query->latest()->paginate(min(max((int) $request->input('per_page', 20), 1), 50));
        return response()->json([
            'success' => true,
            'posts' => $posts->items(),
            'pagination' => [
                'current_page' => $posts->currentPage(), 'last_page' => $posts->lastPage(),
                'total' => $posts->total(), 'has_more_pages' => $posts->hasMorePages(),
            ],
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'description' => 'nullable|string|max:1500',
            'district' => 'required|string|max:100',
            'work_type' => 'required|in:full_time,part_time,either',
            'living_preference' => 'required|in:live_in,live_out,either',
            'expected_salary_min' => 'nullable|numeric|min:0',
            'expected_salary_max' => 'nullable|numeric|min:0|gte:expected_salary_min',
            'available_from' => 'nullable|date',
            'available_immediately' => 'required|boolean',
            'willing_to_relocate' => 'required|boolean',
            'service_ids' => 'required|array|min:1|max:20',
            'service_ids.*' => 'required|integer|distinct|exists:service_categories,id',
        ]);

        $activeCount = ServiceCategory::whereIn('id', $data['service_ids'])->where('active', true)->count();
        if ($activeCount !== count(array_unique($data['service_ids']))) {
            abort(response()->json(['success' => false, 'message' => 'Please select only active service categories.'], 422));
        }
        if ($data['available_immediately']) $data['available_from'] = null;
        return $data;
    }

    private function isApprovedWorker(int $userId): bool
    {
        return WorkerProfile::query()
            ->where('user_id', $userId)
            ->where('profile_completed', true)
            ->where('active', true)
            ->where('verification_status', 'approved')
            ->where('identity_verified', true)
            ->whereHas('user', fn ($q) => $q->where('is_verified', true))
            ->exists();
    }

    private function roleError(string $role): JsonResponse
    {
        return response()->json(['success' => false, 'message' => "Only {$role}s can use this feature."], 403);
    }
}
