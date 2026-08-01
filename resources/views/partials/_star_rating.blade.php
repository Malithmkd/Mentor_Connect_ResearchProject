{{--
    Star Rating Component
    Usage: @include('partials._star_rating', ['inputName' => 'rating', 'currentRating' => 0])
--}}
@php $inputName = $inputName ?? 'rating'; $currentRating = $currentRating ?? 0; @endphp

<div class="star-rating" id="starRatingWidget">
    <input type="hidden" name="{{ $inputName }}" id="starRatingValue" value="{{ $currentRating > 0 ? $currentRating : '' }}" required>
    <div class="star-rating__stars" id="starRatingStars" role="radiogroup" aria-label="Rating">
        @for ($s = 1; $s <= 5; $s++)
        <button type="button"
                class="star-rating__star {{ $s <= $currentRating ? 'is-active' : '' }}"
                data-value="{{ $s }}"
                onclick="setStarRating({{ $s }})"
                onmouseenter="hoverStars({{ $s }})"
                onmouseleave="resetStars()"
                aria-label="{{ $s }} star{{ $s > 1 ? 's' : '' }}"
                role="radio"
                aria-checked="{{ $s == $currentRating ? 'true' : 'false' }}">
            <svg viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                <path d="M8 1l2.12 4.3 4.74.7-3.43 3.34.81 4.72L8 11.77l-4.24 2.29.81-4.72L1.14 6l4.74-.7z"/>
            </svg>
        </button>
        @endfor
    </div>
    <span class="star-rating__label" id="starRatingLabel">
        @if($currentRating > 0)
            {{ $currentRating }}/5 stars
        @else
            Click to rate
        @endif
    </span>
</div>

@once
@push('styles')
<style>
/* ── Star Rating Component ── */
.star-rating {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin: .25rem 0;
}
.star-rating__stars {
    display: flex;
    gap: .2rem;
}
.star-rating__star {
    background: none;
    border: none;
    cursor: pointer;
    padding: 2px;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d1d5db;
    transition: color .1s, transform .1s;
    border-radius: 4px;
}
.star-rating__star:hover,
.star-rating__star.is-hover,
.star-rating__star.is-active {
    color: #f59e0b;
}
.star-rating__star.is-active {
    transform: scale(1.1);
}
.star-rating__star svg {
    width: 28px;
    height: 28px;
}
.star-rating__label {
    font-size: .875rem;
    font-weight: 600;
    color: #6b7280;
    min-width: 80px;
    transition: color .15s;
}
.star-rating__label.is-rated {
    color: #f59e0b;
}

[data-theme="dark"] .star-rating__star { color: #4b5563; }
[data-theme="dark"] .star-rating__label { color: #9ca3af; }
[data-theme="dark"] .star-rating__star.is-active,
[data-theme="dark"] .star-rating__star.is-hover { color: #fbbf24; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var _ratingLabels = ['', 'Terrible', 'Poor', 'Average', 'Good', 'Excellent'];
    var _selected = parseInt(document.getElementById('starRatingValue').value) || 0;

    window.setStarRating = function (val) {
        _selected = val;
        document.getElementById('starRatingValue').value = val;
        updateStars(val);
        var label = document.getElementById('starRatingLabel');
        label.textContent = val + '/5 — ' + _ratingLabels[val];
        label.classList.add('is-rated');
        // update aria
        document.querySelectorAll('.star-rating__star').forEach(function(btn) {
            btn.setAttribute('aria-checked', parseInt(btn.dataset.value) === val ? 'true' : 'false');
        });
    };

    window.hoverStars = function (val) {
        document.querySelectorAll('.star-rating__star').forEach(function(btn) {
            btn.classList.toggle('is-hover', parseInt(btn.dataset.value) <= val);
        });
        var label = document.getElementById('starRatingLabel');
        label.textContent = _ratingLabels[val] || (val + '/5 stars');
    };

    window.resetStars = function () {
        document.querySelectorAll('.star-rating__star').forEach(function(btn) {
            btn.classList.remove('is-hover');
        });
        var label = document.getElementById('starRatingLabel');
        label.textContent = _selected ? (_selected + '/5 — ' + _ratingLabels[_selected]) : 'Click to rate';
    };

    function updateStars(val) {
        document.querySelectorAll('.star-rating__star').forEach(function(btn) {
            btn.classList.toggle('is-active', parseInt(btn.dataset.value) <= val);
        });
    }

    if (_selected > 0) updateStars(_selected);
})();
</script>
@endpush
@endonce
