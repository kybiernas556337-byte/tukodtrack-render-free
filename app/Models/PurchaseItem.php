<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseItem extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['ordered_qty' => 'float', 'received_qty' => 'float', 'unit_cost' => 'float'];
    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
