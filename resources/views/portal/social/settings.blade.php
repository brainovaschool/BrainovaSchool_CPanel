@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    @php($settings = $data['settings'])
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Social Board Settings</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-body">
                <form action="{{ route('portal-social-settings.update') }}" method="post">
                    @csrf

                    <h4>Platforms</h4>
                    <p class="text-secondary">One per line.</p>
                    <textarea data-list-for="social_platforms" class="ot-input mb-2" rows="4">{{ implode("\n", $settings->social_platforms ?: \App\Models\Portal\PortalSetting::DEFAULT_PLATFORMS) }}</textarea>
                    <div id="social_platformsInputs">
                        @foreach ($settings->social_platforms ?: \App\Models\Portal\PortalSetting::DEFAULT_PLATFORMS as $v)
                            <input type="hidden" name="social_platforms[]" value="{{ $v }}">
                        @endforeach
                    </div>

                    <h4 class="mt-4">Tracked numbers</h4>
                    <p class="text-secondary">One per line — e.g. Views, Followers, Likes.</p>
                    <textarea data-list-for="social_metrics" class="ot-input mb-2" rows="4">{{ implode("\n", $settings->social_metrics ?: \App\Models\Portal\PortalSetting::DEFAULT_SOCIAL_METRICS) }}</textarea>
                    <div id="social_metricsInputs">
                        @foreach ($settings->social_metrics ?: \App\Models\Portal\PortalSetting::DEFAULT_SOCIAL_METRICS as $v)
                            <input type="hidden" name="social_metrics[]" value="{{ $v }}">
                        @endforeach
                    </div>

                    <h4 class="mt-4">Reel/topic categories</h4>
                    <p class="text-secondary">One per line.</p>
                    <textarea data-list-for="reel_categories" class="ot-input mb-2" rows="4">{{ implode("\n", $settings->reel_categories ?: \App\Models\Portal\PortalSetting::DEFAULT_REEL_CATEGORIES) }}</textarea>
                    <div id="reel_categoriesInputs">
                        @foreach ($settings->reel_categories ?: \App\Models\Portal\PortalSetting::DEFAULT_REEL_CATEGORIES as $v)
                            <input type="hidden" name="reel_categories[]" value="{{ $v }}">
                        @endforeach
                    </div>

                    <button type="submit" class="btn btn-lg ot-btn-primary mt-3">Save Settings</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('textarea[data-list-for]').forEach(function (textarea) {
            var name = textarea.getAttribute('data-list-for');
            var container = document.getElementById(name + 'Inputs');
            textarea.addEventListener('input', function () {
                container.innerHTML = '';
                this.value.split("\n").map(function (v) { return v.trim(); }).filter(Boolean).forEach(function (v) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name + '[]';
                    input.value = v;
                    container.appendChild(input);
                });
            });
        });
    </script>
@endsection
