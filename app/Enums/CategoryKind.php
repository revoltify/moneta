<?php

declare(strict_types=1);

namespace App\Enums;

enum CategoryKind: string
{
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
