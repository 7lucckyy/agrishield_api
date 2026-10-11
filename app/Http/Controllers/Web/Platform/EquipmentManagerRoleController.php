<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Platform;

use App\Enums\GlobalRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Platform\UpdateEquipmentManagerRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class EquipmentManagerRoleController extends Controller
{
    public function __invoke(UpdateEquipmentManagerRoleRequest $request, User $user): RedirectResponse
    {
        if ($request->boolean('enabled')) {
            $user->assignRole(GlobalRole::EquipmentManager->value);
        } else {
            $user->removeRole(GlobalRole::EquipmentManager->value);
        }

        return to_route('platform.users')->with('status', 'Equipment manager access updated.');
    }
}
