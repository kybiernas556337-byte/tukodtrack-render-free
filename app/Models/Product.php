<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];
    protected $casts = [
        'is_active' => 'boolean', 'is_sample' => 'boolean',
        'selling_price' => 'float', 'quantity_on_hand' => 'float', 'reorder_level' => 'float',
    ];

    public function category() { return $this->belongsTo(Category::class); }
    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeLow($q) { return $q->whereColumn('quantity_on_hand', '<=', 'reorder_level'); }

    public function status(): string
    {
        if (!$this->is_active) return 'Archived';
        if ($this->quantity_on_hand <= 0) return 'Out of stock';
        if ($this->quantity_on_hand <= $this->reorder_level) return 'Low Stock';
        return 'Available';
    }

    public function badge(): string
    {
        return match ($this->status()) { 'Available' => 'ok', 'Archived' => 'muted', default => 'warn' };
    }

    public static function picker()
    {
        return static::active()->orderBy('name')->get()->map(fn ($p) => [
            'id' => $p->id, 'code' => $p->code, 'name' => $p->name, 'unit' => $p->base_unit,
            'price' => $p->selling_price, 'qty' => $p->quantity_on_hand,
        ])->values();
    }
}
