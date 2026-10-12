<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ActivityLog extends Model
{
    protected $guarded = [];
    public function user() { return $this->belongsTo(User::class); }
    public static function record(string $activity, ?string $details = null): self
    {
        return static::create(['user_id' => auth()->id(), 'activity' => $activity, 'details' => $details]);
    }
}
