<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Models\CustomerModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateCustomerCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_regular_customer(): void
    {
        $this->artisan('customer:create')
            ->expectsQuestion('Name', 'Jane Doe')
            ->expectsQuestion('Email', 'jane@example.com')
            ->expectsConfirmation('Is this a premium customer?', 'no')
            ->expectsOutputToContain('Customer created successfully.')
            ->expectsOutputToContain('jane@example.com')
            ->assertSuccessful();

        $this->assertDatabaseHas('customers', [
            'name'       => 'Jane Doe',
            'email'      => 'jane@example.com',
            'is_premium' => false,
        ]);
    }

    public function test_creates_premium_customer(): void
    {
        $this->artisan('customer:create')
            ->expectsQuestion('Name', 'John VIP')
            ->expectsQuestion('Email', 'john@example.com')
            ->expectsConfirmation('Is this a premium customer?', 'yes')
            ->expectsOutputToContain('Customer created successfully.')
            ->assertSuccessful();

        $this->assertDatabaseHas('customers', ['email' => 'john@example.com', 'is_premium' => true]);
    }

    public function test_rejects_duplicate_email_and_retries(): void
    {
        CustomerModel::create(['name' => 'Existing', 'email' => 'existing@example.com', 'is_premium' => false]);

        $this->artisan('customer:create')
            ->expectsQuestion('Name', 'New User')
            ->expectsQuestion('Email', 'existing@example.com')
            ->expectsConfirmation('Try again?', 'yes')
            ->expectsQuestion('Email', 'new@example.com')
            ->expectsConfirmation('Is this a premium customer?', 'no')
            ->assertSuccessful();

        $this->assertDatabaseHas('customers', ['email' => 'new@example.com']);
        $this->assertDatabaseCount('customers', 2);
    }
}
