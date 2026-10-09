@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">My Tasks</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">My Tasks</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Due</th>
                                    <th>Status</th>
                                    <th class="action">{{ ___('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['tasks'] as $task)
                                    <tr>
                                        <td>{{ $task->title }} @if ($task->urgent)<span class="badge-basic-danger-text">Urgent</span>@endif</td>
                                        <td>{{ $task->category ?: '—' }}</td>
                                        <td>{{ $task->priority }}</td>
                                        <td>
                                            {{ $task->due_date?->format('d M Y') }}
                                            @if ($task->is_overdue)
                                                <span class="badge-basic-danger-text">Overdue</span>
                                            @endif
                                        </td>
                                        <td><span class="badge-basic-info-text">{{ str_replace('_', ' ', ucfirst($task->status)) }}</span></td>
                                        <td class="action">
                                            <a href="{{ route('portal-tasks.show', $task->id) }}" class="btn btn-sm ot-btn-primary">Open</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center gray-color">
                                            <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                                            <p class="mb-0 text-center">Nothing assigned to you yet.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
