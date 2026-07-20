<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\Orders\RecordOrderPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecordOrderPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_a_full_payment_marks_the_order_as_paid(): void
    {
        $customer = Customer::factory()->create();
        $method = PaymentMethod::create(['name' => 'Contanti', 'active' => true]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Confirmed,
            'requested_at' => now(),
            'total_gross' => 104,
        ]);

        $this->expectException(ValidationException::class);
        app(RecordOrderPaymentService::class)->record($order, [
            'payment_method_id' => $method->id,
            'payment_amount' => 100,
            'paid_at' => now(),
        ]);
    }

    public function test_full_payment_records_amount_date_method_and_paid_status(): void
    {
        $customer = Customer::factory()->create();
        $method = PaymentMethod::create(['name' => 'Bonifico', 'active' => true]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Confirmed,
            'requested_at' => now(),
            'total_gross' => 104,
        ]);

        app(RecordOrderPaymentService::class)->record($order, [
            'payment_method_id' => $method->id,
            'payment_amount' => 104,
            'paid_at' => now(),
        ]);

        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame('104.00', $order->payment_amount);
        $this->assertSame($method->id, $order->payment_method_id);
        $this->assertNotNull($order->paid_at);
    }
}
