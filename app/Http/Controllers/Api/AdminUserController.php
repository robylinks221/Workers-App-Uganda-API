<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['nullable','in:worker,homeowner,admin'],
            'status' => ['nullable','in:active,suspended,deactivated'],
            'verified' => ['nullable','in:0,1'],
            'search' => ['nullable','string','max:100'],
        ]);
        $q = User::query()->with(['workerProfile:id,user_id,district,verification_status,profile_completed,rating,jobs_completed','homeownerProfile:id,user_id,district,profile_completed']);
        if (!empty($validated['role'])) $q->where('role',$validated['role']);
        if (!empty($validated['status'])) $q->where('account_status',$validated['status']);
        if (array_key_exists('verified',$validated)) $q->where('is_verified',(bool)$validated['verified']);
        if (!empty($validated['search'])) {
            $s=$validated['search'];
            $q->where(fn($x)=>$x->where('full_name','like',"%{$s}%")->orWhere('phone','like',"%{$s}%")->orWhere('email','like',"%{$s}%"));
        }
        return response()->json(['success'=>true,'users'=>$q->latest()->limit(200)->get()]);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $user->load(['workerProfile.services:id,name','workerProfile.galleryImages','homeownerProfile']);
        return response()->json(['success'=>true,'user'=>$user,'activity'=>[
            'jobs_posted'=>$user->jobsPosted()->count(),
            'jobs_assigned'=>$user->acceptedJobs()->count(),
            'applications'=>$user->applications()->count(),
        ]]);
    }

    public function suspend(Request $request, User $user): JsonResponse
    {
        return $this->changeStatus($request,$user,'suspended');
    }

    public function deactivate(Request $request, User $user): JsonResponse
    {
        return $this->changeStatus($request,$user,'deactivated');
    }

    public function activate(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) return response()->json(['success'=>false,'message'=>'Your administrator account is already active.'],422);
        $old=$user->account_status ?? 'active';
        $user->forceFill(['account_status'=>'active','account_status_reason'=>null,'account_status_changed_at'=>now(),'account_status_changed_by'=>$request->user()->id])->save();
        AppNotificationService::send($user->id,'account_reactivated','system','Account restored','Your account has been restored by an administrator.');
        return response()->json(['success'=>true,'message'=>'Account restored successfully.','user'=>$user->fresh()]);
    }

    private function changeStatus(Request $request, User $user, string $status): JsonResponse
    {
        if ($user->id === $request->user()->id) return response()->json(['success'=>false,'message'=>'You cannot suspend or deactivate your own administrator account.'],422);
        if ($user->role === 'admin') return response()->json(['success'=>false,'message'=>'Administrator accounts cannot be changed from this screen.'],422);
        $validated=$request->validate(['reason'=>['required','string','min:5','max:1000']]);
        $user->forceFill(['account_status'=>$status,'account_status_reason'=>$validated['reason'],'account_status_changed_at'=>now(),'account_status_changed_by'=>$request->user()->id])->save();
        // Suspended users remain logged in so they can see the reason and appeal.
        // Deactivated users lose all active API sessions.
        if ($status === 'deactivated') {
            $user->tokens()->delete();
        }

        AppNotificationService::send(
            $user->id,
            'account_'.$status,
            'system',
            $status === 'suspended' ? 'Account suspended' : 'Account deactivated',
            'Reason: '.$validated['reason'],
            $status === 'suspended' ? 'account_status' : null,
            null,
            ['account_status' => $status]
        );
        return response()->json(['success'=>true,'message'=>$status==='suspended'?'Account suspended successfully.':'Account deactivated successfully.','user'=>$user->fresh()]);
    }
}
