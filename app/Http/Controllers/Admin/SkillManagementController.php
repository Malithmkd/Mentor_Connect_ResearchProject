<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Admin Skill Management Controller
 * Allows administrators to add, edit, and delete skills
 * from the master skills list. Skills can be assigned to
 * gigs and selected by users as preferences.
 */
class SkillManagementController extends Controller
{
    public function index(Request $request): View
    {
        $query = Skill::withCount(['gigs', 'users']);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
        }

        $skills = $query->orderBy('category')->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.skills.index', compact('skills'));
    }

    public function create(): View
    {
        return view('admin.skills.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100', 'unique:skills,name'],
            'category'  => ['nullable', 'string', 'max:100'],
            'icon'      => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        Skill::create([
            'name'      => $validated['name'],
            'slug'      => Str::slug($validated['name']),
            'category'  => $validated['category'] ?? null,
            'icon'      => $validated['icon'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.skills.index')
            ->with('success', "Skill \"{$validated['name']}\" created successfully.");
    }

    public function edit(Skill $skill): View
    {
        return view('admin.skills.edit', compact('skill'));
    }

    public function update(Request $request, Skill $skill): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100', 'unique:skills,name,' . $skill->id],
            'category'  => ['nullable', 'string', 'max:100'],
            'icon'      => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        $skill->update([
            'name'      => $validated['name'],
            'slug'      => Str::slug($validated['name']),
            'category'  => $validated['category'] ?? $skill->category,
            'icon'      => $validated['icon'] ?? $skill->icon,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.skills.index')
            ->with('success', "Skill \"{$skill->name}\" updated successfully.");
    }

    public function destroy(Skill $skill): RedirectResponse
    {
        $name = $skill->name;
        $skill->delete();

        return redirect()->route('admin.skills.index')
            ->with('success', "Skill \"{$name}\" deleted.");
    }
}
