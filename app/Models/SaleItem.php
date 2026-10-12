<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SaleItem extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['quantity' => 'float', 'unit_price' => 'float', 'line_total' => 'float'];
    public function product() { return $this->belongsTo(Product::class); }
}
