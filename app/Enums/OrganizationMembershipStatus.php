<?php

namespace App\Enums;

enum OrganizationMembershipStatus: string
{
    case Active = 'active';
    case Invited = 'invited';
    case Removed = 'removed';
}
