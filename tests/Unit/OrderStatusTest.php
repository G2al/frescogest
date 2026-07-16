<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_all_order_states_have_an_italian_label_and_color(): void
    {
        foreach (OrderStatus::cases() as $status) {
            $this->assertNotSame('', $status->label());
            $this->assertNotSame('', $status->color());
            $this->assertSame($status->label(), OrderStatus::options()[$status->value]);
        }
    }
}
