<?php

declare(strict_types=1);

namespace App\Enums;

enum VoiceAssistanceStatus: string
{
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
