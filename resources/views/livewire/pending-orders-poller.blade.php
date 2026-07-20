<div wire:poll.5s="poll" class="hidden" aria-hidden="true">
    <div data-pending-orders-badge-template>
        @if ($count > 0)
            <span class="fi-sidebar-item-badge-ctn">
                <x-filament::badge color="warning" tooltip="Ordini in trattativa WhatsApp">
                    {{ $count }}
                </x-filament::badge>
            </span>
        @endif
    </div>

    @script
        <script>
            const ordersPath = new URL(@js(\App\Filament\Resources\Orders\OrderResource::getUrl('index'))).pathname;

            const syncPendingOrdersBadge = () => {
                const ordersLink = [...document.querySelectorAll('.fi-sidebar-item-btn')]
                    .find(link => new URL(link.href).pathname === ordersPath);

                if (!ordersLink) return;

                ordersLink.querySelector('.fi-sidebar-item-badge-ctn')?.remove();
                const badge = $wire.$el.querySelector('[data-pending-orders-badge-template] .fi-sidebar-item-badge-ctn');

                if (badge) ordersLink.append(badge.cloneNode(true));
            };

            syncPendingOrdersBadge();
            $wire.on('pending-orders-count-updated', () => requestAnimationFrame(syncPendingOrdersBadge));
            document.addEventListener('livewire:navigated', syncPendingOrdersBadge);
        </script>
    @endscript
</div>
