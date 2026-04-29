<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class ProductModel extends Model
{
    protected $table    = 'products';
    protected $fillable = ['name', 'price'];
    protected $casts    = ['price' => 'integer'];
}
