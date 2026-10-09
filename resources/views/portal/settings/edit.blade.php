@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    @php($settings = $data['settings'])
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Team Portal Settings</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-body">
                <form action="{{ route('portal-settings.update') }}" method="post">
                    @csrf

                    <h4>Revision score table</h4>
                    <p class="text-secondary">Score out of 5 given for how many times a task was sent back before it was approved. The last box covers "that many or more". Changing this re-scores every already-completed task.</p>
                    <div class="row g-2 mb-4">
                        @foreach ($settings->revision_scores as $i => $score)
                            <div class="col-auto">
                                <label class="form-label">{{ $i }} revision{{ $i == 1 ? '' : 's' }}{{ $i == count($settings->revision_scores) - 1 ? '+' : '' }}</label>
                                <input type="number" name="revision_scores[]" class="ot-input" min="0" max="5" value="{{ $score }}" style="width:90px">
                            </div>
                        @endforeach
                    </div>

                    <h4>Task categories</h4>
                    <p class="text-secondary">One per line.</p>
                    <textarea data-list-for="categories" class="ot-input mb-2" rows="5">{{ implode("\n", $settings->categories) }}</textarea>
                    <div id="categoryInputs">
                        @foreach ($settings->categories as $cat)
                            <input type="hidden" name="categories[]" value="{{ $cat }}">
                        @endforeach
                    </div>

                    <h4 class="mt-4">Work log — day template</h4>
                    <p class="text-secondary">Used to pre-fill each employee's daily work log with one-hour rows.</p>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Day starts at</label>
                            <input type="time" name="day_start" class="ot-input" value="{{ $settings->day_start }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Work hours per day</label>
                            <input type="number" step="0.5" min="1" name="work_day_hours" class="ot-input" value="{{ $settings->work_day_hours }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Lunch after (hours worked)</label>
                            <input type="number" step="0.5" min="0" name="lunch_after_hours" class="ot-input" value="{{ $settings->lunch_after_hours }}">
                        </div>
                    </div>

                    <h4>Standard activities</h4>
                    <p class="text-secondary">One per line — shown in the work log dropdown alongside an employee's assigned tasks.</p>
                    <textarea data-list-for="activities" class="ot-input mb-2" rows="5">{{ implode("\n", $settings->activities ?: \App\Models\Portal\PortalSetting::DEFAULT_ACTIVITIES) }}</textarea>
                    <div id="activitiesInputs">
                        @foreach ($settings->activities ?: \App\Models\Portal\PortalSetting::DEFAULT_ACTIVITIES as $activity)
                            <input type="hidden" name="activities[]" value="{{ $activity }}">
                        @endforeach
                    </div>

                    <button type="submit" class="btn btn-lg ot-btn-primary mt-3">Save Settings</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Keep each list's hidden [] inputs in sync with its textarea so the
        // backend always receives a clean array, one item per line.
        document.querySelectorAll('textarea[data-list-for]').forEach(function (textarea) {
            var name = textarea.getAttribute('data-list-for');
            var container = document.getElementById(name === 'categories' ? 'categoryInputs' : 'activitiesInputs');
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
