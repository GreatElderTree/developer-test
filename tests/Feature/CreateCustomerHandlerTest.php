<?php

namespace Tests\Feature;

use App\Application\Customer\CreateCustomerCommand;
use App\Application\Customer\CreateCustomerHandler;
use App\Exceptions\DuplicateEmailException;
use App\Infrastructure\Persistence\Models\CustomerModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateCustomerHandlerTest extends TestCase
{
    use RefreshDatabase;

    private CreateCustomerHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = app(CreateCustomerHandler::class);
    }

    public function test_creates_regular_customer(): void
    {
        $customer = $this->handler->handle(new CreateCustomerCommand(
            name:      'Jane Doe',
            email:     'jane@example.com',
            isPremium: false,
        ));

        $this->assertNotNull($customer->id);
        $this->assertEquals('Jane Doe',         $customer->name);
        $this->assertEquals('jane@example.com', $customer->email);
        $this->assertFalse($customer->isPremium);

        $this->assertDatabaseHas('customers', ['email' => 'jane@example.com', 'is_premium' => false]);
    }

    public function test_creates_premium_customer(): void
    {
        $customer = $this->handler->handle(new CreateCustomerCommand(
            name:      'VIP User',
            email:     'vip@example.com',
            isPremium: true,
        ));

        $this->assertNotNull($customer->id);
        $this->assertEquals('VIP User',        $customer->name);
        $this->assertEquals('vip@example.com', $customer->email);
        $this->assertTrue($customer->isPremium);
        $this->assertDatabaseHas('customers', ['email' => 'vip@example.com', 'is_premium' => true]);
    }

    public function test_throws_on_duplicate_email(): void
    {
        CustomerModel::create(['name' => 'Existing', 'email' => 'taken@example.com', 'is_premium' => false]);

        $this->expectException(DuplicateEmailException::class);

        $this->handler->handle(new CreateCustomerCommand(
            name:  'New User',
            email: 'taken@example.com',
        ));
    }

    public function test_duplicate_check_does_not_persist_the_failed_attempt(): void
    {
        CustomerModel::create(['name' => 'Existing', 'email' => 'taken@example.com', 'is_premium' => false]);

        try {
            $this->handler->handle(new CreateCustomerCommand(name: 'New', email: 'taken@example.com'));
        } catch (DuplicateEmailException) {
        }

        $this->assertDatabaseCount('customers', 1);
    }
}
