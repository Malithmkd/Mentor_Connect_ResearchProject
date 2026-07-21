@extends('layouts.app')

@section('title', 'My Gigs')

@section('content')
<section class="dashboard">
    <div class="dashboard__inner">
        <header class="dashboard__header">
            <div>
                <h1 class="dashboard__title">My Gigs</h1>
                <p class="dashboard__subtitle">Manage your mentoring session offerings.</p>
            </div>
            <a href="{{ route('mentor.gigs.create') }}" class="btn btn--primary btn--sm">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                New Gig
            </a>
        </header>

        @if ($gigs->count() > 0)
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Bookings</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($gigs as $gig)
                            <tr>
                                <td>
                                    <a href="{{ route('gigs.show', $gig->slug) }}" class="font-semibold" style="color: var(--color-gray-900);">
                                        {{ Str::limit($gig->title, 50) }}
                                    </a>
                                </td>
                                <td>{{ $gig->formatted_price }}</td>
                                <td><span class="badge badge--{{ $gig->status->colorClass() }}">{{ $gig->status->label() }}</span></td>
                                <td>{{ $gig->bookings_count }}</td>
                                <td>
                                    <div style="display: flex; gap: var(--space-2);">
                                        <a href="{{ route('mentor.gigs.edit', $gig) }}" class="btn btn--secondary btn--xs">Edit</a>
                                        @if ($gig->deleted_at)
                                            <form method="POST" action="{{ route('mentor.gigs.restore', $gig->id) }}" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn--success btn--xs">Restore</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('mentor.gigs.destroy', $gig) }}" style="display: inline;" onsubmit="return confirm('Archive this gig?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn--error btn--xs">Archive</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $gigs->links('partials.pagination') }}
        @else
            <div class="empty">
                <p class="empty__text">No gigs yet. <a href="{{ route('mentor.gigs.create') }}">Create your first gig</a> to start receiving bookings.</p>
            </div>
        @endif
    </div>
</section>
@endsection
