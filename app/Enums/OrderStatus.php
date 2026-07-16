<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingContact = 'pending_contact';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingContact => 'In attesa di contatto',
            self::Confirmed => 'Confermato',
            self::Preparing => 'In preparazione',
            self::Delivered => 'Consegnato',
            self::Cancelled => 'Annullato',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingContact => 'warning',
            self::Confirmed => 'info',
            self::Preparing => 'primary',
            self::Delivered => 'success',
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
