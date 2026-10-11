@extends('layouts.admin', ['area' => 'platform'])
@section('title', 'People') @section('eyebrow', 'Platform control') @section('page-title', 'People')
@section('content')
<section class="page-lead compact"><div><p class="eyebrow"><span></span> Access directory</p><h1>People</h1><p>Review accounts, organization assignments and equipment desk access.</p></div></section>
@if(session('status'))<div class="status-message" role="status">{{ session('status') }}</div>@endif
<section class="panel"><div class="toolbar"><form class="search-form" method="GET"><span>⌕</span><input name="search" value="{{ $search }}" placeholder="Search name or email"><button>Search</button></form><span>{{ $users->total() }} accounts</span></div><div class="data-table-wrap"><table class="data-table"><thead><tr><th>Person</th><th>Status</th><th>Organizations</th><th>Owned farms</th><th>Last active</th><th>Equipment desk</th></tr></thead><tbody>
@forelse($users as $user)<tr><td><div class="entity-link"><span class="entity-avatar">{{ str($user->name)->substr(0, 2)->upper() }}</span><span><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></span></div></td><td><span class="status-pill status-{{ $user->status->value }}">{{ str($user->status->value)->headline() }}</span></td><td>{{ $user->organizations->pluck('name')->join(', ') ?: 'Unassigned' }}</td><td>{{ $user->farms_count }}</td><td>{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td><td>
@if($user->hasRole(\App\Enums\GlobalRole::PlatformAdmin->value))Platform admin
@else<form method="POST" action="{{ route('platform.users.equipment-manager.update', $user) }}">@csrf @method('PATCH')<input type="hidden" name="enabled" value="{{ $user->hasRole(\App\Enums\GlobalRole::EquipmentManager->value) ? 0 : 1 }}"><button type="submit">{{ $user->hasRole(\App\Enums\GlobalRole::EquipmentManager->value) ? 'Revoke access' : 'Grant access' }}</button></form>@endif
</td></tr>@empty<tr><td colspan="6"><div class="empty-state"><strong>No matching accounts</strong><span>Try a different search term.</span></div></td></tr>@endforelse
</tbody></table></div><div class="pagination">{{ $users->links() }}</div></section>
@endsection
