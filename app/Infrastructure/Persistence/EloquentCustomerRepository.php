<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Customer\Customer;
use App\Domain\Customer\Ports\CustomerRepositoryInterface;
use App\Infrastructure\Persistence\Models\CustomerModel;

/** Adapter for CustomerRepositoryInterface — maps between the Eloquent CustomerModel and the pure-PHP Customer domain object. */
class EloquentCustomerRepository implements CustomerRepositoryInterface
{
    public function findByEmail(string $email): ?Customer
    {
        $model = CustomerModel::where('email', $email)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function save(Customer $customer): Customer
    {
        $model = $customer->id
            ? CustomerModel::findOrFail($customer->id)
            : new CustomerModel();

        $model->fill([
            'name'       => $customer->name,
            'email'      => $customer->email,
            'is_premium' => $customer->isPremium,
        ])->save();

        return $this->toDomain($model);
    }

    private function toDomain(CustomerModel $model): Customer
    {
        return new Customer(
            id:        $model->id,
            name:      $model->name,
            email:     $model->email,
            isPremium: $model->is_premium,
        );
    }
}
