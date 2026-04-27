<?php

namespace Khumam\Midtrans\Enums;

enum MidtransPeriod: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case SemiAnnually = 'semi_annually';
    case Annually = 'annually';

    public function toDate(): string
    {
        return match ($this) {
            self::Monthly => now()->addMonth(),
            self::Quarterly => now()->addQuarter(),
            self::SemiAnnually => now()->addMonths(6),
            self::Annually => now()->addYear(),
        };
    }
}
