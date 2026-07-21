@extends('layouts.app')

@section('title', 'Progress — ' . $enrollment->course->title)

@section('content')
<section class="dashboard">
    <div class="dashboard__inner">

        {{-- Header --}}
        <header class="dashboard__header">
            <div>
                <a href="{{ route('lms.course', $enrollment) }}" class="btn btn--ghost btn--sm" style="margin-bottom:.5rem">
                    ← Back to Course
                </a>
                <h1 class="dashboard__title">Learning Progress</h1>
                <p class="dashboard__subtitle">
                    <strong>{{ $enrollment->course->title }}</strong>
                    &middot; Mentor: {{ $enrollment->course->relationship->mentor->full_name }}
                </p>
            </div>
            <span class="badge badge--{{ $enrollment->isCompleted() ? 'success' : 'default' }}" style="font-size:1rem;padding:.5rem 1.25rem">
                {{ $enrollment->isCompleted() ? '🎉 Completed' : 'In Progress' }}
            </span>
        </header>

        @include('partials.flash')

        {{-- ── KPI strip ─────────────────────────────────────────────── --}}
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem;margin-bottom:2rem">
            <div class="panel" style="text-align:center">
                <div class="panel__body">
                    <p style="font-size:2.5rem;font-weight:800;color:var(--color-primary)">{{ $enrollment->progress_percentage }}%</p>
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

        {{-- ── Row 1: Doughnut + Bar ───────────────────────────────────── --}}
        <div style="display:grid;grid-template-columns:1fr 2fr;gap:1.5rem;margin-bottom:1.5rem;align-items:start">

            {{-- Doughnut — Overall --}}
            <div class="panel">
                <div class="panel__header"><h2 class="panel__title">Overall Completion</h2></div>
                <div class="panel__body" style="display:flex;flex-direction:column;align-items:center;gap:1rem">
                    <div style="position:relative;width:200px;height:200px">
                        <canvas id="doughnutChart"></canvas>
                        <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none">
                            <span style="font-size:1.75rem;font-weight:800;color:var(--color-primary)">{{ $enrollment->progress_percentage }}%</span>
                            <span style="font-size:.75rem;color:var(--color-text-muted)">done</span>
                        </div>
                    </div>
                    <div style="display:flex;gap:1rem;font-size:.8rem">
                        <span style="display:flex;align-items:center;gap:.35rem">
                            <span style="width:10px;height:10px;border-radius:2px;background:#4f46e5;display:inline-block"></span>Completed
                        </span>
                        <span style="display:flex;align-items:center;gap:.35rem">
                            <span style="width:10px;height:10px;border-radius:2px;background:#e5e7eb;display:inline-block"></span>Remaining
                        </span>
                    </div>
                </div>
            </div>

            {{-- Bar — Per module --}}
            <div class="panel">
                <div class="panel__header"><h2 class="panel__title">Progress by Module</h2></div>
                <div class="panel__body">
                    <canvas id="moduleBarChart" style="max-height:220px"></canvas>
                </div>
            </div>
        </div>

        {{-- ── Row 2: Cumulative line chart ────────────────────────────── --}}
        <div class="panel" style="margin-bottom:1.5rem">
            <div class="panel__header">
                <h2 class="panel__title">Completion Activity — Last 30 Days</h2>
            </div>
            <div class="panel__body">
                <canvas id="activityLineChart" style="max-height:220px"></canvas>
            </div>
        </div>

        {{-- ── Row 3: Module lesson table ──────────────────────────────── --}}
        <div class="panel">
            <div class="panel__header"><h2 class="panel__title">Lesson Breakdown</h2></div>
            <div class="panel__body" style="padding:0">
                @foreach($enrollment->course->modules as $module)
                @php
                    $modDone  = $module->lessons->whereIn('id', $enrollment->lessonProgress->whereNotNull('completed_at')->pluck('lesson_id')->toArray())->count();
                    $modTotal = $module->lessons->count();
                    $modPct   = $modTotal ? round($modDone / $modTotal * 100) : 0;
                @endphp
                <div style="padding:1rem 1.5rem;border-bottom:1px solid var(--color-gray-100)">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                        <span style="font-weight:600;color:var(--color-gray-800)">{{ $module->title }}</span>
                        <span style="font-size:.8rem;color:var(--color-text-muted)">{{ $modDone }}/{{ $modTotal }} lessons</span>
                    </div>
                    @include('partials.lms._progress_bar', ['percent' => $modPct, 'label' => '', 'size' => 'md'])
                </div>
                @endforeach
            </div>
        </div>

    </div>
</section>

{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    const primary   = '#4f46e5';
    const light     = '#818cf8';
    const success   = '#22c55e';
    const grayLight = '#e5e7eb';
    const grayMid   = '#9ca3af';

    // ── Doughnut ─────────────────────────────────────────────────────────
    new Chart(document.getElementById('doughnutChart'), {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Remaining'],
            datasets: [{
                data:            [{{ $completedLessons }}, {{ $remaining }}],
                backgroundColor: [primary, grayLight],
                borderWidth:     0,
                hoverOffset:     6,
            }]
        },
        options: {
            cutout: '72%',
            plugins: { legend: { display: false }, tooltip: { callbacks: {
                label: ctx => ` ${ctx.parsed} lesson${ctx.parsed !== 1 ? 's' : ''}`
            }}}
        }
    });

    // ── Module bar ───────────────────────────────────────────────────────
    new Chart(document.getElementById('moduleBarChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($moduleLabels) !!},
            datasets: [
                {
                    label:           'Completed',
                    data:            {!! json_encode($moduleCompleted) !!},
                    backgroundColor: primary,
                    borderRadius:    6,
                    borderSkipped:   false,
                },
                {
                    label:           'Total',
                    data:            {!! json_encode($moduleTotals) !!},
                    backgroundColor: grayLight,
                    borderRadius:    6,
                    borderSkipped:   false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 12 } } } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f3f4f6' } }
            }
        }
    });

    // ── Cumulative line ───────────────────────────────────────────────────
    new Chart(document.getElementById('activityLineChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($days->values()) !!},
            datasets: [{
                label:           'Lessons Completed (cumulative)',
                data:            {!! json_encode(array_values($cumulative)) !!},
                borderColor:     primary,
                backgroundColor: 'rgba(79,70,229,0.08)',
                fill:            true,
                tension:         0.4,
                pointRadius:     3,
                pointBackgroundColor: primary,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: {
                    maxTicksLimit: 10, font: { size: 11 }
                }},
                y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f3f4f6' } }
            }
        }
    });
</script>
@endsection
