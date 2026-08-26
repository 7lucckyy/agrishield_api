<?php

namespace App\Http\Controllers\Web\Platform;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __invoke(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $users = User::query()
            ->with(['organizations:id,name'])
            ->withCount('farms')
            ->when($search !== '', fn ($query) => $query->where(fn ($match) => $match
                ->where('name', 'ilike', "%{$search}%")
                ->orWhere('email', 'ilike', "%{$search}%")))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('platform.users', compact('users', 'search'));
    }
}
