<?php

namespace App\Http\Controllers;

use App\Enums\GigStatus;
use App\Http\Requests\Gig\StoreGigRequest;
use App\Http\Requests\Search\SearchGigsRequest;
use App\Models\Gig;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * GigController
 * Handles gig CRUD operations and public listing.
 * Freelancers can browse; mentors can manage their own.
 */
class GigController extends Controller
{
    /**
     * Public gig listing with search and filters.
     * Accessible to all users (including guests).
     */
    public function index(SearchGigsRequest $request): View
    {
        $validated = $request->validated();

        $query = Gig::published()
            ->with(['mentor', 'skills', 'mentor.mentorProfile']);

        // Text search on title/description
        if (!empty($validated['q'])) {
            $query->search($validated['q']);
        }

        // Filter by skills
        if (!empty($validated['skills'])) {
            $query->bySkills($validated['skills']);
        }

        // Filter by experience level
        if (!empty($validated['experience_level'])) {
            $query->byExperienceLevel($validated['experience_level']);
        }

        // Price range filter
        if (!empty($validated['min_price']) || !empty($validated['max_price'])) {
            $query->priceRange(
                $validated['min_price'] ?? null,
                $validated['max_price'] ?? null
            );
        }

        // Minimum rating filter
        if (!empty($validated['min_rating'])) {
            $query->where('average_rating', '>=', $validated['min_rating']);
        }

        // ── Skill-Based Personalization ──
        // For authenticated users with saved skill preferences, boost matching gigs.
        // Only applies when no explicit skill filter is active (to avoid double-filtering).
        $userSkillIds = [];
        if (auth()->check() && empty($validated['skills'])) {
            $userSkillIds = auth()->user()->skills()->pluck('skills.id')->toArray();
        }

        // Sorting
        $sort = $validated['sort'] ?? 'newest';

        if (!empty($userSkillIds) && $sort === 'newest') {
            // Boost gigs that share skills with the user using a scalar subquery.
            // Avoids join/groupBy MySQL ONLY_FULL_GROUP_BY strict mode errors during pagination.
            $skillIdsCsv = implode(',', array_map('intval', $userSkillIds));
            $query->orderByRaw("(SELECT COUNT(*) FROM gig_skill WHERE gig_skill.gig_id = gigs.id AND gig_skill.skill_id IN ({$skillIdsCsv})) DESC")
                  ->latest('gigs.created_at');
        } else {
            match ($sort) {
                'rating'     => $query->orderByRating('desc'),
                'price_asc'  => $query->orderByPrice('asc'),
                'price_desc' => $query->orderByPrice('desc'),
                'popularity' => $query->orderByPopularity(),
                default      => $query->latest(),
            };
        }

        $gigs = $query->paginate($validated['per_page'] ?? 12);
        $skills = Skill::active()->orderBy('name')->get();

        return view('gigs.index', [
            'gigs'          => $gigs,
            'skills'        => $skills,
            'filters'       => $validated,
            'activeFilters' => collect($validated)->filter(fn($v) => !empty($v))->count(),
        ]);
    }

    /**
     * Show single gig detail.
     */
    public function show(string $slug): View
    {
        $gig = Gig::with(['mentor.mentorProfile', 'skills', 'reviews.freelancer'])
            ->where('slug', $slug)
            ->firstOrFail();

        $gig->increment('total_views');

        return view('gigs.show', [
            'gig' => $gig,
            'reviews' => $gig->reviews()->public()->recent()->take(5)->get(),
        ]);
    }

    /**
     * Show mentor's gig management dashboard.
     * Protected by 'role:mentor' middleware.
     */
    public function mentorIndex(): View
    {
        $gigs = Gig::byMentor(auth()->id())
            ->withCount('bookings')
            ->latest()
            ->paginate(10);

        return view('mentor.gigs.index', ['gigs' => $gigs]);
    }

    /**
     * Show gig creation form.
     */
    public function create(): View
    {
        $skills = Skill::active()->orderBy('name')->get();
        return view('mentor.gigs.create', ['skills' => $skills]);
    }

    /**
     * Store new gig.
     */
    public function store(StoreGigRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Handle cover image upload
        $coverImagePath = null;
        if ($request->hasFile('cover_image')) {
            $coverImagePath = $request->file('cover_image')->store('gigs', 'public');
        }

        $gig = Gig::create([
            'mentor_id'               => auth()->id(),
            'title'                   => $data['title'],
            'slug'                    => Str::slug($data['title']) . '-' . uniqid(),
            'description'             => $data['description'],
            'what_to_expect'          => $data['what_to_expect'] ?? null,
            'prerequisites'           => $data['prerequisites'] ?? null,
            'cover_image'             => $coverImagePath,
            'delivery_format'         => $data['delivery_format'],
            'experience_level'        => $data['experience_level'],
            'duration_minutes'        => $data['duration_minutes'],
            'price'                   => $data['price'],
            'status'                  => $data['status'],
            'max_sessions_per_week'   => $data['max_sessions_per_week'],
            'booking_lead_time_hours' => $data['booking_lead_time_hours'],
        ]);

        $gig->skills()->sync($data['skills']);

        return redirect()
            ->route('mentor.gigs.index')
            ->with('success', 'Gig created successfully!');
    }

    /**
     * Show gig edit form.
     */
    public function edit(Gig $gig): View
    {
        $this->authorize('update', $gig);

        $skills = Skill::active()->orderBy('name')->get();

        return view('mentor.gigs.edit', [
            'gig' => $gig,
            'skills' => $skills,
        ]);
    }

    /**
     * Update gig.
     */
    public function update(StoreGigRequest $request, Gig $gig): RedirectResponse
    {
        $this->authorize('update', $gig);

        $data = $request->validated();

        // Handle cover image upload — delete old one if a new file is provided
        $coverImagePath = $gig->cover_image;
        if ($request->hasFile('cover_image')) {
            if ($coverImagePath) {
                Storage::disk('public')->delete($coverImagePath);
            }
            $coverImagePath = $request->file('cover_image')->store('gigs', 'public');
        }

        $gig->update([
            'title'                   => $data['title'] ?? $gig->title,
            'description'             => $data['description'] ?? $gig->description,
            'what_to_expect'          => $data['what_to_expect'] ?? $gig->what_to_expect,
            'prerequisites'           => $data['prerequisites'] ?? $gig->prerequisites,
            'cover_image'             => $coverImagePath,
            'delivery_format'         => $data['delivery_format'] ?? $gig->delivery_format,
            'experience_level'        => $data['experience_level'] ?? $gig->experience_level,
            'duration_minutes'        => $data['duration_minutes'] ?? $gig->duration_minutes,
            'price'                   => $data['price'] ?? $gig->price,
            'status'                  => $data['status'] ?? $gig->status,
            'max_sessions_per_week'   => $data['max_sessions_per_week'] ?? $gig->max_sessions_per_week,
            'booking_lead_time_hours' => $data['booking_lead_time_hours'] ?? $gig->booking_lead_time_hours,
        ]);

        if (!empty($data['skills'])) {
            $gig->skills()->sync($data['skills']);
        }

        return redirect()
            ->route('mentor.gigs.index')
            ->with('success', 'Gig updated successfully!');
    }

    /**
     * Soft delete gig.
     */
    public function destroy(Gig $gig): RedirectResponse
    {
        $this->authorize('delete', $gig);

        $gig->delete();

        return redirect()
            ->route('mentor.gigs.index')
            ->with('success', 'Gig moved to archive.');
    }

    /**
     * Restore soft-deleted gig.
     */
    public function restore(int $id): RedirectResponse
    {
        $gig = Gig::withTrashed()->findOrFail($id);
        $this->authorize('restore', $gig);

        $gig->restore();

        return redirect()
            ->route('mentor.gigs.index')
            ->with('success', 'Gig restored.');
    }
}
