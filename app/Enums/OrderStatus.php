<?php

namespace App\Enums;

enum OrderStatus: string
{
    case WhatsAppPending = 'whatsapp_pending';
    case Confirmed = 'confirmed';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public const PendingContact = self::WhatsAppPending;

    public const Preparing = self::Confirmed;

    public const Delivered = self::Paid;

    public function label(): string
    {
        return match ($this) {
            self::WhatsAppPending => 'In trattativa WhatsApp',
            self::Confirmed => 'Confermato',
            self::Paid => 'Pagato',
            self::Cancelled => 'Annullato',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::WhatsAppPending => 'warning',
            self::Confirmed => 'info',
            self::Paid => 'success',
            self::Cancelled => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
