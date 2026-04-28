<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemModel extends Model
{
    protected $table    = 'order_items';
    protected $fillable = ['order_id', 'product_id', 'product_name', 'qty', 'unit_price'];
    protected $casts    = ['unit_price' => 'decimal:2', 'qty' => 'integer'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderModel::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}
