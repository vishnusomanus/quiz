<?php

namespace App\Enums;

enum QuizAttemptStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Expired = 'expired';
}
