@extends('layouts.admin')

@section('title', 'Skills Management')

@section('content')
<div class="adm-page-header">
    <div>
        <h1 class="adm-page-title">Skills Management</h1>
        <p class="adm-page-subtitle">Manage the master list of skills available to mentors and users.</p>
    </div>
    <a href="{{ route('admin.skills.create') }}" class="adm-btn adm-btn--primary">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 2v12M2 8h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        Add New Skill
    </a>
</div>

{{-- Search --}}
<div class="adm-card" style="margin-bottom:1.5rem;padding:1rem 1.25rem">
    <form method="GET" action="{{ route('admin.skills.index') }}" style="display:flex;gap:.75rem;align-items:center">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search skills or categories…"
               style="flex:1;padding:.5rem .75rem;border:1px solid var(--adm-border);border-radius:8px;font-size:.875rem;background:var(--adm-bg);color:var(--adm-text-700)">
        <button type="submit" class="adm-btn adm-btn--primary adm-btn--sm">Search</button>
        @if(request('search'))
            <a href="{{ route('admin.skills.index') }}" class="adm-btn adm-btn--ghost adm-btn--sm">Clear</a>
        @endif
    </form>
</div>

<div class="adm-card">
    <table class="adm-table" style="width:100%">
        <thead>
            <tr>
                <th>Skill</th>
                <th>Category</th>
                <th>Status</th>
                <th>Gigs</th>
                <th>Users</th>
                <th style="text-align:right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($skills as $skill)
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:.5rem">
                        @if($skill->icon)
                            <span style="font-size:1.2rem">{{ $skill->icon }}</span>
                        @endif
                        <div>
                            <p style="font-weight:600;color:var(--adm-text-900)">{{ $skill->name }}</p>
                            <p style="font-size:.75rem;color:var(--adm-text-400)">{{ $skill->slug }}</p>
                        </div>
                    </div>
                </td>
                <td>
                    @if($skill->category)
                        <span style="display:inline-block;padding:.2rem .6rem;background:var(--adm-primary-50);color:var(--adm-primary);border-radius:20px;font-size:.75rem;font-weight:600">
                            {{ $skill->category }}
                        </span>
                    @else
                        <span style="color:var(--adm-text-400)">—</span>
                    @endif
                </td>
                <td>
                    @if($skill->is_active)
                        <span class="adm-badge adm-badge--green">Active</span>
                    @else
                        <span class="adm-badge adm-badge--gray">Inactive</span>
                    @endif
                </td>
                <td><span style="font-weight:600">{{ $skill->gigs_count }}</span></td>
                <td><span style="font-weight:600">{{ $skill->users_count }}</span></td>
                <td>
                    <div style="display:flex;justify-content:flex-end;gap:.5rem">
                        <a href="{{ route('admin.skills.edit', $skill) }}" class="adm-btn adm-btn--ghost adm-btn--sm">Edit</a>
                        <form method="POST" action="{{ route('admin.skills.destroy', $skill) }}" id="delSkill{{ $skill->id }}">
                            @csrf @method('DELETE')
                            <button type="button" class="adm-btn adm-btn--danger adm-btn--sm"
                                    onclick="confirmDeleteSkill({{ $skill->id }}, '{{ addslashes($skill->name) }}')">
                                Delete
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center;padding:3rem;color:var(--adm-text-400)">
                    No skills found. <a href="{{ route('admin.skills.create') }}">Add the first skill →</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="padding:1rem 1.25rem">
        {{ $skills->links('partials.pagination') }}
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmDeleteSkill(id, name) {
    Swal.fire({
        title: 'Delete Skill?',
        html: 'Are you sure you want to delete <strong>' + name + '</strong>? This will remove it from all gigs and user preferences.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
    }).then(function(r) {
        if (r.isConfirmed) document.getElementById('delSkill' + id).submit();
    });
}
</script>
@endpush
