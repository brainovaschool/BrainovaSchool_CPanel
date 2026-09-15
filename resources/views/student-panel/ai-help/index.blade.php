@extends('student-panel.partials.master')

@section('title')
    {{ @$data['title'] }}
@endsection

@section('content')
    <div class="page-content">
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
    </script>
@endsection
