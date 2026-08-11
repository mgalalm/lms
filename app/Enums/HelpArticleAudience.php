<?php

namespace App\Enums;

enum HelpArticleAudience: string
{
    case All = 'all';
    case Teacher = 'teacher';
    case Student = 'student';
}
