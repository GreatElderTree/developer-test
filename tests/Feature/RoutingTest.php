<?php

namespace Tests\Feature;

use Tests\TestCase;

class RoutingTest extends TestCase
{
    public function test_root_redirects_to_order_form(): void
    {
        $this->get('/')->assertRedirect(route('orders.create'));
    }
}
