{{-- Bare fragment - no @extends. Fetched and injected into the Reels
     table's top bar (see index.blade.php) when exactly one row is
     checked, so none of this page's own JS (nice-select/select2 init,
     series-default autofill, file-name mirroring) runs on it - every
     field here still works as a plain native input/select, it just
     isn't visually styled the same as the rest of the table's forms. --}}
@php($reel = $data['reel'])
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h5 class="mb-0">
        Editing: {{ $reel->title }}
        @if ($reel->code)
            <span class="badge-basic-info-text">{{ $reel->code }}</span>
        @endif
    </h5>
    @unless ($reel->is_document_track)
        <a href="{{ route('portal-social-reels.setup', $reel->id) }}" class="btn btn-sm ot-btn-primary">Reel Setup</a>
    @endunless
</div>

@php($isSuggested = $reel->status === \App\Models\Portal\PortalReel::SUGGESTED)
@php($promptCheckable = $reel->prompt_link && !$reel->prompt_approved_at)
@php($canvaCheckable = $reel->canva_link && !$reel->canva_approved_at)
@php($videoCheckable = $reel->drive_link && !$reel->video_approved_at)

{{-- Approve's own small forms (each submits the moment its checkbox is
     ticked) can't nest inside the Save form below - browsers silently
     drop a <form> nested in another, which would make those checkboxes
     submit the wrong thing entirely. The Document-track/Prompt fields
     DO belong to the Save form though, so they use its form="" attribute
     to associate with it by id without being its DOM descendants - this
     row sits outside any form, Approve keeps its own, and the Save form
     (below, holding the rest of _form.blade.php's fields) still submits
     both document-track/prompt file/prompt link correctly from here. --}}
<div class="row g-3 mb-3">
    @if ($data['canApprove'])
        <div class="col-md-4">
            <div class="card ot-card h-100 mb-0">
                <div class="card-header"><h4 class="mb-0">Approve</h4></div>
                <div class="card-body d-flex flex-column gap-2">
                    <form action="{{ route('portal-social-reels.review', $reel->id) }}" method="post" class="d-flex align-items-center gap-2">
                        @csrf
                        <input type="hidden" name="status" value="accepted">
                        <input type="checkbox" class="form-check-input" {{ $isSuggested ? '' : 'checked disabled' }} onchange="this.form.submit()">
                        <label class="mb-0">Topic</label>
                    </form>
                    @unless ($reel->is_document_track)
                        <form action="{{ route('portal-social-reels.approve-stage', $reel->id) }}" method="post" class="d-flex align-items-center gap-2">
                            @csrf
                            <input type="hidden" name="stage" value="prompt">
                            <input type="checkbox" class="form-check-input" {{ $reel->prompt_approved_at ? 'checked' : '' }} {{ $promptCheckable ? '' : 'disabled' }} onchange="this.form.submit()">
                            <label class="mb-0">Prompt {{ $reel->prompt_link ? '' : '(no link submitted yet)' }}</label>
                        </form>
                        <form action="{{ route('portal-social-reels.approve-stage', $reel->id) }}" method="post" class="d-flex align-items-center gap-2">
                            @csrf
                            <input type="hidden" name="stage" value="canva">
                            <input type="checkbox" class="form-check-input" {{ $reel->canva_approved_at ? 'checked' : '' }} {{ $canvaCheckable ? '' : 'disabled' }} onchange="this.form.submit()">
                            <label class="mb-0">Canva {{ $reel->canva_link ? '' : '(no link submitted yet)' }}</label>
                        </form>
                    @endunless
                    <form action="{{ route('portal-social-reels.approve-stage', $reel->id) }}" method="post" class="d-flex align-items-center gap-2">
                        @csrf
                        <input type="hidden" name="stage" value="video">
                        <input type="checkbox" class="form-check-input" {{ $reel->video_approved_at ? 'checked' : '' }} {{ $videoCheckable ? '' : 'disabled' }} onchange="this.form.submit()">
                        <label class="mb-0">Video {{ $reel->drive_link ? '' : '(no link submitted yet)' }}</label>
                    </form>
                    @if ($isSuggested)
                        <form action="{{ route('portal-social-reels.review', $reel->id) }}" method="post" onsubmit="return confirm('Discard this suggestion?');">
                            @csrf
                            <input type="hidden" name="status" value="rejected">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Discard suggestion</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif
    <div class="col-md-{{ $data['canApprove'] ? 8 : 12 }}">
        <div class="card ot-card bg-light-subtle h-100 mb-0">
            <div class="card-body">
                <div class="form-check mb-2 p-2" style="background:#fff3cd; border:1px solid #ffe69c; border-radius:8px; padding-left:2.5em !important;">
                    <input type="checkbox" class="form-check-input" id="isDocumentTrack" name="is_document_track" value="1" form="reelUpdateForm" style="width:22px; height:22px; border:2px solid #b45309; margin-left:-2em;" {{ old('is_document_track', $reel->is_document_track ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="isDocumentTrack" style="cursor:pointer;"><strong>☑ Document track</strong> — skip Reel Studio. The assignee downloads the prompt file/link below plus this reel's Characters (if set), creates the clips themselves (e.g. in Google Flow), and submits a video link directly. Only the Video stage needs your approval.</label>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Prompt file (optional)</label>
                        <div class="ot_fileUploader left-side mb-1">
                            <input class="form-control ot-input bn-file-placeholder" type="text" placeholder="No file chosen" readonly>
                            <button class="primary-btn-small-input" type="button">
                                <label class="btn btn-lg ot-btn-primary" for="promptFileInput">Browse</label>
                                <input type="file" class="d-none bn-file-input" id="promptFileInput" name="prompt_file" form="reelUpdateForm">
                            </button>
                        </div>
                        @if ($reel->promptUpload)
                            <p class="text-secondary mt-1 mb-0"><a href="{{ globalAsset($reel->promptUpload->path) }}" target="_blank">Current file</a> — uploading a new one replaces it.</p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Prompt link (optional)</label>
                        <input type="text" name="prompt_link" class="form-control ot-input" value="{{ old('prompt_link', $reel->prompt_link ?? '') }}" placeholder="Used instead of/alongside the file, if you'd rather share a link" form="reelUpdateForm">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card ot-card">
    <div class="card-body">
        <form id="reelUpdateForm" action="{{ route('portal-social-reels.update', $reel->id) }}" method="post" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('portal.social.reels._form', ['settings' => $data['settings'], 'reel' => $reel, 'customFields' => $data['customFields'], 'isManager' => $data['isManager'], 'series' => $data['series'], 'hideDocumentTrackCard' => true])
            <div class="mt-3">
                <button type="submit" class="btn btn-lg ot-btn-primary">Save</button>
                <a href="{{ route('portal-social-reels.index') }}" class="btn btn-lg btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
