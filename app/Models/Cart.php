<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function updateTotal()
    {
        $sum = 0;
        foreach ($this->items()->get() as $item) {
            $sum += $item['product']['price'] * $item['quantity'];
        }
        $this->update(['total' => $sum]);
    }

    public static function getDiscountedTotal($cart): float
    {
        $total = 0;
        foreach ($cart->items()->get() as $item) {
            $total += $item->product->getDiscountedPrice() * $item->quantity;
        }
        return round($total, 2);
    }
}
