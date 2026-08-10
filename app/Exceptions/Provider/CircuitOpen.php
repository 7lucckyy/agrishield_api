<?php

declare(strict_types=1);

namespace App\Exceptions\Provider;

use Illuminate\Contracts\Debug\ShouldntReport;

final class CircuitOpen extends ProviderException implements ShouldntReport {}
