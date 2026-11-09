{{-- Expects $row: a ClassContentModule with class/section/subject/lessons.activities eager loaded (see ClassContentModuleRepository::dashboardForTeacher()) --}}
@php
    $rowLocked = (int) auth()->user()->role_id === 5
        && !in_array($row->review_status, [\App\Models\ClassContent\ClassContentModule::DRAFT, \App\Models\ClassContent\ClassContentModule::CHANGES_REQUESTED]);
    $lessonsCount  = $row->lessons->count();
    $projectsCount = $row->lessons->filter(fn ($l) => $l->hasProject())->count();
    $quizzesCount  = $row->lessons->filter(fn ($l) => $l->hasQuiz())->count();
@endphp
<div class="cc-module-card" id="row_{{ $row->id }}">
    <div class="cc-module-card-main">
        <div class="cc-module-card-body">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <h5 class="mb-0">{{ $row->title }}</h5>
                @include('class-content._status-badge', ['status' => $row->review_status])
            </div>
            <p class="text-secondary mb-0" style="font-size:.85rem;">
                {{ optional($row->class)->name }}{{ $row->section ? ' - ' . optional($row->section)->name : '' }} — {{ optional($row->subject)->name }}
                @if ($row->creator) &middot; {{ trim(optional($row->creator)->first_name . ' ' . optional($row->creator)->last_name) }} @endif
            </p>
        </div>

        <div class="cc-module-card-stats">
            <div class="text-center"><div class="cc-module-card-stat-num" style="color:#16a34a;">{{ $lessonsCount }}</div><div class="cc-module-card-stat-label">Lessons</div></div>
            <div class="text-center"><div class="cc-module-card-stat-num" style="color:#b45309;">{{ $projectsCount }}</div><div class="cc-module-card-stat-label">Projects</div></div>
            <div class="text-center"><div class="cc-module-card-stat-num" style="color:#c026d3;">{{ $quizzesCount }}</div><div class="cc-module-card-stat-label">Quizzes</div></div>
        </div>

        <div class="dropdown dropdown-action">
            <button type="button" class="btn-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-ellipsis"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="{{ route('class-content-module.lessons', $row->id) }}">
                        <span class="icon mr-8"><i class="fa-solid fa-list"></i></span>Lessons ({{ $lessonsCount }})
                    </a>
                </li>
                @if (hasPermission('class_content_update') && !$rowLocked)
                    <li>
                        <a class="dropdown-item" href="{{ route('class-content-module.edit', $row->id) }}">
                            <span class="icon mr-8"><i class="fa-solid fa-pen-to-square"></i></span>{{ ___('common.edit') }}
                        </a>
                    </li>
                @endif
                @if (hasPermission('class_content_submit') && !$rowLocked && in_array($row->review_status, [\App\Models\ClassContent\ClassContentModule::DRAFT, \App\Models\ClassContent\ClassContentModule::CHANGES_REQUESTED]))
                    @if ($lessonsCount > 0)
                        <li>
                            <a class="dropdown-item" href="javascript:void(0);" onclick="document.getElementById('submit-form-{{ $row->id }}').submit();">
                                <span class="icon mr-8"><i class="fa-solid fa-paper-plane"></i></span>Submit for review
                            </a>
                            <form id="submit-form-{{ $row->id }}" action="{{ route('class-content-module.submit', $row->id) }}" method="post" class="d-none">
                                @csrf
                            </form>
                        </li>
                    @else
                        <li>
                            <span class="dropdown-item text-secondary" style="cursor:not-allowed;" title="Add at least one lesson first">
                                <span class="icon mr-8"><i class="fa-solid fa-paper-plane"></i></span>Submit for review
                            </span>
                        </li>
                    @endif
                @endif
                @if (hasPermission('class_content_delete') && !$rowLocked)
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);"
                            onclick="delete_row('class-content/delete', {{ $row->id }})">
                            <span class="icon mr-8"><i class="fa-solid fa-trash-can"></i></span>
                            <span>{{ ___('common.delete') }}</span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</div>

@once
    @push('css')
    <style>
        .cc-module-card { background: var(--ot-bg-table-card, #fff); border: 1px solid var(--ot-border-basic-table, #e4e7ec); border-radius: 14px; padding: 16px 18px; margin-bottom: 12px; }
        .cc-module-card-main { display: flex; flex-wrap: wrap; align-items: center; gap: 16px; }
        .cc-module-card-body { flex: 1; min-width: 220px; }
        .cc-module-card-stats { display: flex; gap: 20px; }
        .cc-module-card-stat-num { font-size: 14px; font-weight: 700; }
        .cc-module-card-stat-label { font-size: 11px; color: var(--ot-text-secondary, #8b93a3); }
    </style>
    @endpush
@endonce
