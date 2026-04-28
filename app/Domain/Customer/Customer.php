<?php

namespace App\Domain\Customer;

class Customer
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly bool $isPremium,
    ) {}
}
