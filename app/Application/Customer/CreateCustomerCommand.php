<?php

namespace App\Application\Customer;

/** New: original had no customer management — this is the input DTO for the artisan customer:create flow. */
class CreateCustomerCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly bool $isPremium = false,
    ) {}
}
