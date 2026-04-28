<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerModel extends Model
{
    protected $table    = 'customers';
    protected $fillable = ['name', 'email', 'is_premium'];
    protected $casts    = ['is_premium' => 'boolean'];
}
