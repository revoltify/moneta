<?php

declare(strict_types=1);

namespace App\Data;

final readonly class UserCompany
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?bool $isCurrent = null,
        public string $timezone = 'Asia/Dhaka',
        public string $currency = 'BDT',
    ) {
        //
    }
}
