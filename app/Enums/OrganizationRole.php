<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case Farmer = 'farmer';
    case OrganizationAdmin = 'organization_admin';
    case ClusterLead = 'cluster_lead';
    case Agronomist = 'agronomist';
}
