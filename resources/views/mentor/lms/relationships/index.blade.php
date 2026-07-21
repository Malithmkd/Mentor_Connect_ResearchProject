@extends('layouts.app')

@section('title', 'Long-Term Mentorships')

@section('content')
<section class="dashboard">
    <div class="dashboard__inner">
        <header class="dashboard__header">
            <div>
                <h1 class="dashboard__title">Long-Term Mentorships</h1>
                <p class="dashboard__subtitle">Manage your ongoing mentoring relationships and their courses.</p>
            </div>
        </header>

        @include('partials.flash')

        <div class="panel">
            <div class="panel__header">
                <h2 class="panel__title">Relationship Requests ({{ $relationships->total() }})</h2>
            </div>
            <div class="panel__body">
                @if($relationships->count() > 0)
                    <div class="lms-rel-list">
                        @foreach($relationships as $rel)
                        <div class="lms-rel-card">
                            <div class="lms-rel-card__avatar">
                                <img src="{{ $rel->freelancer->avatar_url }}" alt="{{ $rel->freelancer->full_name }}">
                            </div>
                            <div class="lms-rel-card__info">
                                <p class="lms-rel-card__name">{{ $rel->freelancer->full_name }}</p>
                                <p class="lms-rel-card__meta">
                                    Requested {{ $rel->requested_at->diffForHumans() }}
                                    @if($rel->payment_amount)
                                        &middot; <strong>${{ number_format($rel->payment_amount, 2) }}</strong> / {{ $rel->payment_type }}
                                    @endif
                                </p>
                                @if($rel->payment_notes)
                                    <p class="lms-rel-card__notes">{{ $rel->payment_notes }}</p>
                                @endif
                            </div>
                            <div class="lms-rel-card__status">
                                <span class="badge badge--{{ $rel->status->colorClass() }}">
                                    {{ $rel->status->label() }}
                                </span>
                            </div>
                            <div class="lms-rel-card__actions">
                                @if($rel->isPending())
                                    <form method="POST" action="{{ route('mentor.lms.relationships.accept', $rel) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn--success btn--sm">Accept</button>
                                    </form>
                                    <form method="POST" action="{{ route('mentor.lms.relationships.decline', $rel) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn--error btn--sm"
                                            onclick="return confirm('Decline this long-term request?')">Decline</button>
                                    </form>
                                @elseif($rel->isAccepted())
                                    <a href="{{ route('mentor.lms.courses.index', $rel) }}"
                                       class="btn btn--primary btn--sm">
                                        Courses ({{ $rel->courses->count() }})
                                    </a>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="pagination-wrapper">{{ $relationships->links() }}</div>
                @else
                    <div class="empty">
                        <p class="empty__text">No long-term relationship requests yet. They appear here once a freelancer sends one after a completed session.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
