@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    @php($reel = $data['reel'])
    @php($isManager = $data['isManager'])
    @php($canAssign = $data['canAssign'])
    @php($canApprove = $data['canApprove'])
    @php($myStaffId = $data['myStaffId'])
    @php($isAssignee = $myStaffId !== null && (int) $reel->assigned_to === (int) $myStaffId)
    <div class="page-content">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">
                        {{ $reel->title }}
                        @if ($reel->code)
                            <span class="badge-basic-info-text">{{ $reel->code }}</span>
                        @endif
                    </h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('portal-social-reels.index') }}">Reels</a></li>
                        <li class="breadcrumb-item">{{ $reel->title }}</li>
                    </ol>
                </div>
                <div class="col-sm-4 text-sm-end">
                    @if ($reel->status !== \App\Models\Portal\PortalReel::SUGGESTED && ($canAssign || $isAssignee))
                        <a href="{{ route('portal-social-reels.setup', $reel->id) }}" class="btn ot-btn-primary">Reel Setup</a>
                    @endif
                    @if ($data['canManage'])
                        <a href="{{ route('portal-social-reels.edit', $reel->id) }}" class="btn btn-outline-secondary">Edit</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="row gy-4">
            <div class="col-lg-8">
                <div class="card ot-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Details</h4>
                        <span class="badge-basic-info-text">{{ $reel->statusLabel() }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6"><strong>Code:</strong> {{ $reel->code ?: '—' }}</div>
                            <div class="col-md-6"><strong>Format:</strong> {{ $reel->format }}</div>
                            <div class="col-md-6"><strong>Pillar:</strong> {{ $reel->category ?: '—' }}</div>
                            <div class="col-md-6"><strong>Series:</strong> {{ $reel->series_name ?: '—' }}</div>
                            <div class="col-md-6"><strong>Suggested by:</strong> {{ optional($reel->suggestedBy)->name }}</div>
                            <div class="col-md-6">
                                <strong>Assigned to:</strong>
                                {{ $reel->is_open_paid ? 'Open — Paid Task' : (optional($reel->assignee)->first_name ? trim($reel->assignee->first_name . ' ' . $reel->assignee->last_name) : '—') }}
                            </div>
                            <div class="col-md-6"><strong>Deadline:</strong> {{ $reel->deadline?->format('d M Y') ?: '—' }}</div>
                            <div class="col-md-6"><strong>Uploaded date:</strong> {{ $reel->planned_date?->format('d M Y') ?: '—' }}</div>
                        </div>
                        @if ($reel->hook)<p class="mt-3"><strong>Hook:</strong> {{ $reel->hook }}</p>@endif
                        @if ($reel->prompt_text)<p><strong>Prompt:</strong><br>{{ $reel->prompt_text }}</p>@endif
                        @if ($reel->prompt_link)
                            <p class="mb-1"><strong>Prompt link:</strong> <a href="{{ $reel->prompt_link }}" target="_blank">{{ $reel->prompt_link }}</a>
                                @if ($reel->prompt_approved_at) <span class="text-success">— approved</span> @else <span class="text-secondary">— pending approval</span> @endif
                            </p>
                        @endif
                        @if ($reel->prompt_feedback)<p class="mb-1 text-secondary"><strong>Prompt feedback:</strong> {{ $reel->prompt_feedback }}</p>@endif
                        @if ($reel->promptUpload)<p><strong>Prompt file:</strong> <a href="{{ globalAsset($reel->promptUpload->path) }}" target="_blank">Download</a></p>@endif
                        @if ($reel->thumbUpload)<p><strong>Thumbnail:</strong><br><img src="{{ globalAsset($reel->thumbUpload->path) }}" alt="Thumbnail for {{ $reel->title }}" style="max-width:200px;border-radius:8px"></p>@endif
                        @if ($reel->drive_link)
                            <p class="mb-1"><strong>Video link:</strong> <a href="{{ $reel->drive_link }}" target="_blank">{{ $reel->drive_link }}</a>
                                @if ($reel->video_approved_at) <span class="text-success">— approved</span> @else <span class="text-secondary">— pending approval</span> @endif
                            </p>
                        @endif
                        @if ($reel->video_feedback)<p class="mb-1 text-secondary"><strong>Video feedback:</strong> {{ $reel->video_feedback }}</p>@endif
                        @if ($reel->note)<p class="mt-3"><strong>Note:</strong> {{ $reel->note }}</p>@endif
                        @foreach ($data['customFields'] ?? [] as $field)
                            @php($value = ($reel->custom_fields ?? [])[$field->key] ?? null)
                            @if ($value !== null && $value !== '')
                                @if ($field->type === \App\Models\Portal\PortalReelCustomField::TYPE_FILE)
                                    @php($upload = \App\Models\Upload::find($value))
                                    @if ($upload)
                                        <p class="mb-1"><strong>{{ $field->label }}:</strong> <a href="{{ globalAsset($upload->path) }}" target="_blank">Download</a></p>
                                    @endif
                                @else
                                    <p class="mb-1"><strong>{{ $field->label }}:</strong>
                                        @if ($field->type === \App\Models\Portal\PortalReelCustomField::TYPE_CHECKBOX)
                                            {{ $value ? 'Yes' : 'No' }}
                                        @elseif (is_array($value))
                                            {{ implode(', ', $value) }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </p>
                                @endif
                            @endif
                        @endforeach
                        @if ($reel->reviewedBy)
                            <p class="text-secondary mb-0">Last reviewed by {{ $reel->reviewedBy->name }}, {{ $reel->reviewed_at?->format('d M Y, h:i A') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                @if ($reel->status === \App\Models\Portal\PortalReel::SUGGESTED && $canApprove)
                    <div class="card ot-card">
                        <div class="card-header"><h4 class="mb-0">Review suggestion</h4></div>
                        <div class="card-body">
                            <p class="text-secondary mb-3">Approve to move this forward, or discard it.</p>
                            <form action="{{ route('portal-social-reels.review', $reel->id) }}" method="post" class="mb-2">
                                @csrf
                                <input type="hidden" name="status" value="accepted">
                                <button type="submit" class="btn ot-btn-primary w-100">Approve</button>
                            </form>
                            <form action="{{ route('portal-social-reels.review', $reel->id) }}" method="post" onsubmit="return confirm('Discard this suggestion?');">
                                @csrf
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="btn btn-outline-danger w-100">Discard</button>
                            </form>
                        </div>
                    </div>
                @endif

                @if ($canAssign && $reel->status !== \App\Models\Portal\PortalReel::SUGGESTED)
                    <div class="card ot-card">
                        <div class="card-header"><h4 class="mb-0">Assign</h4></div>
                        <div class="card-body">
                            <form action="{{ route('portal-social-reels.assign', $reel->id) }}" method="post">
                                @csrf
                                <label class="form-label">Assign to</label>
                                <select name="assigned_to" id="assignStaffSelect" class="nice-select niceSelect bordered_style wide mb-2" {{ $reel->is_open_paid ? 'disabled' : '' }}>
                                    <option value="">— Select —</option>
                                    @foreach ($data['contentTeam'] as $staff)
                                        <option value="{{ $staff->id }}" {{ (int) $reel->assigned_to === $staff->id ? 'selected' : '' }}>{{ $staff->first_name }} {{ $staff->last_name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-check mb-2">
                                    <input type="checkbox" class="form-check-input" id="openPaidCheck" name="open_paid" value="1" {{ $reel->is_open_paid ? 'checked' : '' }}>
                                    <label class="form-check-label" for="openPaidCheck">Open — Paid Task (anyone can pick this up)</label>
                                </div>
                                <label class="form-label">Deadline</label>
                                <input type="date" name="deadline" class="form-control ot-input mb-2" value="{{ $reel->deadline?->format('Y-m-d') }}">
                                <button type="submit" class="btn ot-btn-primary w-100">Save</button>
                            </form>
                        </div>
                    </div>
                @endif

                @if ($isAssignee && !$isManager)
                    <div class="card ot-card mt-3">
                        <div class="card-header"><h4 class="mb-0">Submit prompt</h4></div>
                        <div class="card-body">
                            @if ($reel->prompt_approved_at)
                                <p class="text-success mb-0">Prompt approved — you can submit the video once it's ready.</p>
                            @else
                                <form action="{{ route('portal-social-reels.submit-link', $reel->id) }}" method="post">
                                    @csrf
                                    <input type="hidden" name="stage" value="prompt">
                                    <label class="form-label">Prompt link</label>
                                    <input type="url" name="link" class="form-control ot-input mb-2" value="{{ $reel->prompt_link }}" required placeholder="https://...">
                                    <button type="submit" class="btn ot-btn-primary w-100">Submit for approval</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @if ($reel->prompt_approved_at)
                        <div class="card ot-card mt-3">
                            <div class="card-header"><h4 class="mb-0">Submit video</h4></div>
                            <div class="card-body">
                                @if ($reel->video_approved_at)
                                    <p class="text-success mb-0">Video approved.</p>
                                @else
                                    <form action="{{ route('portal-social-reels.submit-link', $reel->id) }}" method="post">
                                        @csrf
                                        <input type="hidden" name="stage" value="video">
                                        <label class="form-label">Video link</label>
                                        <input type="url" name="link" class="form-control ot-input mb-2" value="{{ $reel->drive_link }}" required placeholder="https://...">
                                        <button type="submit" class="btn ot-btn-primary w-100">Submit for approval</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif

                @if ($canApprove)
                    @if ($reel->prompt_link && !$reel->prompt_approved_at)
                        <div class="card ot-card mt-3">
                            <div class="card-header"><h4 class="mb-0">Approve prompt</h4></div>
                            <div class="card-body">
                                <p class="mb-2"><a href="{{ $reel->prompt_link }}" target="_blank">{{ $reel->prompt_link }}</a></p>
                                <form action="{{ route('portal-social-reels.approve-stage', $reel->id) }}" method="post">
                                    @csrf
                                    <input type="hidden" name="stage" value="prompt">
                                    <label class="form-label">Feedback (optional)</label>
                                    <textarea name="feedback" class="form-control ot-input mb-2" rows="2">{{ $reel->prompt_feedback }}</textarea>
                                    <button type="submit" class="btn ot-btn-primary w-100">Approve prompt</button>
                                </form>
                            </div>
                        </div>
                    @endif

                    @if ($reel->drive_link && !$reel->video_approved_at)
                        <div class="card ot-card mt-3">
                            <div class="card-header"><h4 class="mb-0">Approve video</h4></div>
                            <div class="card-body">
                                <p class="mb-2"><a href="{{ $reel->drive_link }}" target="_blank">{{ $reel->drive_link }}</a></p>
                                <form action="{{ route('portal-social-reels.approve-stage', $reel->id) }}" method="post">
                                    @csrf
                                    <input type="hidden" name="stage" value="video">
                                    <label class="form-label">Feedback (optional)</label>
                                    <textarea name="feedback" class="form-control ot-input mb-2" rows="2">{{ $reel->video_feedback }}</textarea>
                                    <button type="submit" class="btn ot-btn-primary w-100">Approve video</button>
                                </form>
                            </div>
                        </div>
                    @endif

                    @if ($reel->video_approved_at)
                        <div class="card ot-card mt-3">
                            <div class="card-header"><h4 class="mb-0">Schedule upload</h4></div>
                            <div class="card-body">
                                <form action="{{ route('portal-social-reels.schedule', $reel->id) }}" method="post">
                                    @csrf
                                    <label class="form-label">Upload date</label>
                                    <input type="date" name="planned_date" class="form-control ot-input mb-2" value="{{ $reel->planned_date?->format('Y-m-d') }}" required>
                                    <button type="submit" class="btn ot-btn-primary w-100">Save date</button>
                                </form>
                            </div>
                        </div>
                    @endif
                @endif

                @if ($isManager)
                    <div class="card ot-card mt-3">
                        <div class="card-header"><h4 class="mb-0">Review</h4></div>
                        <div class="card-body">
                            <form action="{{ route('portal-social-reels.review', $reel->id) }}" method="post">
                                @csrf
                                <label class="form-label">Move to</label>
                                <select name="status" class="nice-select niceSelect bordered_style wide mb-2">
                                    @foreach (['accepted' => 'Accepted', 'production' => 'In production', 'ready' => 'Ready', 'published' => 'Published', 'rejected' => 'Rejected'] as $key => $label)
                                        <option value="{{ $key }}" {{ $reel->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <label class="form-label">Note (optional)</label>
                                <textarea name="note" class="form-control ot-input mb-2" rows="2"></textarea>
                                <button type="submit" class="btn ot-btn-primary w-100">Update</button>
                            </form>
                        </div>
                    </div>

                    <form action="{{ route('portal-social-reels.destroy', $reel->id) }}" method="post" class="mt-3" onsubmit="return confirm('Remove this reel/topic?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-secondary w-100">Remove</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @push('script')
    <script>
        (function () {
            var openPaid = document.getElementById('openPaidCheck');
            var staffSelect = document.getElementById('assignStaffSelect');
            if (!openPaid || !staffSelect) return;

            function syncDisabled() {
                staffSelect.disabled = openPaid.checked;
                if (window.jQuery) { jQuery(staffSelect).niceSelect('update'); }
            }

            // nice-select only surfaces its change event through jQuery's
            // own trigger (same issue fixed elsewhere in this app for the
            // Leave Request role toggle), but a plain checkbox is native.
            openPaid.addEventListener('change', syncDisabled);
        })();
    </script>
    @endpush
@endsection
