@extends('backend.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    <div class="page-content">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">{{ $data['title'] }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <p class="text-secondary mb-24">
            Each of these can be switched on for every student, or kept limited to a hand-picked list of testers — independently of the others. A student with Learning Island access but not Avatar, for example, still gets the full island experience; they just can't change how they look.
        </p>

        @foreach ($data['features'] as $key => $feature)
            <div class="card mb-24" style="border-color:{{ $feature['visible_to_all'] ? '' : '#f3d9a4' }};">
                <div class="card-header"><h5 class="mb-0">{{ $feature['label'] }} — who can see it</h5></div>
                <div class="card-body">
                    @unless ($feature['visible_to_all'])
                        <p class="mb-3" style="font-size:.85rem; color:#92400e; background:#fbf0dd; border:1px solid #f3d9a4; border-radius:8px; padding:8px 12px;">
                            <i class="fa-solid fa-eye-slash"></i> Currently hidden from every student except the ones checked below.
                        </p>
                    @endunless
                    <form action="{{ route('student-feature-access.update', $key) }}" method="post">
                        @csrf
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="visibleToAll_{{ $key }}" name="visible_to_all" value="1" {{ $feature['visible_to_all'] ? 'checked' : '' }}>
                            <label class="form-check-label" for="visibleToAll_{{ $key }}">Show {{ $feature['label'] }} to every student</label>
                        </div>

                        <div id="testerList_{{ $key }}" style="{{ $feature['visible_to_all'] ? 'display:none;' : '' }}">
                            <label class="form-label">Testers — can always see it, even while it's off for everyone else</label>
                            @if ($data['students']->isEmpty())
                                <p class="text-secondary" style="font-size:.85rem;">No students exist yet.</p>
                            @else
                                <div style="max-height:260px; overflow-y:auto; border:1px solid #e7e9ee; border-radius:10px; padding:10px 14px;">
                                    @foreach ($data['students'] as $student)
                                        @php
                                            $scs = $student->session_class_student;
                                            $where = $scs ? trim(optional($scs->class)->name . ' ' . optional($scs->section)->name) : '';
                                        @endphp
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="tester_ids[]" value="{{ $student->id }}"
                                                id="tester_{{ $key }}_{{ $student->id }}" {{ in_array($student->id, $feature['tester_ids'], true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="tester_{{ $key }}_{{ $student->id }}">
                                                {{ $student->first_name }} {{ $student->last_name }}
                                                @if ($where)
                                                    <span class="text-secondary">— {{ $where }}</span>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <button class="btn btn-lg ot-btn-primary mt-3">{{ ___('common.save') }}</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    @push('script')
    <script>
    (function () {
        document.querySelectorAll('[id^="visibleToAll_"]').forEach(function (toggle) {
            var key  = toggle.id.replace('visibleToAll_', '');
            var list = document.getElementById('testerList_' + key);
            if (!list) return;
            toggle.addEventListener('change', function () {
                list.style.display = toggle.checked ? 'none' : '';
            });
        });
    })();
    </script>
    @endpush
@endsection
