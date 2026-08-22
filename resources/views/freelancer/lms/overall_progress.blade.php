@extends('layouts.app')

@section('title', 'All-Time Progress')

@section('content')
<section class="dashboard">
    <div class="dashboard__inner">

        {{-- ── Header ─────────────────────────────────────────────────────── --}}
        <header class="dashboard__header" style="margin-bottom:2rem">
            <div>
                <a href="{{ route('lms.index') }}" class="btn btn--ghost btn--sm" style="margin-bottom:.5rem">
                    ← My Learning
                </a>
                <h1 class="dashboard__title">All-Time Progress</h1>
                <p class="dashboard__subtitle">A full picture of your learning journey across all enrolled courses.</p>
            </div>
            <span class="badge badge--{{ $overallProgressPct >= 100 ? 'success' : 'default' }}"
                  style="font-size:1rem;padding:.5rem 1.25rem">
                {{ $overallProgressPct >= 100 ? '🎉 All Complete!' : $overallProgressPct . '% Overall' }}
            </span>
        </header>

        @include('partials.flash')

        {{-- ── KPI Strip ───────────────────────────────────────────────────── --}}
        <div class="oap-kpi-strip">
            <div class="oap-kpi-card">
                <span class="oap-kpi-card__value" style="color:var(--color-primary)">{{ $overallProgressPct }}%</span>
                <span class="oap-kpi-card__label">Overall Progress</span>
            </div>
            <div class="oap-kpi-card">
                <span class="oap-kpi-card__value" style="color:#22c55e">{{ $overallCompletedLessons }}</span>
                <span class="oap-kpi-card__label">Lessons Completed</span>
            </div>
            <div class="oap-kpi-card">
                <span class="oap-kpi-card__value" style="color:var(--color-warning)">{{ $remainingLessons }}</span>
                <span class="oap-kpi-card__label">Lessons Remaining</span>
            </div>
            <div class="oap-kpi-card">
                <span class="oap-kpi-card__value" style="color:#8b5cf6">{{ $completedCourses }}</span>
                <span class="oap-kpi-card__label">Courses Completed</span>
            </div>
            <div class="oap-kpi-card">
                <span class="oap-kpi-card__value" style="color:#0ea5e9">{{ $enrollments->count() }}</span>
                <span class="oap-kpi-card__label">Total Enrollments</span>
            </div>
        </div>

        {{-- ── Row 1: Doughnut + 30-Day Line ───────────────────────────────── --}}
        <div class="oap-grid-2" style="margin-bottom:1.5rem">

            <div class="panel">
                <div class="panel__header"><h2 class="panel__title">Overall Completion</h2></div>
                <div class="panel__body" style="display:flex;flex-direction:column;align-items:center;gap:1rem">
                    <div style="position:relative;width:200px;height:200px">
                        <canvas id="doughnutChart"></canvas>
                        <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none">
                            <span style="font-size:1.75rem;font-weight:800;color:var(--color-primary)">{{ $overallProgressPct }}%</span>
                            <span style="font-size:.75rem;color:var(--color-text-muted)">done</span>
                        </div>
                    </div>
                    <div style="display:flex;gap:1.25rem;font-size:.8rem">
                        <span style="display:flex;align-items:center;gap:.35rem">
                            <span style="width:10px;height:10px;border-radius:2px;background:#4f46e5;display:inline-block"></span>Completed
                        </span>
                        <span style="display:flex;align-items:center;gap:.35rem">
                            <span style="width:10px;height:10px;border-radius:2px;background:#e5e7eb;display:inline-block"></span>Remaining
                        </span>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel__header"><h2 class="panel__title">Completion Activity — Last 30 Days</h2></div>
                <div class="panel__body">
                    <canvas id="activityLineChart" style="max-height:230px"></canvas>
                </div>
            </div>

        </div>

        {{-- ── Per-course bar chart ─────────────────────────────────────────── --}}
        @if($enrollments->count() > 0)
        <div class="panel" style="margin-bottom:1.5rem">
            <div class="panel__header"><h2 class="panel__title">Progress by Course</h2></div>
            <div class="panel__body">
                <canvas id="courseBarChart" style="max-height:280px"></canvas>
            </div>
        </div>
        @endif

        {{-- ── Mentor Breakdown Table ───────────────────────────────────────── --}}
        @if($mentorSummaries->count() > 0)
        <div class="panel" style="margin-bottom:1.5rem">
            <div class="panel__header"><h2 class="panel__title">Progress by Mentor</h2></div>
            <div class="panel__body" style="padding:0">
                <table class="oap-table">
                    <thead>
                        <tr>
                            <th>Mentor</th>
                            <th style="text-align:center">Courses</th>
                            <th style="text-align:center">Done</th>
                            <th style="text-align:center">Total</th>
                            <th style="min-width:160px">Progress</th>
                            <th style="text-align:center">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mentorSummaries as $summary)
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:.65rem">
                                    @if($summary['mentor'])
                                        <img src="{{ $summary['mentor']->avatar_url }}"
                                             alt="{{ $summary['mentor']->full_name }}"
                                             style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0">
                                        <span style="font-weight:600">{{ $summary['mentor']->full_name }}</span>
                                    @else
                                        <span style="color:var(--color-text-muted)">Unknown</span>
                                    @endif
                                </div>
                            </td>
                            <td style="text-align:center">{{ $summary['count'] }}</td>
                            <td style="text-align:center;color:#22c55e;font-weight:700">{{ $summary['done'] }}</td>
                            <td style="text-align:center;color:var(--color-text-muted)">{{ $summary['total'] }}</td>
                            <td>
                                <div style="background:var(--color-gray-100);border-radius:999px;height:8px;overflow:hidden">
                                    <div style="height:100%;width:{{ $summary['pct'] }}%;background:var(--color-primary);border-radius:999px;transition:width .5s ease"></div>
                                </div>
                            </td>
                            <td style="text-align:center;font-weight:700;color:var(--color-primary)">{{ $summary['pct'] }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- ── Per-Enrollment Cards ─────────────────────────────────────────── --}}
        <div class="panel" style="margin-bottom:1.5rem">
            <div class="panel__header" style="display:flex;justify-content:space-between;align-items:center">
                <h2 class="panel__title">All Enrolled Courses</h2>
                <span style="font-size:.8rem;color:var(--color-text-muted)">{{ $enrollments->count() }} total</span>
            </div>
            <div class="panel__body" style="padding:0">
                @forelse($enrollments as $enrollment)
                @php
                    $pct    = $enrollment->progress_percentage;
                    $isDone = $enrollment->isCompleted();
                    $mentor = optional($enrollment->course->relationship)->mentor;
                @endphp
                <div class="oap-enroll-row" style="padding:1rem 1.5rem;border-bottom:1px solid var(--color-gray-100);display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
                    <div style="flex:1;min-width:200px">
                        <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.2rem;flex-wrap:wrap">
                            <span style="font-weight:600;color:var(--color-gray-900)">{{ $enrollment->course->title }}</span>
                            <span class="badge badge--{{ $isDone ? 'success' : 'default' }}" style="font-size:.7rem">
                                {{ $isDone ? '🎉 Complete' : 'In Progress' }}
                            </span>
                        </div>
                        @if($mentor)
                            <span style="font-size:.8rem;color:var(--color-text-muted)">{{ $mentor->full_name }}</span>
                        @endif
                    </div>
                    <div style="flex:1;min-width:160px;max-width:320px">
                        <div style="background:var(--color-gray-100);border-radius:999px;height:8px;overflow:hidden;margin-bottom:.3rem">
                            <div style="height:100%;width:{{ $pct }}%;background:{{ $isDone ? '#22c55e' : 'var(--color-primary)' }};border-radius:999px;transition:width .5s ease"></div>
                        </div>
                        <span style="font-size:.75rem;color:var(--color-text-muted)">{{ $pct }}% complete</span>
                    </div>
                    <div style="display:flex;gap:.5rem;flex-shrink:0">
                        <a href="{{ route('lms.course', $enrollment) }}" class="btn btn--ghost btn--sm">View Course</a>
                        <a href="{{ route('lms.progress', $enrollment) }}" class="btn btn--secondary btn--sm">📊 Details</a>
                    </div>
                </div>
                @empty
                <div style="padding:2rem;text-align:center;color:var(--color-text-muted)">
                    No enrollments yet.
                </div>
                @endforelse
            </div>
        </div>

    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    const primary   = '#4f46e5';
    const grayLight = '#e5e7eb';

    new Chart(document.getElementById('doughnutChart'), {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Remaining'],
            datasets: [{
                data:            [{{ $overallCompletedLessons }}, {{ $remainingLessons }}],
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

    new Chart(document.getElementById('activityLineChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($days->values()) !!},
            datasets: [{
                label:                'Lessons Completed (cumulative)',
                data:                 {!! json_encode(array_values($cumulative)) !!},
                borderColor:          primary,
                backgroundColor:      'rgba(79,70,229,0.08)',
                fill:                 true,
                tension:              0.4,
                pointRadius:          3,
                pointBackgroundColor: primary,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 10, font: { size: 11 } } },
                y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f3f4f6' } }
            }
        }
    });

    @if($enrollments->count() > 0)
    new Chart(document.getElementById('courseBarChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($courseLabels) !!},
            datasets: [
                {
                    label:           'Completed',
                    data:            {!! json_encode($courseCompleted) !!},
                    backgroundColor: primary,
                    borderRadius:    6,
                    borderSkipped:   false,
                },
                {
                    label:           'Total',
                    data:            {!! json_encode($courseTotals) !!},
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
    @endif
</script>

@push('styles')
<style>
.oap-kpi-strip {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.oap-kpi-card {
    background: var(--color-white);
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    padding: 1.25rem 1rem;
    text-align: center;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
    display: flex;
    flex-direction: column;
    gap: .3rem;
}
.oap-kpi-card__value {
    font-size: 2.25rem;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -0.03em;
}
.oap-kpi-card__label {
    font-size: .78rem;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: .05em;
    font-weight: 600;
}
.oap-grid-2 {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 1.5rem;
    align-items: start;
}
.oap-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .875rem;
}
.oap-table thead tr { background: var(--color-gray-50); }
.oap-table th, .oap-table td {
    padding: .75rem 1.25rem;
    text-align: left;
    border-bottom: 1px solid var(--color-gray-100);
}
.oap-table th {
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--color-text-muted);
    font-weight: 700;
}
.oap-table tbody tr:hover { background: var(--color-gray-50); transition: background .15s; }
.oap-table tbody tr:last-child td { border-bottom: none; }
.oap-enroll-row { transition: background .15s; }
.oap-enroll-row:hover { background: var(--color-gray-50); }
.oap-enroll-row:last-child { border-bottom: none !important; }

@media (max-width: 1024px) { .oap-kpi-strip { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px)  { .oap-kpi-strip { grid-template-columns: repeat(2, 1fr); } .oap-grid-2 { grid-template-columns: 1fr; } }
@media (max-width: 480px)  { .oap-kpi-strip { grid-template-columns: 1fr 1fr; } }

[data-theme="dark"] .oap-kpi-card { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .oap-table thead tr { background: #0f172a; }
[data-theme="dark"] .oap-table th, [data-theme="dark"] .oap-table td { border-color: #334155; }
[data-theme="dark"] .oap-table tbody tr:hover, [data-theme="dark"] .oap-enroll-row:hover { background: #1e293b; }
</style>
@endpush
@endsection
