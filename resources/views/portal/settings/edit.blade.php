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
                    <textarea name="categories_text" class="ot-input mb-2" rows="5">{{ implode("\n", $settings->categories) }}</textarea>
                    <div id="categoryInputs">
                        @foreach ($settings->categories as $cat)
                            <input type="hidden" name="categories[]" value="{{ $cat }}">
                        @endforeach
                    </div>

                    <button type="submit" class="btn btn-lg ot-btn-primary">Save Settings</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Keep the hidden categories[] inputs in sync with the textarea so the
        // backend always receives a clean array, one category per line.
        document.querySelector('textarea[name="categories_text"]').addEventListener('input', function () {
            var container = document.getElementById('categoryInputs');
            container.innerHTML = '';
            this.value.split("\n").map(function (v) { return v.trim(); }).filter(Boolean).forEach(function (v) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'categories[]';
                input.value = v;
                container.appendChild(input);
            });
        });
    </script>
@endsection
