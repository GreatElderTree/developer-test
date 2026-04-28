<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductModel extends Model
{
    protected $table    = 'products';
    protected $fillable = ['name', 'price'];
    protected $casts    = ['price' => 'decimal:2'];

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItemModel::class, 'product_id');
    }
}
