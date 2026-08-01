@extends('layouts.admin')

@section('title', 'Edit Skill')

@section('content')
<div class="adm-page-header">
    <div>
        <h1 class="adm-page-title">Edit Skill</h1>
        <p class="adm-page-subtitle">Update "{{ $skill->name }}"</p>
    </div>
    <a href="{{ route('admin.skills.index') }}" class="adm-btn adm-btn--ghost">← Back to Skills</a>
</div>

<div class="adm-card" style="max-width:600px">
    <div style="padding:1.5rem">
        <form method="POST" action="{{ route('admin.skills.update', $skill) }}">
            @csrf @method('PATCH')

            <div style="margin-bottom:1.25rem">
                <label style="display:block;font-weight:600;font-size:.875rem;margin-bottom:.4rem;color:var(--adm-text-700)">
                    Skill Name <span style="color:#ef4444">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name', $skill->name) }}" required
                       style="width:100%;padding:.6rem .875rem;border:1.5px solid var(--adm-border);border-radius:8px;font-size:.9rem;background:var(--adm-bg);color:var(--adm-text-900)">
                @error('name') <p style="color:#ef4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</p> @enderror
            </div>

            <div style="margin-bottom:1.25rem">
                <label style="display:block;font-weight:600;font-size:.875rem;margin-bottom:.4rem;color:var(--adm-text-700)">
                    Category
                </label>
                <input type="text" name="category" value="{{ old('category', $skill->category) }}"
                       style="width:100%;padding:.6rem .875rem;border:1.5px solid var(--adm-border);border-radius:8px;font-size:.9rem;background:var(--adm-bg);color:var(--adm-text-900)"
                       placeholder="e.g. Programming, Design, Business">
                @error('category') <p style="color:#ef4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</p> @enderror
            </div>

            <div style="margin-bottom:1.25rem">
                <label style="display:block;font-weight:600;font-size:.875rem;margin-bottom:.4rem;color:var(--adm-text-700)">
                    Icon (emoji)
                </label>
                <input type="text" name="icon" value="{{ old('icon', $skill->icon) }}" maxlength="10"
                       style="width:100%;padding:.6rem .875rem;border:1.5px solid var(--adm-border);border-radius:8px;font-size:.9rem;background:var(--adm-bg);color:var(--adm-text-900)"
                       placeholder="e.g. 🐍 💻 🎨">
                @error('icon') <p style="color:#ef4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</p> @enderror
            </div>

            <div style="margin-bottom:1.5rem;display:flex;align-items:center;gap:.5rem">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ $skill->is_active ? 'checked' : '' }}
                       style="width:16px;height:16px;cursor:pointer;accent-color:#3b82f6">
                <label for="is_active" style="font-weight:600;font-size:.875rem;color:var(--adm-text-700);cursor:pointer">
                    Active (visible to users and mentors)
                </label>
            </div>

            <div style="display:flex;gap:.75rem">
                <button type="submit" class="adm-btn adm-btn--primary">Update Skill</button>
                <a href="{{ route('admin.skills.index') }}" class="adm-btn adm-btn--ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
