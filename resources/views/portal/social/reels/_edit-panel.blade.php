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

@if ($data['canApprove'])
    @php($isSuggested = $reel->status === \App\Models\Portal\PortalReel::SUGGESTED)
    @php($promptCheckable = $reel->prompt_link && !$reel->prompt_approved_at)
    @php($canvaCheckable = $reel->canva_link && !$reel->canva_approved_at)
    @php($videoCheckable = $reel->drive_link && !$reel->video_approved_at)
    <div class="card ot-card mb-3">
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
@endif

<div class="card ot-card">
    <div class="card-body">
        <form action="{{ route('portal-social-reels.update', $reel->id) }}" method="post" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('portal.social.reels._form', ['settings' => $data['settings'], 'reel' => $reel, 'customFields' => $data['customFields'], 'isManager' => $data['isManager'], 'series' => $data['series']])
            <div class="mt-3">
                <button type="submit" class="btn btn-lg ot-btn-primary">Save</button>
                <a href="{{ route('portal-social-reels.index') }}" class="btn btn-lg btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
