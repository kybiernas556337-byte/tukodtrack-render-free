<?php
namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;

class StockService
{
    /** Changes the product balance and records the movement. Call inside DB::transaction(). */
    public static function post(Product $product, float $delta, string $type, array $extra = []): StockMovement
    {
        $p = Product::lockForUpdate()->findOrFail($product->id);
        $new = round($p->quantity_on_hand + $delta, 3);
        if ($new < 0) {
            throw new \RuntimeException("Not enough stock for {$p->name} (available " . qty($p->quantity_on_hand) . " {$p->base_unit}).");
        }
        $p->update(['quantity_on_hand' => $new]);

        return StockMovement::create(array_merge([
            'product_id' => $p->id,
            'recorded_by' => auth()->id(),
            'movement_type' => $type,
            'quantity_delta' => $delta,
        ], $extra));
    }
}
