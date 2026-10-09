@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    @php
        $weekStart = \Carbon\Carbon::parse($data['weekStart']);
        $days = collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i));
        $plansByStaff = $data['plansByStaff'];
        $myGoal = $data['goals']->get($data['myStaffId']);
    @endphp
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">Social Board</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Daily Plan &amp; Weekly Goals</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card mb-24 d-flex flex-row align-items-center gap-3" style="padding:1rem 1.25rem">
            <a href="{{ route('portal-social-daily-plan.index', ['week' => $weekStart->copy()->subWeek()->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-secondary">&larr; Previous week</a>
            <strong>{{ $weekStart->format('d M') }} – {{ $weekStart->copy()->addDays(6)->format('d M Y') }}</strong>
            <a href="{{ route('portal-social-daily-plan.index', ['week' => $weekStart->copy()->addWeek()->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-secondary">Next week &rarr;</a>
        </div>

        <div class="table-content table-basic mb-24">
            <div class="card">
                <div class="card-header"><h4 class="mb-0">Daily Plan</h4></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    <th>Person</th>
                                    @foreach ($days as $day)
                                        <th>{{ $day->format('D, d M') }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['contentTeam'] as $member)
                                    <tr>
                                        <td>{{ trim($member->first_name . ' ' . $member->last_name) }}</td>
                                        @foreach ($days as $day)
                                            @php($plan = ($plansByStaff->get($member->id) ?? collect())->firstWhere(fn ($p) => $p->date->format('Y-m-d') === $day->format('Y-m-d')))
                                            <td style="min-width:150px">
                                                @if ($data['myStaffId'] === $member->id)
                                                    <form action="{{ route('portal-social-daily-plan.store') }}" method="post">
                                                        @csrf
                                                        <input type="hidden" name="date" value="{{ $day->format('Y-m-d') }}">
                                                        <textarea name="plan_text" class="ot-input" rows="2" onblur="this.form.requestSubmit()">{{ $plan->plan_text ?? '' }}</textarea>
                                                    </form>
                                                @else
                                                    {{ $plan->plan_text ?? '' }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr><td colspan="100%" class="text-center gray-color">No one currently holds the Content responsibility — assign it from Team Portal → Responsibilities.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-header"><h4 class="mb-0">Weekly Goals</h4></div>
                <div class="card-body">
                    <div class="table-responsive">
                    <table class="table table-bordered class-table">
                        <thead class="thead"><tr><th>Person</th><th>Goal</th><th>Target metric</th><th>Achieved</th></tr></thead>
                        <tbody class="tbody">
                            @foreach ($data['contentTeam'] as $member)
                                @php($goal = $data['goals']->get($member->id))
                                <tr>
                                    <td>{{ trim($member->first_name . ' ' . $member->last_name) }}</td>
                                    <td>{{ $goal->goal_text ?? '—' }}</td>
                                    <td>{{ $goal->target_metric ?? '—' }}</td>
                                    <td>
                                        @if ($goal)
                                            @if ($data['myStaffId'] === $member->id)
                                                <form action="{{ route('portal-social-weekly-goal.toggle', $goal->id) }}" method="post">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm {{ $goal->achieved ? 'ot-btn-success' : 'btn-outline-secondary' }}">{{ $goal->achieved ? 'Achieved' : 'Not yet' }}</button>
                                                </form>
                                            @else
                                                <span class="{{ $goal->achieved ? 'badge-basic-success-text' : 'badge-basic-warning-text' }}">{{ $goal->achieved ? 'Achieved' : 'Not yet' }}</span>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>

                    @if ($data['isOnTeam'])
                        <div class="border-top pt-3 mt-3">
                            <h5>Set my goal for this week</h5>
                            <form action="{{ route('portal-social-weekly-goal.store') }}" method="post" class="d-flex gap-2 flex-wrap align-items-end">
                                @csrf
                                <input type="hidden" name="week_start" value="{{ $weekStart->format('Y-m-d') }}">
                                <div style="min-width:260px">
                                    <label class="form-label">Goal</label>
                                    <input type="text" name="goal_text" class="ot-input" value="{{ $myGoal->goal_text ?? '' }}">
                                </div>
                                <div>
                                    <label class="form-label">Target metric (optional)</label>
                                    <input type="text" name="target_metric" class="ot-input" value="{{ $myGoal->target_metric ?? '' }}" placeholder="e.g. 500 new followers">
                                </div>
                                <button type="submit" class="btn ot-btn-primary">Save</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
