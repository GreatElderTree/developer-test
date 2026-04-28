<?php

namespace App\Domain\Customer\Ports;

use App\Domain\Customer\Customer;

/** Port that decouples customer lookups from Eloquent — used by both the order flow (guest vs. registered) and the create-customer flow. */
interface CustomerRepositoryInterface
{
    public function findByEmail(string $email): ?Customer;

    public function save(Customer $customer): Customer;
}
