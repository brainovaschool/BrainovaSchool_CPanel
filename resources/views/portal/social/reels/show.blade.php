@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    @php($reel = $data['reel'])
    <div class="page-content">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">{{ $reel->title }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('portal-social-reels.index') }}">Reels</a></li>
                        <li class="breadcrumb-item">{{ $reel->title }}</li>
                    </ol>
                </div>
                @if ($data['canManage'])
                    <div class="col-sm-4 text-sm-end">
                        <a href="{{ route('portal-social-reels.edit', $reel->id) }}" class="btn btn-outline-secondary">Edit</a>
                    </div>
                @endif
            </div>
        </div>

        <div class="row gy-4">
            <div class="col-lg-8">
                <div class="card ot-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Details</h4>
                        <span class="badge-basic-info-text">{{ ucfirst($reel->status) }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6"><strong>Format:</strong> {{ $reel->format }}</div>
                            <div class="col-md-6"><strong>Category:</strong> {{ $reel->category ?: '—' }}</div>
                            <div class="col-md-6"><strong>Planned date:</strong> {{ $reel->planned_date?->format('d M Y') ?: '—' }}</div>
                            <div class="col-md-6"><strong>Suggested by:</strong> {{ optional($reel->suggestedBy)->name }}</div>
                        </div>
                        @if ($reel->hook)<p class="mt-3"><strong>Hook:</strong> {{ $reel->hook }}</p>@endif
                        @if ($reel->drive_link)<p><strong>Drive link:</strong> <a href="{{ $reel->drive_link }}" target="_blank">{{ $reel->drive_link }}</a></p>@endif
                        @if ($reel->prompt_text)<p><strong>Prompt:</strong><br>{{ $reel->prompt_text }}</p>@endif
                        @if ($reel->prompt_link)<p><strong>Prompt link:</strong> <a href="{{ $reel->prompt_link }}" target="_blank">{{ $reel->prompt_link }}</a></p>@endif
                        @if ($reel->promptUpload)<p><strong>Prompt file:</strong> <a href="{{ globalAsset($reel->promptUpload->path) }}" target="_blank">Download</a></p>@endif
                        @if ($reel->thumbUpload)<p><strong>Thumbnail:</strong><br><img src="{{ globalAsset($reel->thumbUpload->path) }}" alt="Thumbnail for {{ $reel->title }}" style="max-width:200px;border-radius:8px"></p>@endif
                        @if ($reel->note)<p class="mt-3"><strong>Note:</strong> {{ $reel->note }}</p>@endif
                        @if ($reel->reviewedBy)
                            <p class="text-secondary mb-0">Last reviewed by {{ $reel->reviewedBy->name }}, {{ $reel->reviewed_at?->format('d M Y, h:i A') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            @if ($data['canManage'])
                <div class="col-lg-4">
                    <div class="card ot-card">
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
                                <textarea name="note" class="ot-input mb-2" rows="2"></textarea>
                                <button type="submit" class="btn ot-btn-primary w-100">Update</button>
                            </form>
                        </div>
                    </div>

                    <form action="{{ route('portal-social-reels.destroy', $reel->id) }}" method="post" class="mt-3" onsubmit="return confirm('Remove this reel/topic?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-secondary w-100">Remove</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
