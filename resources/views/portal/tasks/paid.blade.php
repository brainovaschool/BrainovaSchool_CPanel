@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">Paid Tasks</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Paid Tasks</li>
                    </ol>
                </div>
                @if ($data['isManager'])
                    <div class="col-sm-4 text-sm-end">
                        <a href="{{ route('portal-tasks.create') }}" class="btn ot-btn-primary">+ Create Paid Task</a>
                    </div>
                @endif
            </div>
        </div>

        @unless ($data['isManager'])
            @if ($data['claimBlocked'])
                <div class="card ot-card mb-24" style="border-left:4px solid #dc3545;">
                    <div class="card-body">
                        <p class="text-danger mb-2"><strong>You can't claim an open task right now</strong> — complete 1 of your {{ $data['claimBlockers']->count() }} tasks to become eligible for more:</p>
                        <ul class="mb-0">
                            @foreach ($data['claimBlockers'] as $b)
                                <li><a href="{{ route('portal-tasks.show', $b->id) }}">{{ $b->title }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        @endunless

        <form action="{{ route('portal-paid-tasks.index') }}" method="get">
            <div class="card ot-card bn-reels-filters mb-24 d-flex flex-row align-items-center gap-3 flex-wrap" style="padding:1rem 1.25rem">
                <select name="series_name" class="nice-select niceSelect bordered_style">
                    <option value="">All series</option>
                    @foreach ($data['openSeriesOptions'] as $s)
                        <option value="{{ $s }}" {{ ($data['filters']['series_name'] ?? '') === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
                <select name="prompt_status" class="nice-select niceSelect bordered_style">
                    <option value="">Prompt — any status</option>
                    <option value="ready" {{ ($data['filters']['prompt_status'] ?? '') === 'ready' ? 'selected' : '' }}>Ready</option>
                    <option value="no_prompt" {{ ($data['filters']['prompt_status'] ?? '') === 'no_prompt' ? 'selected' : '' }}>No prompt</option>
                    <option value="in_process" {{ ($data['filters']['prompt_status'] ?? '') === 'in_process' ? 'selected' : '' }}>In process</option>
                </select>
                <button class="btn ot-btn-primary" type="submit">{{ ___('common.Search') }}</button>
                @if (($data['filters']['series_name'] ?? '') !== '' || ($data['filters']['prompt_status'] ?? '') !== '')
                    <a href="{{ route('portal-paid-tasks.index') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>

        <div class="table-content table-basic mb-24">
            <div class="card">
                <div class="card-header"><h4 class="mb-0">Open — available to claim</h4></div>
                <div class="card-body">
                    <div class="table-responsive">
                    <table class="ot-basic-table ot-table-bg">
                        <thead class="thead"><tr><th>Title</th><th>Code</th><th>Series</th><th>Due</th><th>Amount</th><th>Prompt</th><th>Status</th>@if ($data['isManager'])<th>Visible to</th>@endif<th>Claimed by</th><th class="action">{{ ___('common.action') }}</th></tr></thead>
                        <tbody class="tbody">
                            @forelse ($data['openTasks'] as $t)
                                <tr>
                                    <td>{{ $t->title }}</td>
                                    <td>{{ $t->reel?->code ?: '—' }}</td>
                                    <td>{{ $t->reel?->series_name ?: '—' }}</td>
                                    <td>{{ $t->due_date?->format('d M Y') }}</td>
                                    <td>{{ Setting('currency_symbol') }} {{ number_format($t->amount, 2) }}</td>
                                    <td>
                                        @if ($t->no_prompt)
                                            <span class="badge-basic-secondary-text">No prompt</span>
                                        @elseif ($t->prompt_ready)
                                            <span class="badge-basic-success-text">Ready</span>
                                        @else
                                            <span class="badge-basic-warning-text">In process</span>
                                        @endif
                                    </td>
                                    <td><span class="badge-basic-info-text">{{ str_replace('_', ' ', ucfirst($t->status)) }}</span></td>
                                    @if ($data['isManager'])
                                        <td style="font-size:.85rem">
                                            @if (empty($t->visible_to_role_ids) && empty($t->visible_to_staff_ids))
                                                Everyone
                                            @else
                                                @if (!empty($t->visible_to_role_ids))
                                                    {{ \App\Models\Role::whereIn('id', $t->visible_to_role_ids)->pluck('name')->implode(', ') }}
                                                @endif
                                                @if (!empty($t->visible_to_staff_ids))
                                                    @if (!empty($t->visible_to_role_ids))<br>+ @endif
                                                    {{ \App\Models\Staff\Staff::whereIn('id', $t->visible_to_staff_ids)->get()->map(fn ($s) => trim($s->first_name . ' ' . $s->last_name))->implode(', ') }}
                                                @endif
                                            @endif
                                            @if (!empty($t->hidden_from_staff_ids))
                                                <br><span class="text-danger">− {{ \App\Models\Staff\Staff::whereIn('id', $t->hidden_from_staff_ids)->get()->map(fn ($s) => trim($s->first_name . ' ' . $s->last_name))->implode(', ') }}</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td>{{ optional($t->assignee)->first_name ? trim($t->assignee->first_name . ' ' . $t->assignee->last_name) : '—' }}</td>
                                    <td class="action"><a href="{{ route('portal-tasks.show', $t->id) }}" class="btn btn-sm ot-btn-primary">{{ $data['isManager'] ? 'Open' : ($t->prompt_ready ? 'View & Claim' : 'View') }}</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="100%" class="text-center gray-color">{{ ___('common.no_data_available') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>

        @if ($data['isManager'])
            <div class="table-content table-basic mb-24">
                <div class="card">
                    <div class="card-header"><h4 class="mb-0">Claimed / in progress</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                        <table class="ot-basic-table ot-table-bg">
                            <thead class="thead"><tr><th>Title</th><th>Employee</th><th>Status</th><th>Amount</th><th class="action">{{ ___('common.action') }}</th></tr></thead>
                            <tbody class="tbody">
                                @forelse ($data['claimedTasks'] as $t)
                                    <tr>
                                        <td>{{ $t->title }}</td>
                                        <td>{{ optional($t->assignee)->first_name }} {{ optional($t->assignee)->last_name }}</td>
                                        <td><span class="badge-basic-info-text">{{ str_replace('_', ' ', ucfirst($t->status)) }}</span></td>
                                        <td>{{ Setting('currency_symbol') }} {{ number_format($t->amount, 2) }}</td>
                                        <td class="action"><a href="{{ route('portal-tasks.show', $t->id) }}" class="btn btn-sm ot-btn-primary">Open</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="100%" class="text-center gray-color">{{ ___('common.no_data_available') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-content table-basic">
                <div class="card">
                    <div class="card-header"><h4 class="mb-0">Completed</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                        <table class="ot-basic-table ot-table-bg">
                            <thead class="thead"><tr><th>Title</th><th>Employee</th><th>Amount</th><th>Payment</th><th class="action">{{ ___('common.action') }}</th></tr></thead>
                            <tbody class="tbody">
                                @forelse ($data['completedTasks'] as $t)
                                    <tr>
                                        <td>{{ $t->title }}</td>
                                        <td>{{ optional($t->assignee)->first_name }} {{ optional($t->assignee)->last_name }}</td>
                                        <td>{{ Setting('currency_symbol') }} {{ number_format($t->amount, 2) }}</td>
                                        <td>
                                            @if ($t->pay_status === 'paid')
                                                <span class="badge-basic-success-text">Paid</span>
                                            @else
                                                <span class="badge-basic-warning-text">Due</span>
                                            @endif
                                        </td>
                                        <td class="action"><a href="{{ route('portal-tasks.show', $t->id) }}" class="btn btn-sm ot-btn-primary">Open</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="100%" class="text-center gray-color">{{ ___('common.no_data_available') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="table-content table-basic">
                <div class="card">
                    <div class="card-header"><h4 class="mb-0">My Paid Tasks</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                        <table class="ot-basic-table ot-table-bg">
                            <thead class="thead"><tr><th>Title</th><th>Status</th><th>Amount</th><th>Payment</th></tr></thead>
                            <tbody class="tbody">
                                @forelse ($data['myPaidTasks'] as $t)
                                    <tr>
                                        <td><a href="{{ route('portal-tasks.show', $t->id) }}">{{ $t->title }}</a></td>
                                        <td><span class="badge-basic-info-text">{{ str_replace('_', ' ', ucfirst($t->status)) }}</span></td>
                                        <td>{{ Setting('currency_symbol') }} {{ number_format($t->amount, 2) }}</td>
                                        <td>
                                            @if ($t->pay_status === 'paid')
                                                <span class="badge-basic-success-text">Paid</span>
                                            @elseif ($t->pay_status === 'due')
                                                <span class="badge-basic-warning-text">Due</span>
                                            @else
                                                <span class="badge-basic-info-text">Not yet completed</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="100%" class="text-center gray-color">{{ ___('common.no_data_available') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
