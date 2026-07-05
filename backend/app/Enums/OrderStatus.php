<?php

namespace App\Enums;

/**
 * Order lifecycle — mirrors SPEC §6.
 *
 * pending_payment → paid_escrow → accepted → in_progress → with_courier
 *   → delivered → completed
 * cancelled       (free before accepted)
 * return_requested → return_pickup → returned_refunded
 */
enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case PaidEscrow = 'paid_escrow';
    case Accepted = 'accepted';
    case InProgress = 'in_progress';
    case WithCourier = 'with_courier';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case ReturnRequested = 'return_requested';
    case ReturnPickup = 'return_pickup';
    case ReturnedRefunded = 'returned_refunded';

    /** Allowed forward transitions (guarded in Order::transitionTo). */
    public function allowedNext(): array
    {
        return match ($this) {
            self::PendingPayment => [self::PaidEscrow, self::Cancelled],
            self::PaidEscrow => [self::Accepted, self::Cancelled],
            self::Accepted => [self::InProgress, self::Cancelled],
            self::InProgress => [self::WithCourier, self::ReturnRequested],
            self::WithCourier => [self::Delivered, self::ReturnRequested],
            self::Delivered => [self::Completed, self::ReturnRequested],
            self::Completed => [self::ReturnRequested],
            self::ReturnRequested => [self::ReturnPickup],
            self::ReturnPickup => [self::ReturnedRefunded],
            default => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    /** Free cancellation is only permitted before the seller accepts (SPEC §4.1). */
    public function isFreelyCancellable(): bool
    {
        return in_array($this, [self::PendingPayment, self::PaidEscrow], true);
    }

    /** Arabic label used by the app timeline (verbatim from prototype). */
    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'بانتظار الدفع',
            self::PaidEscrow => 'مدفوع ومحجوز',
            self::Accepted => 'قبلته المصممة',
            self::InProgress => 'قيد التنفيذ',
            self::WithCourier => 'مع المندوب',
            self::Delivered => 'تم التسليم',
            self::Completed => 'مكتمل',
            self::Cancelled => 'ملغي',
            self::ReturnRequested => 'إرجاع جاري',
            self::ReturnPickup => 'المندوب في الطريق للاستلام',
            self::ReturnedRefunded => 'مُرجَع — استُرد المبلغ',
        };
    }
}
