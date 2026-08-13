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

    public static function allowedTransitions(): array
    {
        return [
            self::SCHEDULED->value => [self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED->value => [self::WAITING, self::CANCELLED, self::NO_SHOW],
            self::WAITING->value => [self::IN_PROGRESS, self::CANCELLED],
            self::IN_PROGRESS->value => [self::COMPLETED],
            self::COMPLETED->value => [],
            self::CANCELLED->value => [],
            self::NO_SHOW->value => [],
        ];
    }

    public function canTransitionTo(self $target): bool
    {
        $allowed = self::allowedTransitions()[$this->value] ?? [];

        return in_array($target, $allowed, true);
    }
}
