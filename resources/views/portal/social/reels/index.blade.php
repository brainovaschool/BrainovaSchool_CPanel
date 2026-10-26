@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <h4 class="bradecrumb-title mb-1">Reels</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Reels</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="col-12 p-0">
            <form action="{{ route('portal-social-reels.index') }}" method="get">
                <div class="card ot-card bn-reels-filters mb-24 d-flex flex-row align-items-center gap-3 flex-wrap" style="padding:1rem 1.25rem">
                    <select name="status" class="nice-select niceSelect bordered_style">
                        <option value="">All statuses</option>
                        @foreach (['suggested', 'accepted', 'production', 'ready', 'published', 'rejected'] as $s)
                            <option value="{{ $s }}" {{ ($data['filters']['status'] ?? '') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    <select name="category" class="nice-select niceSelect bordered_style">
                        <option value="">All pillars</option>
                        @foreach ($data['settings']->reel_categories ?: \App\Models\Portal\PortalSetting::DEFAULT_REEL_CATEGORIES as $cat)
                            <option value="{{ $cat }}" {{ ($data['filters']['category'] ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                    <select name="format" class="nice-select niceSelect bordered_style">
                        <option value="">All formats</option>
                        @foreach ($data['settings']->reel_formats ?: \App\Models\Portal\PortalSetting::DEFAULT_REEL_FORMATS as $f)
                            <option value="{{ $f }}" {{ ($data['filters']['format'] ?? '') === $f ? 'selected' : '' }}>{{ $f }}</option>
                        @endforeach
                    </select>
                    @if ($data['series']->count())
                        <select name="series_name" class="nice-select niceSelect bordered_style">
                            <option value="">All series</option>
                            @foreach ($data['series'] as $s)
                                <option value="{{ $s->name }}" {{ ($data['filters']['series_name'] ?? '') === $s->name ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <button class="btn ot-btn-primary" type="submit">{{ ___('common.Search') }}</button>
                </div>
            </form>
        </div>

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                    <table class="ot-basic-table ot-table-bg bn-reels-table">
                        <colgroup>
                            <col style="width:8%">
                            <col style="width:15%">
                            <col style="width:17%">
                            <col style="width:10%">
                            <col style="width:12%">
                            <col style="width:8%">
                            <col style="width:8%">
                            <col style="width:8%">
                            <col style="width:14%">
                        </colgroup>
                        <thead class="thead">
                            <tr>
                                <th>Series</th>
                                <th>Topic</th>
                                <th>Hook</th>
                                <th>Suggested by</th>
                                <th>Status</th>
                                <th>Deadline</th>
                                <th>Progress</th>
                                <th>Uploaded date</th>
                                <th class="action">{{ ___('common.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="tbody">
                            @forelse ($data['reels'] as $reel)
                                @php($staff = optional($reel->suggestedBy)->staff)
                                <tr>
                                    <td>
                                        @if ($reel->series_name)
                                            <span class="bn-series-tag">{{ $reel->series_name }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('portal-social-reels.show', $reel->id) }}" class="bn-reel-title">{{ $reel->title }}</a>
                                    </td>
                                    <td>
                                        @if ($reel->hook)
                                            <span class="bn-reel-hook" title="{{ $reel->hook }}">{{ \Illuminate\Support\Str::limit($reel->hook, 60) }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        {{ optional($reel->suggestedBy)->name ?: '—' }}
                                        @if (optional($staff?->designation)->name)
                                            <div class="bn-reel-sub">{{ $staff->designation->name }}</div>
                                        @endif
                                    </td>
                                    <td><span class="{{ $reel->statusBadgeClass() }}">{{ $reel->statusLabel() }}</span></td>
                                    <td>{{ $reel->deadline?->format('d M Y') ?: '—' }}</td>
                                    <td>
                                        <span class="{{ $reel->promptBadgeClass() }} bn-progress-dot" title="Prompt: {{ $reel->prompt_approved_at ? 'approved' : ($reel->prompt_link ? 'pending approval' : 'not submitted') }}">P</span>
                                        <span class="{{ $reel->videoBadgeClass() }} bn-progress-dot" title="Video: {{ $reel->video_approved_at ? 'approved' : ($reel->drive_link ? 'pending approval' : 'not submitted') }}">V</span>
                                    </td>
                                    <td>{{ $reel->planned_date?->format('d M Y') ?: '—' }}</td>
                                    <td class="action bn-reel-actions">
                                        @php($needsAssignment = $reel->status !== \App\Models\Portal\PortalReel::SUGGESTED && !$reel->is_open_paid && !$reel->assigned_to)
                                        @if ($reel->status === \App\Models\Portal\PortalReel::SUGGESTED || $needsAssignment)
                                            <a href="{{ route('portal-social-reels.show', $reel->id) }}" class="btn btn-sm ot-btn-primary">Open</a>
                                        @else
                                            <a href="{{ route('portal-social-reels.setup', $reel->id) }}" class="btn btn-sm ot-btn-primary">Open</a>
                                        @endif
                                        @if ($data['canManage'])
                                            <form action="{{ route('portal-social-reels.destroy', $reel->id) }}" method="post" class="d-inline" onsubmit="return confirm('Remove this reel/topic?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">{{ ___('common.delete') }}</button>
                                            </form>
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
    </div>

    @push('css')
    <style>
        .bn-reel-title { font-weight: 600; display: block; word-break: break-word; }
        .bn-series-tag { font-family: "Lexend", sans-serif; font-weight: 600; font-size: 11px; border-radius: 14px; padding: 3px 10px; background: rgba(var(--bn-turquoise-rgb), .14); color: var(--bn-turquoise-text, #007585); white-space: normal; display: inline-block; line-height: 1.3; }
        .dark-theme .bn-series-tag { background: rgba(0, 151, 178, .22); color: var(--bn-turquoise-light); }
        .bn-reel-hook { color: var(--bn-muted, #8a8fa3); font-size: .85rem; font-style: italic; word-break: break-word; }
        .bn-reel-sub { color: var(--bn-muted, #8a8fa3); font-size: .78rem; margin-top: .15rem; }
        .bn-progress-dot { display: inline-flex; align-items: center; justify-content: center; width: 1.5rem; height: 1.5rem; border-radius: 50%; font-size: .72rem; font-weight: 700; margin-right: .35rem; }
        .bn-reel-actions { display: flex; flex-wrap: wrap; align-items: flex-start; gap: .4rem; }
        .bn-reels-table { table-layout: fixed; width: 100%; }
        .bn-reels-table td { vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; }
    </style>
    @endpush
@endsection
