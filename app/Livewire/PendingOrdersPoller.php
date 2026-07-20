<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PendingOrdersPoller extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->count = $this->pendingCount();
    }

    public function poll(): void
    {
        $this->count = $this->pendingCount();
        $this->dispatch('pending-orders-count-updated', count: $this->count);
    }

    public function render(): View
    {
        return view('livewire.pending-orders-poller');
    }

    private function pendingCount(): int
    {
        return Order::query()
            ->where('status', OrderStatus::WhatsAppPending)
            ->count();
    }
}
