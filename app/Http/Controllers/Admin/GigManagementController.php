<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gig;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * GigManagementController
 * Admin overview of all gigs on the platform.
 */
class GigManagementController extends Controller
{
    /**
     * List all gigs with optional filtering and sorting.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'status', 'sort']);

        $query = Gig::withTrashed(false)
            ->with(['mentor', 'skills'])
            ->withCount('bookings');

        // Search by title or mentor name
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('mentor', fn ($m) =>
                      $m->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name',  'like', "%{$search}%")
                        ->orWhere('email',      'like', "%{$search}%")
                  );
            });
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Sorting
        match ($filters['sort'] ?? 'newest') {
            'oldest'     => $query->oldest(),
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'rating'     => $query->orderByDesc('average_rating'),
            default      => $query->latest(),
        };

        $gigs = $query->paginate(20)->withQueryString();

        // Summary stats
        $stats = [
            'total'     => Gig::count(),
            'published' => Gig::where('status', 'published')->count(),
            'draft'     => Gig::where('status', 'draft')->count(),
            'paused'    => Gig::whereIn('status', ['paused', 'archived'])->count(),
        ];

        return view('admin.gigs.index', compact('gigs', 'filters', 'stats'));
    }
}
