@php
    $attendanceTabs = [
        ['route' => 'attendance.index', 'match' => ['attendance.index', 'attendance.search'], 'label' => ___('settings.Daily Attendance')],
        ['route' => 'attendance.subject-attendance', 'match' => ['attendance.subject-attendance', 'attendance.student.search'], 'label' => ___('settings.Subject Attendance')],
        ['route' => 'attendance.report', 'match' => ['attendance.report', 'attendance.report-search'], 'label' => ___('settings.Daily Attendance Report')],
    ];
    $currentRoute = request()->route() ? request()->route()->getName() : '';
@endphp
<div class="d-flex flex-wrap gap-2 mb-24">
    @foreach ($attendanceTabs as $tab)
        <a href="{{ route($tab['route']) }}"
           class="btn {{ in_array($currentRoute, $tab['match']) ? 'ot-btn-primary' : 'btn-outline-primary' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
