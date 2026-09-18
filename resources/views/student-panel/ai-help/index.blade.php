@extends('student-panel.partials.master')

@section('title')
    {{ @$data['title'] }}
@endsection

@section('content')
    <div class="page-content">
        @if (dashboard_feature_enabled('student', 'ai_ask_helper'))
        <div class="card ot-card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-4">
                    @if ($data['mascot'])
                        <img src="{{ $data['mascot'] }}" alt="mascot" style="height:90px;">
                    @endif
                    <div>
                        <h4 class="mb-1">{{ $data['title'] }}</h4>
                        <p class="text-secondary mb-0">Stuck on something? Ask below and get a simple, step-by-step explanation.</p>
                    </div>
                </div>

                <form id="aiHelpStudentForm">
                    @csrf
                    <label class="form-label">{{ $data['question_label'] }}</label>
                    <textarea id="aiHelpStudentQuestion" class="form-control ot-textarea mb-3" rows="4"
                        placeholder="e.g. I don't understand how to add fractions with different denominators" required></textarea>

                    <button type="submit" id="aiHelpStudentBtn" class="btn ot-btn-primary">{{ $data['button_text'] }}</button>

                    <div id="aiHelpStudentAnswer" class="mt-4 p-3 rounded" style="display:none;background:#eaf2ff;color:#1e293b;line-height:1.6;"></div>
                </form>
            </div>
        </div>
        @endif

        @if (dashboard_feature_enabled('student', 'teach_kea'))
        <div class="card ot-card mt-4">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-4">
                    @if ($data['mascot'])
                        <img src="{{ $data['mascot'] }}" alt="Kea" style="height:70px;border-radius:50%;">
                    @endif
                    <div>
                        <h4 class="mb-1">Teach Kea</h4>
                        <p class="text-secondary mb-0">Pick a skill and explain it in your own words — teaching it to Kea is one of the best ways to find out if you really know it.</p>
                    </div>
                </div>

                @if ($data['kea_skills']->isEmpty())
                    <p class="text-secondary mb-0">No skills are set up for your class yet — check back later.</p>
                @else
                    <form id="teachKeaForm">
                        @csrf
                        <label class="form-label">What are you explaining?</label>
                        <select id="teachKeaSkill" class="form-select mb-3" required>
                            <option value="" disabled selected>Choose a skill…</option>
                            @foreach ($data['kea_skills'] as $skill)
                                <option value="{{ $skill->id }}">{{ $skill->title }}</option>
                            @endforeach
                        </select>

                        <label class="form-label">Explain it to Kea in your own words</label>
                        <textarea id="teachKeaExplanation" class="form-control ot-textarea mb-3" rows="4"
                            placeholder="e.g. To add fractions with different denominators, you first..." required></textarea>

                        <button type="submit" id="teachKeaBtn" class="btn ot-btn-primary">Teach Kea</button>

                        <div id="teachKeaAnswer" class="mt-4 p-3 rounded" style="display:none;line-height:1.6;"></div>
                    </form>
                @endif
            </div>
        </div>
        @endif

        @if (dashboard_feature_enabled('student', 'ai_fact_checker'))
        <div class="card ot-card mt-4">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-4">
                    @php($brainbotImage = setting('ai_helper_teacher_mascot') ? globalAsset(setting('ai_helper_teacher_mascot')) : null)
                    @if ($brainbotImage)
                        <img src="{{ $brainbotImage }}" alt="Brainbot" style="height:70px;border-radius:50%;">
                    @endif
                    <div>
                        <h4 class="mb-1">AI Fact-Checker</h4>
                        <p class="text-secondary mb-0">Read or heard something and not sure it's true? Paste it below — Brainbot checks it instead of you just trusting it.</p>
                    </div>
                </div>

                <form id="factCheckForm">
                    @csrf
                    <label class="form-label">What's the claim?</label>
                    <textarea id="factCheckClaim" class="form-control ot-textarea mb-3" rows="3"
                        placeholder="e.g. Goldfish only have a 3-second memory" required></textarea>

                    <button type="submit" id="factCheckBtn" class="btn ot-btn-primary">Check It</button>

                    <div id="factCheckAnswer" class="mt-4 p-3 rounded" style="display:none;line-height:1.6;"></div>
                </form>
            </div>
        </div>
        @endif
    </div>

    <script>
        (function () {
            var form     = document.getElementById('aiHelpStudentForm');
            var question = document.getElementById('aiHelpStudentQuestion');
            var btn      = document.getElementById('aiHelpStudentBtn');
            var answer   = document.getElementById('aiHelpStudentAnswer');

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var token = form.querySelector('input[name="_token"]').value;

                btn.disabled = true;
                answer.style.display = 'block';
                answer.style.background = '#eaf2ff';
                answer.style.color = '#1e40af';
                answer.textContent = 'Thinking…';

                fetch('{{ route('student-panel-ai-help.ask') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ question: question.value })
                })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data.ok) {
                        answer.style.background = '#e8f7ef';
                        answer.style.color = '#1e293b';
                        answer.textContent = data.text;
                    } else {
                        answer.style.background = '#fff1f2';
                        answer.style.color = '#b91c1c';
                        answer.textContent = data.message || 'Something went wrong. Please try again.';
                    }
                })
                .catch(function () {
                    answer.style.background = '#fff1f2';
                    answer.style.color = '#b91c1c';
                    answer.textContent = 'Something went wrong. Please try again.';
                })
                .finally(function () {
                    btn.disabled = false;
                });
            });
        })();

        (function () {
            var form        = document.getElementById('teachKeaForm');
            if (!form) return;

            var skillSelect  = document.getElementById('teachKeaSkill');
            var explanation  = document.getElementById('teachKeaExplanation');
            var btn          = document.getElementById('teachKeaBtn');
            var answer       = document.getElementById('teachKeaAnswer');

            function escapeHtml(text) {
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var token = form.querySelector('input[name="_token"]').value;

                btn.disabled = true;
                answer.style.display = 'block';
                answer.style.background = '#eaf2ff';
                answer.style.color = '#1e40af';
                answer.textContent = 'Kea is listening…';

                fetch('{{ route('student-panel-ai-help.teach-kea') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        skill_id: skillSelect.value,
                        explanation: explanation.value
                    })
                })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data.ok) {
                        if (data.understood) {
                            answer.style.background = '#e8f7ef';
                            answer.style.color = '#1e293b';
                            answer.innerHTML = '<strong>🐦 Kea\'s got it! (+30 XP)</strong><br>' + escapeHtml(data.feedback);
                        } else {
                            answer.style.background = '#fff8e6';
                            answer.style.color = '#1e293b';
                            answer.innerHTML = '<strong>🐦 Kea (+10 XP for trying)</strong><br>' + escapeHtml(data.feedback);
                        }
                    } else {
                        answer.style.background = '#fff1f2';
                        answer.style.color = '#b91c1c';
                        answer.textContent = data.message || 'Something went wrong. Please try again.';
                    }
                })
                .catch(function () {
                    answer.style.background = '#fff1f2';
                    answer.style.color = '#b91c1c';
                    answer.textContent = 'Something went wrong. Please try again.';
                })
                .finally(function () {
                    btn.disabled = false;
                });
            });
        })();

        (function () {
            var form   = document.getElementById('factCheckForm');
            if (!form) return;

            var claim  = document.getElementById('factCheckClaim');
            var btn    = document.getElementById('factCheckBtn');
            var answer = document.getElementById('factCheckAnswer');

            var verdictLabels = {
                likely_true:  '✅ Likely true',
                likely_false: '❌ Likely false',
                unclear:      '❓ Unclear — needs a real source'
            };
            var verdictColors = {
                likely_true:  { bg: '#e8f7ef', fg: '#1e293b' },
                likely_false: { bg: '#fff1f2', fg: '#1e293b' },
                unclear:      { bg: '#fff8e6', fg: '#1e293b' }
            };

            function escapeHtml(text) {
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var token = form.querySelector('input[name="_token"]').value;

                btn.disabled = true;
                answer.style.display = 'block';
                answer.style.background = '#eaf2ff';
                answer.style.color = '#1e40af';
                answer.textContent = 'Brainbot is checking…';

                fetch('{{ route('student-panel-ai-help.fact-check') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ claim: claim.value })
                })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data.ok) {
                        var colors = verdictColors[data.verdict] || verdictColors.unclear;
                        var label  = verdictLabels[data.verdict] || verdictLabels.unclear;
                        answer.style.background = colors.bg;
                        answer.style.color = colors.fg;
                        answer.innerHTML = '<strong>🤖 ' + label + '</strong><br>' + escapeHtml(data.explanation);
                    } else {
                        answer.style.background = '#fff1f2';
                        answer.style.color = '#b91c1c';
                        answer.textContent = data.message || 'Something went wrong. Please try again.';
                    }
                })
                .catch(function () {
                    answer.style.background = '#fff1f2';
                    answer.style.color = '#b91c1c';
                    answer.textContent = 'Something went wrong. Please try again.';
                })
                .finally(function () {
                    btn.disabled = false;
                });
            });
        })();
    </script>
@endsection
