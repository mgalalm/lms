<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TeacherRequestType: string implements HasLabel
{
    case Leave = 'leave';
    case ExcusedTime = 'excused_time';
    case HrRequest = 'hr_request';
    case Support = 'support';
    case Resignation = 'resignation';

    public function getLabel(): string
    {
        return match ($this) {
            self::Leave => 'Leave Request',
            self::ExcusedTime => 'Excused Time',
            self::HrRequest => 'HR Request',
            self::Support => 'Support',
            self::Resignation => 'Resignation',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Leave => 'Planned or emergency leave request.',
            self::ExcusedTime => 'Absence for valid reasons.',
            self::HrRequest => 'Raise and manage HR tickets.',
            self::Support => 'Submit a ticket, inquiry, or recommendation.',
            self::Resignation => 'Stepping down from position.',
        };
    }
}
