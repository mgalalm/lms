<?php

namespace App\Enums;

enum SupportTicketStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';
}
