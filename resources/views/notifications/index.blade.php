@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<section class="dashboard">
    <div class="dashboard__inner">
        <header class="dashboard__header">
            <div>
                <h1 class="dashboard__title">Notifications</h1>
                <p class="dashboard__subtitle">Stay up to date with your sessions and activity.</p>
            </div>

            @if ($notifications->total() > 0)
                <form method="POST" action="{{ route('notifications.readAll') }}">
                    @csrf
                    <button type="submit" class="btn btn--ghost btn--sm">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                            <path d="M2 8l4 4 8-8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Mark all as read
                    </button>
                </form>
            @endif
        </header>

        @include('partials.flash')

        <div class="panel">
            <div class="panel__body" style="padding:0">
                @forelse ($notifications as $notification)
                    <div class="notification-item {{ $notification->is_read ? '' : 'notification-item--unread' }}">
                        {{-- Icon by type --}}
                        <div class="notification-item__icon">
                            @switch($notification->type)
                                @case('booking_requested')
                                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none"><rect x="3" y="4" width="14" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M7 2v4M13 2v4M3 9h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                    @break
                                @case('booking_accepted')
                                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.5"/><path d="M7 10l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    @break
                                @case('booking_rejected')
                                @case('booking_cancelled')
                                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.5"/><path d="M7 7l6 6M13 7l-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                    @break
                                @case('booking_completed')
                                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none"><path d="M10 2l2.5 5.08L18 7.9l-4 3.9.94 5.5L10 14.77l-4.94 2.6L6 11.8l-4-3.9 5.5-.82L10 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    @break
                                @case('review_received')
                                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none"><path d="M10 2l2.5 5.08L18 7.9l-4 3.9.94 5.5L10 14.77l-4.94 2.6L6 11.8l-4-3.9 5.5-.82L10 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    @break
                                @default
                                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none"><path d="M10 2a6 6 0 00-6 6v3l-1.5 2.5h15L16 11V8a6 6 0 00-6-6zM8 16a2 2 0 004 0" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
                            @endswitch
                        </div>

                        {{-- Content --}}
                        <div class="notification-item__body">
                            <p class="notification-item__title">{{ $notification->title }}</p>
                            <p class="notification-item__message">{{ $notification->message }}</p>
                            <p class="notification-item__time">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>

                        {{-- Actions --}}
                        <div class="notification-item__actions">
                            @if ($notification->action_url)
                                {{-- POST to mark-read; controller redirects to action_url --}}
                                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                    @csrf
                                    <button type="submit" class="btn btn--primary btn--xs">
                                        {{ $notification->action_text ?? 'View' }}
                                    </button>
                                </form>
                            @elseif (!$notification->is_read)
                                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                    @csrf
                                    <button type="submit" class="btn btn--ghost btn--xs">Mark read</button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('notifications.destroy', $notification) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn--ghost btn--xs" title="Dismiss">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                                        <path d="M1 1l10 10M11 1L1 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="empty" style="padding:3rem">
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" style="margin:0 auto 1rem;display:block;opacity:.3">
                            <path d="M20 4a12 12 0 00-12 12v6l-3 5h30l-3-5v-6A12 12 0 0020 4zM16 32a4 4 0 008 0" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        </svg>
                        <p class="empty__text">You're all caught up! No notifications.</p>
                    </div>
                @endforelse
            </div>
        </div>

        @if ($notifications->hasPages())
            <div class="pagination-wrapper" style="margin-top:1.5rem">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</section>

<style>
.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border);
    transition: background .15s;
}
.notification-item:last-child { border-bottom: none; }
.notification-item:hover { background: var(--bg-subtle, #f8f9fc); }
.notification-item--unread { background: var(--primary-50, #eef2ff); }
.notification-item--unread:hover { background: var(--primary-100, #e0e7ff); }

.notification-item__icon {
    flex-shrink: 0;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 50%;
    background: var(--primary-100, #e0e7ff);
    color: var(--primary, #4f46e5);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: .1rem;
}
.notification-item__body { flex: 1; min-width: 0; }
.notification-item__title {
    font-weight: 600;
    font-size: .9rem;
    color: var(--text-primary, #111);
    margin: 0 0 .2rem;
}
.notification-item__message {
    font-size: .85rem;
    color: var(--text-secondary, #555);
    margin: 0 0 .35rem;
    line-height: 1.4;
}
.notification-item__time {
    font-size: .75rem;
    color: var(--text-muted, #999);
    margin: 0;
}
.notification-item__actions {
    display: flex;
    align-items: center;
    gap: .5rem;
    flex-shrink: 0;
}
</style>
@endsection
