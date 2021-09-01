<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function getTotalQuantity(): int
    {
        return $this->items()->sum('quantity');
    }

    public function getSubTotal(): float
    {
        $sum = 0;
        foreach ($this->items()->get() as $item) {
            $sum += $item['quantity'] * $item['price'];
        }
        return $sum;
    }

    public function getTotal()
    {
        $shipping = $this->shipping ?? 0;
        return $this->getSubTotal() + $shipping;
    }

    public static function getCode(): int
    {
        do {
            $key = mt_rand(1000000, 9999999);
        } while (static::query()->where('code', $key)->count() > 0);
        return $key;
    }
}
