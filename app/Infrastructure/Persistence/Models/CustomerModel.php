<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerModel extends Model
{
    protected $table    = 'customers';
    protected $fillable = ['name', 'email', 'is_premium'];
    protected $casts    = ['is_premium' => 'boolean'];

    public function orders(): HasMany
    {
        return $this->hasMany(OrderModel::class, 'customer_id');
    }
}
