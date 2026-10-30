@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">Suggest Content</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('portal-social-suggest-reel.index') }}">Suggest</a></li>
                        <li class="breadcrumb-item">Add</li>
                    </ol>
                </div>
            </div>
        </div>

        @php
            $pillarOptions = optional($data['lists']->firstWhere('key', 'reel_categories'))->values ?? [];
        @endphp
        <div class="card ot-card">
            <div class="card-body">
                <form action="{{ route('portal-social-reels.store') }}" method="post">
                    @csrf
                    <input type="hidden" name="return_to" value="suggest">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Pillar</label>
                            <select name="category" id="suggestPillarSelect" class="form-control ot-input">
                                <option value="">—</option>
                                @foreach ($pillarOptions as $cat)
                                    <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Series Name</label>
                            <select name="series_name" id="suggestSeriesSelect" class="form-control ot-input">
                                <option value="">—</option>
                                @foreach ($data['series'] as $s)
                                    <option value="{{ $s->name }}" data-pillar="{{ $s->defaults['category'] ?? '' }}" {{ old('series_name') === $s->name ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Topic</label>
                            <input type="text" name="title" class="form-control ot-input" value="{{ old('title') }}" required maxlength="150">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Hook</label>
                            <input type="text" name="hook" class="form-control ot-input" value="{{ old('hook') }}">
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-lg ot-btn-primary">Add</button>
                        <a href="{{ route('portal-social-suggest-reel.index') }}" class="btn btn-lg btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        @push('script')
        <script>
            (function () {
                // Series Name only narrows once a Pillar is picked - with
                // no Pillar chosen yet, every series is still offered so
                // this never blocks someone who wants to skip it.
                var pillarSelect = document.getElementById('suggestPillarSelect');
                var seriesSelect = document.getElementById('suggestSeriesSelect');

                function applyPillarFilter() {
                    var pillar = pillarSelect.value;
                    var currentStillValid = false;
                    Array.prototype.forEach.call(seriesSelect.options, function (opt) {
                        if (!opt.value) return; // always keep the blank "—" option
                        var matches = !pillar || opt.dataset.pillar === pillar;
                        opt.hidden = !matches;
                        opt.disabled = !matches;
                        if (matches && opt.selected) currentStillValid = true;
                    });
                    if (!currentStillValid) seriesSelect.value = '';
                }

                pillarSelect.addEventListener('change', applyPillarFilter);
                applyPillarFilter();
            })();
        </script>
        @endpush
    </div>
@endsection
