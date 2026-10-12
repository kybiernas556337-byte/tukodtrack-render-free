<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Purchase extends Model
{
    protected $guarded = [];
    protected $casts = ['received_at' => 'datetime'];
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function items() { return $this->hasMany(PurchaseItem::class); }
    public function deliveries() { return $this->hasMany(Delivery::class); }
    public function statusLabel(): string { return ['pending' => 'Awaiting delivery', 'partial' => 'Partially received', 'received' => 'Received'][$this->status] ?? ucfirst($this->status); }
}
