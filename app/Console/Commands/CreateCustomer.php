<?php

namespace App\Console\Commands;

use App\Application\Customer\CreateCustomerCommand;
use App\Application\Customer\CreateCustomerHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/** New: original had no way to manage customers — artisan customer:create adds interactive inline validation and re-prompts on bad input. */
class CreateCustomer extends Command
{
    protected $signature   = 'customer:create';
    protected $description = 'Create a new customer';

    public function __construct(private readonly CreateCustomerHandler $handler)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->askValid('Name', ['required', 'string', 'max:255']);
        if ($name === null) {
            return self::FAILURE;
        }

        $email = $this->askValid('Email', ['required', 'email', 'unique:customers,email']);
        if ($email === null) {
            return self::FAILURE;
        }

        $isPremium = $this->confirm('Is this a premium customer?', default: false);

        $customer = $this->handler->handle(
            new CreateCustomerCommand(name: $name, email: $email, isPremium: $isPremium)
        );

        $this->info('Customer created successfully.');
        $this->table(
            ['ID', 'Name', 'Email', 'Premium'],
            [[$customer->id, $customer->name, $customer->email, $customer->isPremium ? 'Yes' : 'No']]
        );

        return self::SUCCESS;
    }

    private function askValid(string $label, array $rules): ?string
    {
        while (true) {
            $value     = $this->ask($label);
            $validator = Validator::make([$label => $value], [$label => $rules]);

            if (! $validator->fails()) {
                return $value;
            }

            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            if (! $this->confirm('Try again?', default: true)) {
                return null;
            }
        }
    }
}
