<?php

namespace App\Exceptions\Auth;

use Illuminate\Contracts\Debug\ShouldntReport;
use RuntimeException;

final class InactiveAccountException extends RuntimeException implements ShouldntReport {}
