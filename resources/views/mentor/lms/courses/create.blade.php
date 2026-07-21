@extends('layouts.app')

@section('title', 'New Course')

@section('content')
<section class="dashboard">
    <div class="dashboard__inner" style="max-width:680px">
        <header class="dashboard__header">
            <div>
                <h1 class="dashboard__title">New Course</h1>
                <p class="dashboard__subtitle">
                    For <strong>{{ $relationship->freelancer->full_name }}</strong> — add modules and lessons after saving.
                </p>
            </div>
        </header>

        @include('partials.flash')

        <div class="panel">
            <div class="panel__body">
                <form method="POST" action="{{ route('mentor.lms.courses.store', $relationship) }}" class="form">
                    @csrf

                    <div class="form__group">
                        <label class="form__label" for="title">Course Title <span class="text-error">*</span></label>
                        <input type="text" id="title" name="title" class="form__input @error('title') is-error @enderror"
                               value="{{ old('title') }}" required placeholder="e.g. Freelancing Fundamentals">
                        @error('title')
                            <p class="form__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form__group">
                        <label class="form__label" for="description">Description</label>
                        <textarea id="description" name="description" class="form__input @error('description') is-error @enderror"
                                  rows="4" placeholder="What will this course cover?">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="form__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form__actions">
                        <button type="submit" class="btn btn--primary">Create Course →</button>
                        <a href="{{ route('mentor.lms.courses.index', $relationship) }}" class="btn btn--ghost">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
