{{--
    Student-facing Progress & Report - same visual language as the admin
    version (resources/views/class-content/_progress-report-block.blade.php,
    reuses its .cc-pr-* class names) but shows only this one student's
    own scores, never a classmate's or a class average.

    Expects: $data['selectedProgram'] (Program, with Approved
    programClasses eager loaded) and $data['progress'] (from
    ProgramClassMarkRepository::progressForStudent()).
--}}
@php
    $spr_selected = $data['selectedProgram'] ?? null;
    $spr_progress = $data['progress'] ?? null;
@endphp

<div class="cc-pr">
    @if (!$spr_selected)
        <div class="cc-cd-card">
            <p class="text-secondary mb-0" style="font-size: 13px;">Pick a course above (under Course Details) to see your progress &amp; report.</p>
        </div>
    @else
        <div class="cc-pr-cards">
            <div class="cc-pr-stat">
                <p class="cc-pr-stat-label">Lessons</p>
                <p class="cc-pr-stat-value">{{ $spr_selected->programClasses->count() }}</p>
            </div>
            <div class="cc-pr-stat">
                <p class="cc-pr-stat-label">Items Graded</p>
                <p class="cc-pr-stat-value">{{ $spr_progress['graded'] }}/{{ $spr_progress['totalSlots'] }}</p>
            </div>
            <div class="cc-pr-stat">
                <p class="cc-pr-stat-label">Your Average</p>
                <p class="cc-pr-stat-value">{{ $spr_progress['pct'] !== null ? $spr_progress['pct'] . '%' : '—' }}</p>
            </div>
        </div>

        <div class="cc-cd-card">
            <p class="cc-cd-label">Lesson-wise progress</p>
            @if ($spr_selected->programClasses->isEmpty())
                <p class="text-secondary mb-0" style="font-size: 13px;">No lessons published for this course yet.</p>
            @else
                <div class="table-responsive">
                    <table class="cc-pr-table">
                        <thead>
                            <tr>
                                <th>Lesson</th>
                                <th>Gradable items</th>
                                <th>Your score</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($spr_selected->programClasses as $pc)
                                @php $spr_row = $spr_progress['lessonRows'][$pc->id]['items'] ?? null; @endphp
                                @if ($spr_row)
                                    <tr>
                                        <td>{{ $pc->title }}</td>
                                        <td>{{ implode(', ', array_map(fn ($i) => $i['title'], $spr_row)) }}</td>
                                        <td>
                                            @foreach ($spr_row as $spr_item)
                                                <div>
                                                    {{ $spr_item['title'] }}:
                                                    @if ($spr_item['marks'] !== null)
                                                        {{ $spr_item['marks'] }}{{ $spr_item['max_marks'] ? ' / ' . $spr_item['max_marks'] : '' }}
                                                    @else
                                                        <span class="text-secondary">—</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>

@once
    @push('css')
    <style>
        .cc-pr-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .cc-pr-stat {
            background: #ffffff;
            border: 1px solid var(--ot-border-basic-table, #e4e7ec);
            border-radius: 16px;
            padding: 1rem 1.1rem;
        }
        .cc-pr-stat-label { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #8b93a3; margin: 0 0 0.35rem; }
        .cc-pr-stat-value { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; }
        .cc-pr-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .cc-pr-table th { text-align: left; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; color: #8b93a3; padding: 0.5rem 0.6rem; border-bottom: 1px solid var(--ot-border-basic-table, #e4e7ec); }
        .cc-pr-table td { padding: 0.55rem 0.6rem; border-bottom: 1px solid #f1f3f6; color: #334155; }
        .cc-pr-table tbody tr:last-child td { border-bottom: none; }
    </style>
    @endpush
@endonce
