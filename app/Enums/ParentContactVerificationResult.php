<?php

namespace App\Enums;

enum ParentContactVerificationResult
{
    case Verified;
    case Incorrect;
    case Expired;
    case TooManyAttempts;
    case NoPendingCode;
}
