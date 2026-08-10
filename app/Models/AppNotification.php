<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AppNotification extends Model {
 protected $fillable=['user_id','type','category','title','body','action_type','action_id','data','read_at'];
 protected $casts=['data'=>'array','read_at'=>'datetime'];
 public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
