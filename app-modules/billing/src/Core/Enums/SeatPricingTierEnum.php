<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Enums;

enum SeatPricingTierEnum
{
    case UpTo15;
    case UpTo30;
    case UpTo70;
    case Above70;

    public static function fromQuantity(int $qty): self
    {
        return match (true) {
            $qty <= 15 => self::UpTo15,
            $qty <= 30 => self::UpTo30,
            $qty <= 70 => self::UpTo70,
            default => self::Above70,
        };
    }

    public function pricePerSeat(): float
    {
        return match ($this) {
            self::UpTo15 => 44.90,
            self::UpTo30 => 34.90,
            self::UpTo70 => 24.90,
            self::Above70 => 11.90,
        };
    }

    public function minSeats(): int
    {
        return match ($this) {
            self::UpTo15 => 5,
            self::UpTo30 => 16,
            self::UpTo70 => 31,
            self::Above70 => 71,
        };
    }

    public function maxSeats(): ?int
    {
        return match ($this) {
            self::UpTo15 => 15,
            self::UpTo30 => 30,
            self::UpTo70 => 70,
            self::Above70 => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::UpTo15 => '5-15 colaboradores',
            self::UpTo30 => '16-30 colaboradores',
            self::UpTo70 => '31-70 colaboradores',
            self::Above70 => '71+ colaboradores',
        };
    }
}
