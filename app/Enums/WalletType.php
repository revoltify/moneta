<?php

declare(strict_types=1);

namespace App\Enums;

enum WalletType: string
{
    case Bank = 'bank';
    case MobileBanking = 'mobile_banking';
    case Cash = 'cash';
    case Card = 'card';
    case Savings = 'savings';

    /**
     * @return array<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::Bank => 'Bank',
            self::MobileBanking => 'Mobile Banking',
            self::Cash => 'Cash',
            self::Card => 'Card',
            self::Savings => 'Savings',
        };
    }
}
