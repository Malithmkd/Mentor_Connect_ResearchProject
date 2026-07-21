@if (session('success'))
    <div class="flash flash--success" role="alert">
        <svg class="flash__icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M10 18a8 8 0 100-16 8 8 0 000 16z" stroke="currentColor" stroke-width="1.5"/>
            <path d="M7 10l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="flash__message">{{ session('success') }}</span>
        <button class="flash__close" aria-label="Dismiss" onclick="this.parentElement.remove()">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </button>
    </div>
@endif

@if (session('error'))
    <div class="flash flash--error" role="alert">
        <svg class="flash__icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
            <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/>
            <path d="M7 7l6 6M13 7l-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <span class="flash__message">{{ session('error') }}</span>
        <button class="flash__close" aria-label="Dismiss" onclick="this.parentElement.remove()">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </button>
    </div>
@endif

@if (session('status'))
    <div class="flash flash--info" role="alert">
        <svg class="flash__icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
            <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/>
            <path d="M10 6v4M10 14h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <span class="flash__message">{{ session('status') }}</span>
        <button class="flash__close" aria-label="Dismiss" onclick="this.parentElement.remove()">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </button>
    </div>
@endif
