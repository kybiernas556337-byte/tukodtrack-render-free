<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StockAdjustment extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['requested_at' => 'datetime', 'approved_at' => 'datetime', 'expected_qty' => 'float', 'counted_qty' => 'float', 'quantity_delta' => 'float'];
    public function product() { return $this->belongsTo(Product::class); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}
