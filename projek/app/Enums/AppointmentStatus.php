<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case SCHEDULED = 'SCHEDULED';
    case CONFIRMED = 'CONFIRMED';
    case WAITING = 'WAITING';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';
    case NO_SHOW = 'NO_SHOW';
}
