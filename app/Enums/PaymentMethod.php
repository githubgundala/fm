<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Transfer = 'transfer';
    case Cash = 'cash';
    case Va = 'va';
    case Qris = 'qris';
}
