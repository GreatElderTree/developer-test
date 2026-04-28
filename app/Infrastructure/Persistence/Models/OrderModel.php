<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderModel extends Model
{
    protected $table    = 'orders';
    protected $fillable = [
        'customer_id', 'guest_email',
        'subtotal', 'discount_percentage', 'discount_amount', 'total', 'status',
    ];
    protected $casts = [
        'subtotal'            => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount'     => 'decimal:2',
        'total'               => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItemModel::class, 'order_id');
    }
}
