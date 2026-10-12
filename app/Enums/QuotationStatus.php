<?php

namespace App\Enums;

enum QuotationStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Converted = 'converted';
    case Cancelled = 'cancelled';
}
