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
                        <li class="breadcrumb-item">Audience Numbers</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="col-12 p-0">
            <form action="{{ route('portal-social-audience.index') }}" method="get">
                <div class="card ot-card mb-24 d-flex flex-row align-items-center gap-3" style="padding:1rem 1.25rem">
                    <input type="month" name="month" class="ot-input" value="{{ $data['month'] }}">
                    <button class="btn ot-btn-primary" type="submit">{{ ___('common.Search') }}</button>
                </div>
            </form>
        </div>

        <div class="card ot-card mb-24">
            <div class="card-header"><h4 class="mb-0">Log today's numbers</h4></div>
            <div class="card-body">
                <form action="{{ route('portal-social-audience.store') }}" method="post" class="d-flex gap-2 flex-wrap align-items-end">
                    @csrf
                    <div>
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="ot-input" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div>
                        <label class="form-label">Platform</label>
                        <select name="platform" class="nice-select niceSelect bordered_style wide">
                            @foreach ($data['settings']->social_platforms ?: \App\Models\Portal\PortalSetting::DEFAULT_PLATFORMS as $p)
                                <option value="{{ $p }}">{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                    @foreach ($data['settings']->social_metrics ?: \App\Models\Portal\PortalSetting::DEFAULT_SOCIAL_METRICS as $metric)
                        <div>
                            <label class="form-label">{{ $metric }}</label>
                            <input type="hidden" name="metric_name[]" value="{{ $metric }}">
                            <input type="number" min="0" name="metric_value[]" class="ot-input" style="max-width:110px">
                        </div>
                    @endforeach
                    <button type="submit" class="btn ot-btn-primary">Save</button>
                </form>
            </div>
        </div>

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-header"><h4 class="mb-0">{{ \Carbon\Carbon::parse($data['month'] . '-01')->format('F Y') }}</h4></div>
                <div class="card-body">
                    <table class="table table-bordered class-table">
                        <thead class="thead"><tr><th>Date</th><th>Platform</th><th>Numbers</th></tr></thead>
                        <tbody class="tbody">
                            @forelse ($data['metrics'] as $m)
                                <tr>
                                    <td>{{ $m->date->format('d M Y') }}</td>
                                    <td>{{ $m->platform }}</td>
                                    <td>
                                        @foreach ($m->values as $name => $value)
                                            <span class="badge-basic-info-text">{{ $name }}: {{ $value }}</span>
                                        @endforeach
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
@endsection
