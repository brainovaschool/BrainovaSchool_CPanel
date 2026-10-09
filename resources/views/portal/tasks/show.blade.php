@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    @php($task = $data['task'])
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">{{ $task->title }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ $data['isManager'] ? route('portal-tasks.index') : route('portal-my-tasks.index') }}">Tasks</a></li>
                        <li class="breadcrumb-item">{{ $task->title }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row gy-4">
            <div class="col-lg-8">
                <div class="card ot-card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="mb-0">Details</h4>
                        <span class="badge-basic-info-text">{{ str_replace('_', ' ', ucfirst($task->status)) }}</span>
                    </div>
                    <div class="card-body">
                        <p class="text-secondary">{{ $task->description ?: 'No description given.' }}</p>
                        <div class="row">
                            <div class="col-md-6"><strong>Employee:</strong> {{ optional($task->assignee)->first_name }} {{ optional($task->assignee)->last_name }}</div>
                            <div class="col-md-6"><strong>Assigned by:</strong> {{ optional($task->assignedBy)->name }}</div>
                            <div class="col-md-6"><strong>Category:</strong> {{ $task->category ?: '—' }}</div>
                            <div class="col-md-6"><strong>Priority:</strong> {{ $task->priority }} @if ($task->urgent)<span class="badge-basic-danger-text">Urgent</span>@endif</div>
                            <div class="col-md-6"><strong>Assigned date:</strong> {{ $task->assigned_date?->format('d M Y') }}</div>
                            <div class="col-md-6">
                                <strong>Due date:</strong> {{ $task->due_date?->format('d M Y') }}
                                @if ($task->is_overdue)<span class="badge-basic-danger-text">Overdue</span>@endif
                            </div>
                            @if ($task->est_hours)<div class="col-md-6"><strong>Estimated hours:</strong> {{ $task->est_hours }}</div>@endif
                            @if ($task->format)<div class="col-md-6"><strong>Expected format:</strong> {{ $task->format }}</div>@endif
                        </div>
                        @if ($task->output)<p class="mt-3"><strong>What "done" looks like:</strong><br>{{ $task->output }}</p>@endif
                        @if ($task->refs)<p><strong>References:</strong><br>{{ $task->refs }}</p>@endif
                        @if ($task->drive_link)<p><strong>Drive link:</strong> <a href="{{ $task->drive_link }}" target="_blank">{{ $task->drive_link }}</a></p>@endif
                        @if ($task->refUpload)<p><strong>Reference file:</strong> <a href="{{ globalAsset($task->refUpload->path) }}" target="_blank">Download</a></p>@endif
                        @if ($data['isManager'] && $task->notes)<p><strong>Internal notes:</strong><br>{{ $task->notes }}</p>@endif
                        @if ($task->final_score !== null)
                            <p class="mt-3"><strong>Final score:</strong> {{ $task->final_score }}/10
                                <span class="text-secondary">(revision {{ $task->revision_score }}/5 + quality {{ $task->quality_score }}/5)</span></p>
                        @endif
                        @if ($task->paid)
                            <p class="mt-3"><strong>Paid task:</strong> {{ Setting('currency_symbol') }} {{ number_format($task->amount, 2) }}
                                @if ($task->pay_status === 'paid')<span class="badge-basic-success-text">Paid</span>
                                @elseif ($task->pay_status === 'due')<span class="badge-basic-warning-text">Payment due</span>
                                @else <span class="badge-basic-info-text">Unpaid — not yet completed</span>@endif
                            </p>
                        @endif
                    </div>
                </div>

                <div class="card ot-card mt-4">
                    <div class="card-header"><h4 class="mb-0">Submission &amp; revision history</h4></div>
                    <div class="card-body">
                        @forelse ($task->submissions as $sub)
                            <div class="border-bottom pb-3 mb-3">
                                <p class="mb-1"><strong>Submission #{{ $sub->n }}</strong> — {{ $sub->created_at->format('d M Y, h:i A') }}
                                    @if ($sub->result === 'approved')<span class="badge-basic-success-text">Approved</span>
                                    @elseif ($sub->result === 'revision')<span class="badge-basic-warning-text">Sent back for revision</span>
                                    @else <span class="badge-basic-info-text">Waiting on review</span>@endif
                                </p>
                                @if ($sub->comment)<p class="mb-1">{{ $sub->comment }}</p>@endif
                                @if ($sub->link)<p class="mb-1"><a href="{{ $sub->link }}" target="_blank">{{ $sub->link }}</a></p>@endif
                                @if ($sub->upload)<p class="mb-1"><a href="{{ globalAsset($sub->upload->path) }}" target="_blank">Download attached file</a></p>@endif
                                @if ($sub->admin_comment)
                                    <p class="mb-0 text-secondary"><strong>Reviewer note:</strong> {{ $sub->admin_comment }} — {{ optional($sub->reviewedBy)->name }}, {{ $sub->reviewed_at?->format('d M Y, h:i A') }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-secondary mb-0">No work submitted yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="card ot-card mt-4">
                    <div class="card-header"><h4 class="mb-0">Comments</h4></div>
                    <div class="card-body">
                        @forelse ($task->comments as $comment)
                            <p class="mb-2"><strong>{{ optional($comment->user)->name }}</strong> — {{ $comment->created_at->diffForHumans() }}<br>{{ $comment->body }}</p>
                        @empty
                            <p class="text-secondary">No comments yet.</p>
                        @endforelse

                        @if ($data['isManager'] || $data['isAssignee'])
                            <form action="{{ route('portal-tasks.comment', $task->id) }}" method="post" class="mt-3">
                                @csrf
                                <textarea name="body" class="ot-input" rows="2" placeholder="Write a comment..." required></textarea>
                                <button type="submit" class="btn btn-sm ot-btn-primary mt-2">Post comment</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                @if ($data['canClaim'])
                    <div class="card ot-card">
                        <div class="card-header"><h4 class="mb-0">Claim this paid task</h4></div>
                        <div class="card-body">
                            @if ($data['claimBlockers']->isNotEmpty())
                                <p class="text-danger mb-2">You can't claim this right now — you have an overdue task or one in revision:</p>
                                <ul class="mb-0">
                                    @foreach ($data['claimBlockers'] as $b)
                                        <li><a href="{{ route('portal-tasks.show', $b->id) }}">{{ $b->title }}</a></li>
                                    @endforeach
                                </ul>
                            @else
                                <form action="{{ route('portal-tasks.claim', $task->id) }}" method="post">
                                    @csrf
                                    <button type="submit" class="btn ot-btn-success w-100">Claim — {{ Setting('currency_symbol') }} {{ number_format($task->amount, 2) }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($data['isAssignee'])
                    <div class="card ot-card">
                        <div class="card-header"><h4 class="mb-0">Your actions</h4></div>
                        <div class="card-body">
                            @if ($task->status === 'assigned')
                                <form action="{{ route('portal-tasks.accept', $task->id) }}" method="post">
                                    @csrf
                                    <button type="submit" class="btn ot-btn-primary w-100">Accept &amp; Start</button>
                                </form>
                            @elseif (in_array($task->status, ['in_progress', 'revision']))
                                <form action="{{ route('portal-tasks.submit', $task->id) }}" method="post" enctype="multipart/form-data">
                                    @csrf
                                    <label class="form-label">Link (optional)</label>
                                    <input type="text" name="link" class="ot-input mb-2">
                                    <label class="form-label">File (optional)</label>
                                    <input type="file" name="file" class="ot-input mb-2">
                                    <label class="form-label">Comment</label>
                                    <textarea name="comment" class="ot-input mb-2" rows="2"></textarea>
                                    <button type="submit" class="btn ot-btn-primary w-100">Submit Work</button>
                                </form>
                            @else
                                <p class="text-secondary mb-0">No action needed from you right now.</p>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($data['canMarkPaid'])
                    <div class="card ot-card mt-4">
                        <div class="card-header"><h4 class="mb-0">Payment</h4></div>
                        <div class="card-body">
                            <p>Due: {{ Setting('currency_symbol') }} {{ number_format($task->amount, 2) }} to {{ optional($task->assignee)->first_name }} {{ optional($task->assignee)->last_name }}</p>
                            <form action="{{ route('portal-tasks.mark-paid', $task->id) }}" method="post" onsubmit="return confirm('Mark this paid? This records a real expense and can\'t be undone.');">
                                @csrf
                                <button type="submit" class="btn ot-btn-success w-100">Mark Paid</button>
                            </form>
                        </div>
                    </div>
                @endif

                @if ($data['isManager'])
                    <div class="card ot-card mt-4">
                        <div class="card-header"><h4 class="mb-0">Review</h4></div>
                        <div class="card-body">
                            @if ($task->status === 'submitted')
                                <form action="{{ route('portal-tasks.start-review', $task->id) }}" method="post" class="mb-3">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary w-100">Start Review</button>
                                </form>
                            @endif

                            @if (in_array($task->status, ['submitted', 'under_review']))
                                <form action="{{ route('portal-tasks.request-revision', $task->id) }}" method="post" class="mb-3">
                                    @csrf
                                    <label class="form-label">Revision comment (required)</label>
                                    <textarea name="comment" class="ot-input mb-2" rows="2" required></textarea>
                                    <button type="submit" class="btn ot-btn-warning w-100">Send Back for Revision</button>
                                </form>

                                <form action="{{ route('portal-tasks.approve', $task->id) }}" method="post">
                                    @csrf
                                    <label class="form-label">Quality score (0–5)</label>
                                    <input type="number" name="quality_score" class="ot-input mb-2" min="0" max="5" required>
                                    <label class="form-label">Note (optional)</label>
                                    <textarea name="comment" class="ot-input mb-2" rows="2"></textarea>
                                    <button type="submit" class="btn ot-btn-success w-100">Approve &amp; Complete</button>
                                </form>
                            @else
                                <p class="text-secondary mb-0">No review action available in this status.</p>
                            @endif
                        </div>
                    </div>

                    @if ($task->status !== 'completed')
                        <div class="card ot-card mt-4">
                            <div class="card-header"><h4 class="mb-0">Reassign</h4></div>
                            <div class="card-body">
                                <form action="{{ route('portal-tasks.reassign', $task->id) }}" method="post">
                                    @csrf
                                    <select name="assigned_to" class="nice-select niceSelect bordered_style wide mb-2" required>
                                        @foreach (\App\Models\Staff\Staff::orderBy('first_name')->get() as $emp)
                                            <option value="{{ $emp->id }}" {{ $task->assigned_to == $emp->id ? 'selected' : '' }}>{{ trim($emp->first_name . ' ' . $emp->last_name) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-outline-secondary w-100">Reassign</button>
                                </form>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection
