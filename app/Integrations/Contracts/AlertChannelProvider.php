<?php

declare(strict_types=1);

namespace App\Integrations\Contracts;

use App\Models\AlertDelivery;

interface AlertChannelProvider
{
    public function send(AlertDelivery $delivery): string;
}
