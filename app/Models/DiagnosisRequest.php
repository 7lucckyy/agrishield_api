<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['farm_id', 'requested_by_user_id', 'image_path', 'image_checksum'])]
final class DiagnosisRequest extends Model {}
