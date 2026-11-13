@php
    $tier = min($depth, 2);
    $tierMeta = [
        0 => ['label' => 'GOAL', 'color' => '#5e17eb', 'bg' => '#f3ecfe', 'size' => '16px', 'weight' => '700'],
        1 => ['label' => 'SUB-GOAL', 'color' => '#0097b2', 'bg' => '#e1f6fa', 'size' => '14px', 'weight' => '600'],
        2 => ['label' => 'STEP', 'color' => '#b45309', 'bg' => '#fdf0e1', 'size' => '13px', 'weight' => '500'],
    ][$tier];
    $statusBadgeClass = [
        'not_started' => 'badge-basic-secondary-text',
        'in_progress' => 'badge-basic-info-text',
        'done'        => 'badge-basic-success-text',
    ][$goal->status] ?? 'badge-basic-secondary-text';
    $progress = $goal->progressPercent();
    $timeline = ($goal->start_date || $goal->end_date)
        ? ($goal->start_date?->format('d M Y') ?: '—') . ' – ' . ($goal->end_date?->format('d M Y') ?: '—')
        : null;
    $childNoun = $depth === 0 ? 'sub-goal' : 'step';
@endphp

<div class="goal-node" style="margin-left: {{ $depth * 24 }}px; border-left-color: {{ $tierMeta['color'] }};">
    <div class="goal-node-main">
        <span class="goal-tier-badge" style="background: {{ $tierMeta['bg'] }}; color: {{ $tierMeta['color'] }};">{{ $tierMeta['label'] }}</span>
        <span class="goal-title" style="font-size: {{ $tierMeta['size'] }}; font-weight: {{ $tierMeta['weight'] }};">{{ $goal->title }}</span>
        <span class="{{ $statusBadgeClass }}">{{ $goal->statusLabel() }}</span>
        @if ($timeline)
            <span class="goal-timeline">{{ $timeline }}</span>
        @endif
        @if ($progress !== null)
            <span class="goal-progress-text" title="Share of this {{ strtolower($tierMeta['label']) }}'s tasks that are completed">{{ $progress }}% of tasks done</span>
        @endif
        @if ($goal->owner)
            <span class="goal-owner">{{ trim($goal->owner->first_name . ' ' . $goal->owner->last_name) }}</span>
        @endif
        <button type="button" class="goal-delete" title="Delete this {{ strtolower($tierMeta['label']) }}" onclick="delete_row('portal/goals', {{ $goal->id }})">
            <i class="fa-solid fa-trash-can"></i>
        </button>
    </div>

    @if ($goal->description)
        <div class="goal-description">{{ $goal->description }}</div>
    @endif

    <details class="goal-details">
        <summary>Details &amp; tasks</summary>
        <div class="goal-details-body">
            <div class="goal-tasks">
                @forelse ($goal->tasks as $task)
                    <div class="goal-task-row" id="row_{{ $task->id }}">
                        <a href="{{ route('portal-tasks.show', $task->id) }}">{{ $task->title }}</a>
                        <form action="{{ route('portal-tasks.reassign', $task->id) }}" method="post" class="goal-task-inline-form">
                            @csrf
                            <select name="assigned_to" class="nice-select niceSelect bordered_style wide goal-task-select" required onchange="this.form.submit()">
                                @foreach ($staffList as $s)
                                    <option value="{{ $s->id }}" {{ (int) $task->assigned_to === $s->id ? 'selected' : '' }}>{{ trim($s->first_name . ' ' . $s->last_name) }}</option>
                                @endforeach
                            </select>
                        </form>
                        <form action="{{ route('portal-tasks.reschedule', $task->id) }}" method="post" class="goal-task-inline-form">
                            @csrf
                            <input type="date" name="due_date" class="form-control ot-input goal-task-date {{ $task->is_overdue ? 'is-overdue' : '' }}" value="{{ optional($task->due_date)->format('Y-m-d') }}" required onchange="this.form.submit()">
                        </form>
                        <span class="badge-basic-info-text">{{ str_replace('_', ' ', ucfirst($task->status)) }}</span>
                        <button type="button" class="goal-task-delete" title="Delete this task" onclick="delete_row('portal/tasks/delete', {{ $task->id }})">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                @empty
                    <div class="goal-no-tasks">No tasks assigned yet.</div>
                @endforelse

                <details class="goal-inline-form">
                    <summary>+ Assign a task here</summary>
                    <form action="{{ route('portal-goals.assign-task', $goal->id) }}" method="post">
                        @csrf
                        <input type="text" name="title" class="form-control ot-input mb-2" placeholder="Task title" required maxlength="150">
                        <select name="assigned_to" class="nice-select niceSelect bordered_style wide mb-2" required>
                            <option value="">— Assign to —</option>
                            @foreach ($staffList as $s)
                                <option value="{{ $s->id }}">{{ trim($s->first_name . ' ' . $s->last_name) }}</option>
                            @endforeach
                        </select>
                        <input type="date" name="due_date" class="form-control ot-input mb-2" required>
                        <button type="submit" class="btn btn-sm ot-btn-primary">Assign task</button>
                    </form>
                </details>
            </div>

            <details class="goal-inline-form">
                <summary>Edit this {{ strtolower($tierMeta['label']) }}</summary>
                <form action="{{ route('portal-goals.update', $goal->id) }}" method="post">
                    @csrf
                    @method('PUT')
                    <input type="text" name="title" value="{{ $goal->title }}" class="form-control ot-input mb-2" required maxlength="150">
                    <textarea name="description" class="form-control ot-input mb-2" rows="2" placeholder="Description">{{ $goal->description }}</textarea>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small mb-1">Start date</label>
                            <input type="date" name="start_date" value="{{ optional($goal->start_date)->format('Y-m-d') }}" class="form-control ot-input">
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1">End date</label>
                            <input type="date" name="end_date" value="{{ optional($goal->end_date)->format('Y-m-d') }}" class="form-control ot-input">
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small mb-1">Status</label>
                            <select name="status" class="nice-select niceSelect bordered_style wide">
                                <option value="not_started" {{ $goal->status === 'not_started' ? 'selected' : '' }}>Not started</option>
                                <option value="in_progress" {{ $goal->status === 'in_progress' ? 'selected' : '' }}>In progress</option>
                                <option value="done" {{ $goal->status === 'done' ? 'selected' : '' }}>Done</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1">Owner</label>
                            <select name="owner_staff_id" class="nice-select niceSelect bordered_style wide">
                                <option value="">— None —</option>
                                @foreach ($staffList as $s)
                                    <option value="{{ $s->id }}" {{ (int) $goal->owner_staff_id === $s->id ? 'selected' : '' }}>{{ trim($s->first_name . ' ' . $s->last_name) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm ot-btn-primary">Save changes</button>
                </form>
            </details>

            <details class="goal-inline-form">
                <summary>+ Add a {{ $childNoun }} under this {{ strtolower($tierMeta['label']) }}</summary>
                <form action="{{ route('portal-goals.store') }}" method="post">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $goal->id }}">
                    <input type="text" name="title" class="form-control ot-input mb-2" placeholder="Title" required maxlength="150">
                    <textarea name="description" class="form-control ot-input mb-2" rows="2" placeholder="Description"></textarea>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small mb-1">Start date</label>
                            <input type="date" name="start_date" class="form-control ot-input">
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1">End date</label>
                            <input type="date" name="end_date" class="form-control ot-input">
                        </div>
                    </div>
                    <label class="form-label small mb-1">Owner</label>
                    <select name="owner_staff_id" class="nice-select niceSelect bordered_style wide mb-2">
                        <option value="">— None —</option>
                        @foreach ($staffList as $s)
                            <option value="{{ $s->id }}">{{ trim($s->first_name . ' ' . $s->last_name) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm ot-btn-primary">Add</button>
                </form>
            </details>
        </div>
    </details>

    @if ($goal->childGoals->isNotEmpty())
        <details class="goal-children" open>
            <summary>{{ $goal->childGoals->count() }} {{ $childNoun }}{{ $goal->childGoals->count() === 1 ? '' : 's' }}</summary>
            <div>
                @foreach ($goal->childGoals as $child)
                    @include('portal.goals._row', ['goal' => $child, 'depth' => $depth + 1, 'staffList' => $staffList])
                @endforeach
            </div>
        </details>
    @endif
</div>
