<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Orders\CreateOrderService;
use App\Services\WhatsApp\WhatsAppLinkService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store(
        StoreOrderRequest $request,
        CreateOrderService $orders,
        WhatsAppLinkService $whatsApp,
    ) {
        $order = $orders->create($request->user(), $request->validated());
        $whatsAppData = $whatsApp->create($order);

        return response()->json([
            'data' => [
                'order' => new OrderResource($order),
                'order_number' => $order->order_number,
                'whatsapp_url' => $whatsAppData['url'],
                'whatsapp_message' => $whatsAppData['message'],
            ],
            'message' => 'Richiesta d’ordine salvata correttamente.',
        ], 201);
    }

    public function index(Request $request)
    {
        return OrderResource::collection(
            $request->user()->customer->orders()
                ->with('items.product')
                ->latest('requested_at')
                ->paginate(20),
        );
    }

    public function show(Request $request, string $orderNumber): OrderResource
    {
        $order = Order::query()
            ->where('customer_id', $request->user()->customer->id)
            ->where('order_number', $orderNumber)
            ->with('items.product')
            ->firstOrFail();

        return new OrderResource($order);
    }
}
