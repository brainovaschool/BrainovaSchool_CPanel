@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">Social Board</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Reels</li>
                    </ol>
                </div>
                @if ($data['canManage'])
                    <div class="col-sm-4 text-sm-end">
                        <a href="{{ route('portal-social-reels.create') }}" class="btn ot-btn-primary">+ Add Reel/Topic</a>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12 p-0">
            <form action="{{ route('portal-social-reels.index') }}" method="get">
                <div class="card ot-card mb-24 d-flex flex-row align-items-center gap-3 flex-wrap" style="padding:1rem 1.25rem">
                    <select name="status" class="nice-select niceSelect bordered_style">
                        <option value="">All statuses</option>
                        @foreach (['suggested', 'accepted', 'production', 'ready', 'published', 'rejected'] as $s)
                            <option value="{{ $s }}" {{ ($data['filters']['status'] ?? '') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    <select name="category" class="nice-select niceSelect bordered_style">
                        <option value="">All categories</option>
                        @foreach ($data['settings']->reel_categories ?: \App\Models\Portal\PortalSetting::DEFAULT_REEL_CATEGORIES as $cat)
                            <option value="{{ $cat }}" {{ ($data['filters']['category'] ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                    <select name="format" class="nice-select niceSelect bordered_style">
                        <option value="">All formats</option>
                        @foreach (['Reel', 'Carousel', 'Post'] as $f)
                            <option value="{{ $f }}" {{ ($data['filters']['format'] ?? '') === $f ? 'selected' : '' }}>{{ $f }}</option>
                        @endforeach
                    </select>
                    <button class="btn ot-btn-primary" type="submit">{{ ___('common.Search') }}</button>
                </div>
            </form>
        </div>

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                    <table class="table table-bordered class-table">
                        <thead class="thead"><tr><th>Title</th><th>Format</th><th>Category</th><th>Planned date</th><th>Status</th><th class="action">{{ ___('common.action') }}</th></tr></thead>
                        <tbody class="tbody">
                            @forelse ($data['reels'] as $reel)
                                <tr>
                                    <td>{{ $reel->title }}</td>
                                    <td>{{ $reel->format }}</td>
                                    <td>{{ $reel->category ?: '—' }}</td>
                                    <td>{{ $reel->planned_date?->format('d M Y') ?: '—' }}</td>
                                    <td><span class="badge-basic-info-text">{{ ucfirst($reel->status) }}</span></td>
                                    <td class="action"><a href="{{ route('portal-social-reels.show', $reel->id) }}" class="btn btn-sm ot-btn-primary">Open</a></td>
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
@endsection
