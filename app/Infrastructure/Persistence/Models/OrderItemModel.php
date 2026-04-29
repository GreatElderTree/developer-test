<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemModel extends Model
{
    protected $table    = 'order_items';
    protected $fillable = ['order_id', 'product_id', 'product_name', 'qty', 'unit_price'];
    protected $casts    = ['unit_price' => 'integer', 'qty' => 'integer'];
}
