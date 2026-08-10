<?php

namespace App\Exceptions\Auth;

use Illuminate\Contracts\Debug\ShouldntReport;
use RuntimeException;

final class InvalidCredentialsException extends RuntimeException implements ShouldntReport {}
