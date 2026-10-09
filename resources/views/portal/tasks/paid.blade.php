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
            @if ($data['claimBlockers']->isNotEmpty())
                <div class="card ot-card mb-24" style="border-left:4px solid #dc3545;">
                    <div class="card-body">
                        <p class="text-danger mb-2"><strong>You can't claim an open task right now</strong> — you have an overdue task or one in revision:</p>
                        <ul class="mb-0">
                            @foreach ($data['claimBlockers'] as $b)
                                <li><a href="{{ route('portal-tasks.show', $b->id) }}">{{ $b->title }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        @endunless

        <div class="table-content table-basic mb-24">
            <div class="card">
                <div class="card-header"><h4 class="mb-0">Open — available to claim</h4></div>
                <div class="card-body">
                    <table class="table table-bordered class-table">
                        <thead class="thead"><tr><th>Title</th><th>Category</th><th>Due</th><th>Amount</th><th class="action">{{ ___('common.action') }}</th></tr></thead>
                        <tbody class="tbody">
                            @forelse ($data['openTasks'] as $t)
                                <tr>
                                    <td>{{ $t->title }}</td>
                                    <td>{{ $t->category ?: '—' }}</td>
                                    <td>{{ $t->due_date?->format('d M Y') }}</td>
                                    <td>{{ Setting('currency_symbol') }} {{ number_format($t->amount, 2) }}</td>
                                    <td class="action"><a href="{{ route('portal-tasks.show', $t->id) }}" class="btn btn-sm ot-btn-primary">{{ $data['isManager'] ? 'Open' : 'View & Claim' }}</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="100%" class="text-center gray-color">{{ ___('common.no_data_available') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if ($data['isManager'])
            <div class="table-content table-basic mb-24">
                <div class="card">
                    <div class="card-header"><h4 class="mb-0">Claimed / in progress</h4></div>
                    <div class="card-body">
                        <table class="table table-bordered class-table">
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

            <div class="table-content table-basic">
                <div class="card">
                    <div class="card-header"><h4 class="mb-0">Completed</h4></div>
                    <div class="card-body">
                        <table class="table table-bordered class-table">
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
        @else
            <div class="table-content table-basic">
                <div class="card">
                    <div class="card-header"><h4 class="mb-0">My Paid Tasks</h4></div>
                    <div class="card-body">
                        <table class="table table-bordered class-table">
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
        @endif
    </div>
@endsection
