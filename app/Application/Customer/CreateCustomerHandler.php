<?php

namespace App\Application\Customer;

use App\Domain\Customer\Customer;
use App\Domain\Customer\Ports\CustomerRepositoryInterface;
use App\Exceptions\DuplicateEmailException;

/** New: original had no customer creation path — this handler adds duplicate-email protection before persisting. */
class CreateCustomerHandler
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {}

    public function handle(CreateCustomerCommand $command): Customer
    {
        if ($this->customerRepository->findByEmail($command->email) !== null) {
            throw new DuplicateEmailException("Email {$command->email} is already registered.");
        }

        return $this->customerRepository->save(
            new Customer(
                id:        null,
                name:      $command->name,
                email:     $command->email,
                isPremium: $command->isPremium,
            )
        );
    }
}
