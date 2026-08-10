<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class NotificationController extends Controller {
 public function index(Request $request): JsonResponse { $q=AppNotification::query()->where('user_id',$request->user()->id)->latest(); if($request->boolean('unread_only')) $q->whereNull('read_at'); $items=$q->limit(100)->get(); return response()->json(['success'=>true,'unread_count'=>AppNotification::where('user_id',$request->user()->id)->whereNull('read_at')->count(),'notifications'=>$items]); }
 public function unreadCount(Request $request): JsonResponse { return response()->json(['success'=>true,'unread_count'=>AppNotification::where('user_id',$request->user()->id)->whereNull('read_at')->count()]); }
 public function read(Request $request,AppNotification $notification): JsonResponse { abort_unless($notification->user_id===$request->user()->id,403); if($notification->read_at===null){$notification->update(['read_at'=>now()]);} return response()->json(['success'=>true,'notification'=>$notification->fresh()]); }
 public function readAll(Request $request): JsonResponse { AppNotification::where('user_id',$request->user()->id)->whereNull('read_at')->update(['read_at'=>now()]); return response()->json(['success'=>true]); }
 public function destroy(Request $request,AppNotification $notification): JsonResponse { abort_unless($notification->user_id===$request->user()->id,403); $notification->delete(); return response()->json(['success'=>true]); }
}
