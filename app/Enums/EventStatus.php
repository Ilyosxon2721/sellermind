<?php

declare(strict_types=1);

namespace App\Enums;

enum EventStatus: string
{
    case RECEIVED = 'received';
    case PROCESSING = 'processing';
    case PROCESSED = 'processed';
    case FAILED = 'failed';
    case SKIPPED = 'skipped';
}
