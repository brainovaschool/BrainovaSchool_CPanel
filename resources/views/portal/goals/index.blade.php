@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">Yearly Goals</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Planning Board</li>
                        <li class="breadcrumb-item">Yearly Goals</li>
                    </ol>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <div class="btn-group" role="group">
                        <a href="{{ route('portal-goals.index', ['view' => 'tree', 'year' => $data['year']]) }}" class="btn {{ $data['view'] === 'tree' ? 'ot-btn-primary' : 'btn-outline-secondary' }}">Goal tree</a>
                        <a href="{{ route('portal-goals.index', ['view' => 'roadmap', 'year' => $data['year']]) }}" class="btn {{ $data['view'] === 'roadmap' ? 'ot-btn-primary' : 'btn-outline-secondary' }}">Roadmap</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="d-flex flex-wrap align-items-center gap-24 mb-16" style="font-size: 13px; color: var(--ot-text-primary);">
            <div class="d-flex align-items-center gap-16">
                <span class="goal-legend-item"><span class="goal-legend-dot" style="background:#5e17eb"></span> Goal</span>
                <span class="goal-legend-item"><span class="goal-legend-dot" style="background:#0097b2"></span> Sub-goal</span>
                <span class="goal-legend-item"><span class="goal-legend-dot" style="background:#b45309"></span> Step</span>
            </div>
            <div class="d-flex align-items-center gap-16">
                <span class="badge-basic-secondary-text">Not started</span>
                <span class="badge-basic-info-text">In progress</span>
                <span class="badge-basic-success-text">Done</span>
            </div>
        </div>

        <!-- New top-level goal -->
        <div class="card ot-card mb-16">
            <div class="card-body">
                <details>
                    <summary style="cursor:pointer;font-weight:600;color:#0097b2;">+ New goal</summary>
                    <form action="{{ route('portal-goals.store') }}" method="post" class="mt-3">
                        @csrf
                        <input type="text" name="title" class="form-control ot-input mb-2" placeholder="Goal title" required maxlength="150">
                        <textarea name="output" class="form-control ot-input mb-2" rows="2" placeholder="Output / deliverable — what does achieving this look like?"></textarea>
                        <textarea name="prerequisites" class="form-control ot-input mb-2" rows="2" placeholder="Prerequisites"></textarea>
                        <div class="row g-2 mb-2">
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Start date</label>
                                <input type="date" name="start_date" class="form-control ot-input">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">End date</label>
                                <input type="date" name="end_date" class="form-control ot-input">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Status</label>
                                <select name="status" class="nice-select niceSelect bordered_style wide">
                                    <option value="not_started">Not started</option>
                                    <option value="in_progress">In progress</option>
                                    <option value="done">Done</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Owner</label>
                                <select name="owner_staff_id" class="nice-select niceSelect bordered_style wide">
                                    <option value="">— None —</option>
                                    @foreach ($data['staffList'] as $s)
                                        <option value="{{ $s->id }}">{{ trim($s->first_name . ' ' . $s->last_name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn ot-btn-primary">Add goal</button>
                    </form>
                </details>
            </div>
        </div>

        @if ($data['view'] === 'roadmap')
            <div class="roadmap-wrap mb-16">
                <div class="d-flex align-items-center justify-content-center gap-16 mb-16">
                    <a href="{{ route('portal-goals.index', ['view' => 'roadmap', 'year' => $data['year'] - 1]) }}" class="btn btn-sm btn-outline-secondary">&larr; {{ $data['year'] - 1 }}</a>
                    <strong style="font-size: 16px;">{{ $data['year'] }}</strong>
                    <a href="{{ route('portal-goals.index', ['view' => 'roadmap', 'year' => $data['year'] + 1]) }}" class="btn btn-sm btn-outline-secondary">{{ $data['year'] + 1 }} &rarr;</a>
                </div>
                <div class="roadmap-grid">
                    <div class="roadmap-header-row">
                        <div></div>
                        @foreach (['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $m)
                            <div>{{ $m }}</div>
                        @endforeach
                    </div>
                    @forelse ($data['roadmapGoals'] as $g)
                        @php $progress = $g->progressPercent(); @endphp
                        <div class="roadmap-row">
                            <div class="roadmap-label">{{ $g->title }}</div>
                            <div class="roadmap-bar roadmap-bar-{{ $g->status }}" style="grid-column: {{ $g->roadmapStartCol }} / {{ $g->roadmapEndCol }};">
                                <span>{{ $progress !== null ? $progress . '%' : $g->statusLabel() }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-secondary text-center mt-3 mb-0">No goals have both a start and end date in {{ $data['year'] }} yet — set them on a goal (in Goal tree) to see it here.</p>
                    @endforelse
                </div>
            </div>
            <p class="text-secondary" style="font-size: 13px;">The big picture: which goal runs when across the year, and how far along each one is. Switch to Goal tree for the sub-goals and detail underneath any bar.</p>
        @else
            @forelse ($data['tree'] as $goal)
                @include('portal.goals._row', ['goal' => $goal, 'depth' => 0, 'staffList' => $data['staffList']])
            @empty
                <div class="card ot-card">
                    <div class="card-body text-center gray-color">
                        <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                        <p class="mb-0">No goals yet — add the first one above.</p>
                    </div>
                </div>
            @endforelse
            <p class="text-secondary mt-3" style="font-size: 13px;">Click "Details & tasks" on any goal for its output, prerequisites and assigned tasks (each with its own deadline). "Assign a task here" creates a real Task Board task linked to that goal — it shows up in My Tasks, where it's edited with full details, same as any other task.</p>
        @endif

    </div>
@endsection

@push('css')
<style>
    .goal-legend-item { display: inline-flex; align-items: center; gap: 6px; }
    .goal-legend-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }

    .goal-node {
        background: var(--ot-bg-table-card, #fff);
        border: 1px solid var(--ot-border-basic-table, #e4e7ec);
        border-left-width: 4px;
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 10px;
    }
    .goal-node-main { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
    .goal-tier-badge { font-size: 10px; font-weight: 700; letter-spacing: .04em; padding: 3px 8px; border-radius: 999px; }
    .goal-timeline, .goal-progress-text, .goal-owner { font-size: 12px; color: var(--ot-text-secondary, #5b6472); }

    .goal-details > summary,
    .goal-children > summary,
    .goal-inline-form > summary {
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        color: #0097b2;
        margin-top: 10px;
    }
    .goal-details-body { margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--ot-border-basic-table, #e4e7ec); }
    .goal-details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 12px; }
    .goal-details-label { font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--ot-text-secondary, #8b93a3); margin-bottom: 4px; }
    .goal-details-value { font-size: 13px; color: var(--ot-text-primary, #334058); line-height: 1.5; }

    .goal-task-row { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 6px 0; border-bottom: 1px dashed var(--ot-border-basic-table, #edeef1); font-size: 13px; }
    .goal-no-tasks { font-size: 13px; color: var(--ot-text-secondary, #8b93a3); margin: 6px 0; }

    .goal-inline-form form { max-width: 520px; margin-top: 8px; }

    .goal-task-inline-form { margin: 0; }
    .goal-task-select { min-width: 130px; max-width: 160px; font-size: 12.5px; }
    .goal-task-date { min-width: 130px; font-size: 12.5px; padding: 4px 8px; }
    .goal-task-date.is-overdue { color: #b91c1c; font-weight: 600; }
    .goal-task-delete { border: none; background: none; color: #b91c1c; font-size: 13px; padding: 4px 6px; cursor: pointer; margin-left: auto; }
    .goal-task-delete:hover { color: #7f1d1d; }
    .goal-children { margin-top: 10px; }
    .goal-children > summary { color: var(--ot-text-secondary, #475065); }
    .goal-children > div { margin-top: 10px; }

    .roadmap-wrap { background: var(--ot-bg-table-card, #fff); border: 1px solid var(--ot-border-basic-table, #e4e7ec); border-radius: 16px; padding: 20px; overflow-x: auto; }
    .roadmap-grid { min-width: 760px; }
    .roadmap-header-row, .roadmap-row { display: grid; grid-template-columns: 220px repeat(12, 1fr); align-items: center; }
    .roadmap-header-row > div { text-align: center; font-size: 12px; font-weight: 600; color: var(--ot-text-secondary, #8b93a3); padding-bottom: 10px; border-bottom: 1px solid var(--ot-border-basic-table, #edeef1); }
    .roadmap-row { min-height: 54px; border-bottom: 1px solid var(--ot-border-basic-table, #f1f2f5); }
    .roadmap-label { font-size: 14px; font-weight: 600; color: var(--ot-text-primary, #1f2430); padding-right: 14px; }
    .roadmap-bar { height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: flex-end; padding-right: 10px; font-size: 12px; font-weight: 700; color: #fff; }
    .roadmap-bar-not_started { background: #9aa2b1; }
    .roadmap-bar-in_progress { background: #0097b2; }
    .roadmap-bar-done { background: #16a34a; }
</style>
@endpush

@push('script')
@include('backend.partials.delete-ajax')
@endpush
