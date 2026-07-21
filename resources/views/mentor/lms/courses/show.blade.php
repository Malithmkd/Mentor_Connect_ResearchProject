@extends('layouts.app')

@section('title', $course->title . ' — Student Progress')

@section('content')
<section class="dashboard">
    <div class="dashboard__inner">
        <header class="dashboard__header">
            <div>
                <h1 class="dashboard__title">{{ $course->title }}</h1>
                <p class="dashboard__subtitle">
                    Student: <strong>{{ $course->relationship->freelancer->full_name }}</strong>
                    &middot;
                    <span class="badge badge--{{ $course->status->colorClass() }}">{{ $course->status->label() }}</span>
                </p>
            </div>
            <div style="display:flex;gap:.75rem">
                <a href="{{ route('mentor.lms.courses.edit', $course) }}" class="btn btn--secondary btn--sm">✏️ Edit Course</a>
                <a href="{{ route('mentor.lms.courses.index', $course->relationship) }}" class="btn btn--ghost btn--sm">← Courses</a>
            </div>
        </header>

        @include('partials.flash')

        @if($enrollment)

        {{-- ── KPI Strip ──────────────────────────────────────────────── --}}
        @php
            $completedLessons = $enrollment->lessonProgress->whereNotNull('completed_at')->count();
            $totalLessons     = $course->lessons()->count();
            $remaining        = max(0, $totalLessons - $completedLessons);
            $pct              = $enrollment->progress_percentage;
            $completedIds     = $enrollment->lessonProgress->whereNotNull('completed_at')->pluck('lesson_id')->toArray();

            // Module chart data
            $moduleLabels    = [];
            $moduleTotals    = [];
            $moduleCompleted = [];
            foreach ($course->modules as $mod) {
                $moduleLabels[]    = $mod->title;
                $moduleTotals[]    = $mod->lessons->count();
                $moduleCompleted[] = $mod->lessons->whereIn('id', $completedIds)->count();
            }

            // 30-day cumulative activity
            $days       = collect(range(29, 0))->map(fn($d) => now()->subDays($d)->format('M d'));
            $cumulative = [];
            $running    = 0;
            foreach (range(29, 0) as $d) {
                $date     = now()->subDays($d)->toDateString();
                $count    = $enrollment->lessonProgress
                    ->whereNotNull('completed_at')
                    ->filter(fn($p) => $p->completed_at->toDateString() === $date)
                    ->count();
                $running     += $count;
                $cumulative[] = $running;
            }
        @endphp

        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem;margin-bottom:2rem">
            <div class="panel" style="text-align:center">
                <div class="panel__body">
                    <p style="font-size:2.5rem;font-weight:800;color:var(--color-primary)">{{ $pct }}%</p>
                    <p style="color:var(--color-text-muted);font-size:.875rem;margin-top:.25rem">Overall Progress</p>
                </div>
            </div>
            <div class="panel" style="text-align:center">
                <div class="panel__body">
                    <p style="font-size:2.5rem;font-weight:800;color:#22c55e">{{ $completedLessons }}</p>
                    <p style="color:var(--color-text-muted);font-size:.875rem;margin-top:.25rem">Lessons Completed</p>
                </div>
            </div>
            <div class="panel" style="text-align:center">
                <div class="panel__body">
                    <p style="font-size:2.5rem;font-weight:800;color:var(--color-warning)">{{ $remaining }}</p>
                    <p style="color:var(--color-text-muted);font-size:.875rem;margin-top:.25rem">Lessons Remaining</p>
                </div>
            </div>
        </div>

        {{-- ── Row 1: Doughnut + Bar ──────────────────────────────────── --}}
        <div style="display:grid;grid-template-columns:1fr 2fr;gap:1.5rem;margin-bottom:1.5rem;align-items:start">
            <div class="panel">
                <div class="panel__header"><h2 class="panel__title">Overall Completion</h2></div>
                <div class="panel__body" style="display:flex;flex-direction:column;align-items:center;gap:1rem">
                    <div style="position:relative;width:180px;height:180px">
                        <canvas id="doughnutChart"></canvas>
                        <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none">
                            <span style="font-size:1.6rem;font-weight:800;color:var(--color-primary)">{{ $pct }}%</span>
                            <span style="font-size:.7rem;color:var(--color-text-muted)">done</span>
                        </div>
                    </div>
                    <div style="display:flex;gap:1rem;font-size:.8rem">
                        <span style="display:flex;align-items:center;gap:.35rem">
                            <span style="width:10px;height:10px;border-radius:2px;background:#4f46e5;display:inline-block"></span>Done
                        </span>
                        <span style="display:flex;align-items:center;gap:.35rem">
                            <span style="width:10px;height:10px;border-radius:2px;background:#e5e7eb;display:inline-block"></span>Left
                        </span>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel__header"><h2 class="panel__title">Progress by Module</h2></div>
                <div class="panel__body">
                    <canvas id="moduleBarChart" style="max-height:220px"></canvas>
                </div>
            </div>
        </div>

        {{-- ── Row 2: Activity line ───────────────────────────────────── --}}
        <div class="panel" style="margin-bottom:1.5rem">
            <div class="panel__header">
                <h2 class="panel__title">Student Activity — Last 30 Days (Cumulative)</h2>
            </div>
            <div class="panel__body">
                <canvas id="activityLineChart" style="max-height:220px"></canvas>
            </div>
        </div>

        {{-- ── Row 3: Lesson status table ───────────────────────────── --}}
        <div class="panel">
            <div class="panel__header"><h2 class="panel__title">Lesson Breakdown</h2></div>
            <div class="panel__body" style="padding:0">
                @foreach($course->modules as $module)
                @php
                    $modDone  = $module->lessons->whereIn('id', $completedIds)->count();
                    $modTotal = $module->lessons->count();
                    $modPct   = $modTotal ? round($modDone / $modTotal * 100) : 0;
                @endphp
                <div style="padding:1rem 1.5rem;border-bottom:1px solid var(--color-gray-100)">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                        <span style="font-weight:600;color:var(--color-gray-800)">{{ $module->title }}</span>
                        <span style="font-size:.8rem;color:var(--color-text-muted)">{{ $modDone }}/{{ $modTotal }} lessons</span>
                    </div>
                    @include('partials.lms._progress_bar', ['percent' => $modPct, 'label' => '', 'size' => 'md'])
                    {{-- Per-lesson status --}}
                    <div style="margin-top:.75rem;display:flex;flex-direction:column;gap:.25rem">
                        @foreach($module->lessons as $lesson)
                        @php $done = in_array($lesson->id, $completedIds); @endphp
                        <div style="display:flex;align-items:center;gap:.5rem;font-size:.82rem;color:{{ $done ? '#16a34a' : 'var(--color-text-muted)' }}">
                            <span>{{ $done ? '✓' : '○' }}</span>
                            <span>{{ $lesson->title }}</span>
                            @if($done)
                            @php
                                $prog = $enrollment->lessonProgress->firstWhere('lesson_id', $lesson->id);
                            @endphp
                            @if($prog && $prog->completed_at)
                                <span style="margin-left:auto;font-size:.75rem;color:var(--color-gray-400)">
                                    {{ $prog->completed_at->format('M d, g:ia') }}
                                </span>
                            @endif
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Chart.js --}}
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
        <script>
            const primary   = '#4f46e5';
            const grayLight = '#e5e7eb';

            new Chart(document.getElementById('doughnutChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'Remaining'],
                    datasets: [{ data: [{{ $completedLessons }}, {{ $remaining }}], backgroundColor: [primary, grayLight], borderWidth: 0, hoverOffset: 6 }]
                },
                options: { cutout: '72%', plugins: { legend: { display: false } } }
            });

            new Chart(document.getElementById('moduleBarChart'), {
                type: 'bar',
                data: {
                    labels: {!! json_encode($moduleLabels) !!},
                    datasets: [
                        { label: 'Completed', data: {!! json_encode($moduleCompleted) !!}, backgroundColor: primary,   borderRadius: 6, borderSkipped: false },
                        { label: 'Total',     data: {!! json_encode($moduleTotals) !!},    backgroundColor: grayLight, borderRadius: 6, borderSkipped: false }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: true,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 12 } } } },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                        y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f3f4f6' } }
                    }
                }
            });

            new Chart(document.getElementById('activityLineChart'), {
                type: 'line',
                data: {
                    labels: {!! json_encode($days->values()) !!},
                    datasets: [{
                        label: 'Lessons (cumulative)',
                        data:  {!! json_encode(array_values($cumulative)) !!},
                        borderColor: primary, backgroundColor: 'rgba(79,70,229,0.08)',
                        fill: true, tension: 0.4, pointRadius: 3, pointBackgroundColor: primary,
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { maxTicksLimit: 10, font: { size: 11 } } },
                        y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f3f4f6' } }
                    }
                }
            });
        </script>

        @else
        {{-- Not enrolled yet --}}
        <div class="panel">
            <div class="panel__body">
                <div class="empty">
                    <p class="empty__text">The freelancer hasn't been enrolled yet.</p>
                    <p style="color:var(--color-text-muted);font-size:.875rem;margin-top:.5rem">
                        Publish the course to automatically enroll them.
                    </p>
                    @if($course->isDraft())
                        <a href="{{ route('mentor.lms.courses.edit', $course) }}" class="btn btn--primary btn--sm" style="margin-top:1rem">
                            Go to Course Builder
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

    </div>
</section>
@endsection
