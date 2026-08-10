@extends('layouts.app')

@section('title', 'Edit — ' . $course->title)

@section('content')
<section class="dashboard">
    <div class="dashboard__inner">
        {{-- ── Course Header ── --}}
        <header class="dashboard__header">
            <div>
                <h1 class="dashboard__title">Course Builder</h1>
                <p class="dashboard__subtitle">
                    <span class="badge badge--{{ $course->status->colorClass() }}">{{ $course->status->label() }}</span>
                    &nbsp;for <strong>{{ $course->relationship->freelancer->full_name }}</strong>
                </p>
            </div>
            <div style="display:flex;gap:.75rem;align-items:center">
                @if($course->isDraft())
                    <form method="POST" action="{{ route('mentor.lms.courses.publish', $course) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn--success btn--sm">Publish &amp; Enroll Freelancer</button>
                    </form>
                @endif
                <a href="{{ route('mentor.lms.courses.index', $course->relationship) }}" class="btn btn--ghost btn--sm">
                    ← Courses
                </a>
            </div>
        </header>

        @include('partials.flash')

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;align-items:start">

            {{-- ── Left: Edit Course Metadata ── --}}
            <div class="panel">
                <div class="panel__header"><h2 class="panel__title">Course Details</h2></div>
                <div class="panel__body">
                    <form method="POST" action="{{ route('mentor.lms.courses.update', $course) }}" class="form">
                        @csrf @method('PATCH')
                        <div class="form__group">
                            <label class="form__label" for="title">Title</label>
                            <input type="text" id="title" name="title" class="form__input"
                                   value="{{ old('title', $course->title) }}" required>
                            @error('title')<p class="form__error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form__group">
                            <label class="form__label" for="description">Description</label>
                            <textarea id="description" name="description" class="form__input" rows="3">{{ old('description', $course->description) }}</textarea>
                        </div>
                        <button type="submit" class="btn btn--secondary btn--sm">Save Details</button>
                    </form>
                </div>
            </div>

            {{-- ── Right: Add Module ── --}}
            <div class="panel">
                <div class="panel__header"><h2 class="panel__title">Add Module</h2></div>
                <div class="panel__body">
                    <form method="POST" action="{{ route('mentor.lms.modules.store', $course) }}" class="form">
                        @csrf
                        <div class="form__group">
                            <label class="form__label" for="mod_title">Module Title</label>
                            <input type="text" id="mod_title" name="title" class="form__input"
                                   placeholder="e.g. Getting Started" required>
                        </div>
                        <div class="form__group">
                            <label class="form__label" for="mod_desc">Description (optional)</label>
                            <textarea id="mod_desc" name="description" class="form__input" rows="2"
                                      placeholder="What will this module cover?"></textarea>
                        </div>
                        <button type="submit" class="btn btn--primary btn--sm">+ Add Module</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ── Modules & Lessons ── --}}
        <div style="margin-top:2rem">
            @forelse($course->modules as $module)
            <div class="panel lms-module" style="margin-bottom:1.5rem">
                <div class="panel__header lms-module__header">
                    <div>
                        <span class="lms-module__order">Module {{ $loop->iteration }}</span>
                        <h3 class="panel__title" style="display:inline;margin-left:.5rem">{{ $module->title }}</h3>
                    </div>
                    <div style="display:flex;gap:.5rem">
                        {{-- Delete module --}}
                        <form method="POST" action="{{ route('mentor.lms.modules.destroy', $module) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn--error btn--xs"
                                onclick="return confirm('Delete this module and all its lessons?')">Delete Module</button>
                        </form>
                    </div>
                </div>

                <div class="panel__body">
                    {{-- Lessons list --}}
                    @forelse($module->lessons as $lesson)
                    <div class="lms-lesson-row">
                        <span class="lms-lesson-row__num">{{ $loop->iteration }}</span>
                        <div class="lms-lesson-row__info">
                            <strong>{{ $lesson->title }}</strong>
                            @if($lesson->video_url)
                                <span class="badge badge--default" style="margin-left:.5rem;font-size:.7rem">🎬 Video</span>
                            @endif
                            @if($lesson->hasPdf())
                                <span class="badge badge--info" style="margin-left:.5rem;font-size:.7rem">📄 PDF</span>
                            @endif
                        </div>
                        <div class="lms-lesson-row__actions">
                            {{-- Edit lesson toggle --}}
                            <button type="button" class="btn btn--ghost btn--xs"
                                onclick="document.getElementById('lesson-edit-{{ $lesson->id }}').classList.toggle('hidden')">
                                Edit
                            </button>
                            <form method="POST" action="{{ route('mentor.lms.lessons.destroy', $lesson) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn--error btn--xs"
                                    onclick="return confirm('Delete this lesson?')">Delete</button>
                            </form>
                        </div>
                    </div>

                    {{-- ── Edit lesson inline form ── --}}
                    <div id="lesson-edit-{{ $lesson->id }}" class="hidden lms-lesson-edit-form">
                        <form method="POST" action="{{ route('mentor.lms.lessons.update', $lesson) }}"
                              class="form" enctype="multipart/form-data">
                            @csrf @method('PATCH')
                            <div class="form__group">
                                <label class="form__label">Lesson Title</label>
                                <input type="text" name="title" class="form__input" value="{{ $lesson->title }}" required>
                            </div>
                            <div class="form__group">
                                <label class="form__label">Content</label>
                                <textarea name="content" class="form__input" rows="6"
                                    placeholder="Lesson content (markdown or plain text)">{{ $lesson->content }}</textarea>
                            </div>
                            <div class="form__group">
                                <label class="form__label">Video URL (optional)</label>
                                <input type="url" name="video_url" class="form__input"
                                       value="{{ $lesson->video_url }}" placeholder="https://www.youtube.com/watch?v=...">
                                <p style="font-size:.75rem;color:var(--color-text-muted);margin-top:.25rem">
                                    Paste any YouTube or Vimeo link — embed format is handled automatically.
                                </p>
                            </div>

                            {{-- ── PDF Notes ── --}}
                            <div class="form__group">
                                <label class="form__label">PDF Notes (optional, max 10 MB)</label>

                                @if($lesson->hasPdf())
                                {{-- Show current PDF --}}
                                <div class="lms-pdf-current">
                                    <span>📄</span>
                                    <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.85rem">
                                        {{ $lesson->pdfName() }}
                                    </span>
                                    <a href="{{ $lesson->pdfUrl() }}" target="_blank" rel="noopener"
                                       class="btn btn--ghost btn--xs">View</a>
                                </div>
                                <div style="margin-top:.5rem;display:flex;align-items:center;gap:.75rem">
                                    <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;cursor:pointer">
                                        <input type="checkbox" name="remove_pdf" value="1">
                                        Remove current PDF
                                    </label>
                                    <span style="color:var(--color-gray-400);font-size:.75rem">or</span>
                                    <span style="font-size:.82rem;color:var(--color-gray-500)">upload a new one below to replace it</span>
                                </div>
                                @endif

                                <input type="file" name="pdf" class="form__input lms-pdf-input"
                                       accept=".pdf" style="margin-top:.5rem">
                                @error('pdf')
                                    <p class="form__error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div style="display:flex;gap:.5rem">
                                <button type="submit" class="btn btn--primary btn--sm">Save Lesson</button>
                                <button type="button" class="btn btn--ghost btn--sm"
                                    onclick="document.getElementById('lesson-edit-{{ $lesson->id }}').classList.add('hidden')">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                    @empty
                        <p class="empty__text" style="margin-bottom:1rem">No lessons yet. Add one below.</p>
                    @endforelse

                    {{-- ── Add Lesson to this module ── --}}
                    <div class="lms-add-lesson">
                        <button type="button" class="btn btn--ghost btn--sm"
                            onclick="document.getElementById('add-lesson-{{ $module->id }}').classList.toggle('hidden')">
                            + Add Lesson
                        </button>
                        <div id="add-lesson-{{ $module->id }}" class="hidden lms-lesson-edit-form" style="margin-top:1rem">
                            <form method="POST" action="{{ route('mentor.lms.lessons.store', $module) }}"
                                  class="form" enctype="multipart/form-data">
                                @csrf
                                <div class="form__group">
                                    <label class="form__label">Lesson Title</label>
                                    <input type="text" name="title" class="form__input" required placeholder="e.g. Introduction to Upwork">
                                </div>
                                <div class="form__group">
                                    <label class="form__label">Content</label>
                                    <textarea name="content" class="form__input" rows="6"
                                        placeholder="Lesson content (markdown or plain text)"></textarea>
                                </div>
                                <div class="form__group">
                                    <label class="form__label">Video URL (optional)</label>
                                    <input type="url" name="video_url" class="form__input"
                                           placeholder="https://www.youtube.com/watch?v=...">
                                    <p style="font-size:.75rem;color:var(--color-text-muted);margin-top:.25rem">
                                        Paste any YouTube or Vimeo link — embed format is handled automatically.
                                    </p>
                                </div>

                                {{-- ── PDF Notes ── --}}
                                <div class="form__group">
                                    <label class="form__label">PDF Notes (optional, max 10 MB)</label>
                                    <input type="file" name="pdf" class="form__input lms-pdf-input" accept=".pdf">
                                    <p style="font-size:.75rem;color:var(--color-text-muted);margin-top:.25rem">
                                        Attach a PDF handout or study notes for this lesson (PDF only, max 10 MB).
                                    </p>
                                    @error('pdf')
                                        <p class="form__error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn--primary btn--sm">Add Lesson</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @empty
                <div class="empty">
                    <p class="empty__text">No modules yet. Add your first module using the form above.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
/* ── PDF current file display ── */
.lms-pdf-current {
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: .6rem .875rem;
    background: var(--color-primary-50);
    border: 1px solid var(--color-primary-light);
    border-radius: var(--radius);
    font-size: .85rem;
    color: var(--color-gray-700);
}

/* ── PDF file input styling ── */
.lms-pdf-input {
    padding: .4rem;
    font-size: .85rem;
    cursor: pointer;
}
.lms-pdf-input::file-selector-button {
    padding: .3rem .75rem;
    background: var(--color-primary);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    font-size: .82rem;
    margin-right: .75rem;
    transition: background .15s;
}
.lms-pdf-input::file-selector-button:hover {
    background: var(--color-primary-dark);
}

[data-theme="dark"] .lms-pdf-current {
    background: #1e1b4b;
    border-color: #4f46e5;
    color: #c7d2fe;
}
</style>
@endpush
