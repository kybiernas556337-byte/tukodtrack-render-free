<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StockMovement extends Model
{
    protected $guarded = [];
    protected $casts = ['quantity_delta' => 'float'];
    public function product() { return $this->belongsTo(Product::class); }
    public function recorder() { return $this->belongsTo(User::class, 'recorded_by'); }
}
