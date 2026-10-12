<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Sale extends Model
{
    protected $guarded = [];
    protected $casts = ['sale_date' => 'datetime', 'voided_at' => 'datetime', 'total_amount' => 'float', 'amount_paid' => 'float', 'change_amount' => 'float'];
    public function cashier() { return $this->belongsTo(User::class, 'cashier_id'); }
    public function items() { return $this->hasMany(SaleItem::class); }
}
