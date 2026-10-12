<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Delivery extends Model
{
    protected $guarded = [];
    protected $casts = ['received_at' => 'datetime'];
    public function items() { return $this->hasMany(DeliveryItem::class); }
    public function receiver() { return $this->belongsTo(User::class, 'received_by'); }
}
