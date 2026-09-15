@extends('backend.master')

@section('title')
    {{ $data['title'] }}
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

        <div class="card ot-card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-4">
                    @if ($data['mascot'])
                        <img src="{{ $data['mascot'] }}" alt="mascot" style="height:80px;">
                    @endif
                    <div>
                        <h4 class="mb-1">{{ $data['title'] }}</h4>
                        <p class="text-secondary mb-0">Fill in the lesson details and generate a plan, a
                            branded visual PDF, or a slide deck.</p>
                    </div>
                </div>

                <form id="aiHelperTeacherForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Grade</label>
                            <select name="grade" class="form-control ot-input" required>
                                <option value="">— Select Grade —</option>
                                @for ($g = 1; $g <= 8; $g++)
                                    <option value="Grade {{ $g }}">Grade {{ $g }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Subject</label>
                            <select name="subject" class="form-control ot-input" required>
                                <option value="">— Select Subject —</option>
                                @foreach (['English', 'Urdu', 'Maths', 'Science', 'History', 'Geography', 'Islamiyat', 'Others'] as $subjectOption)
                                    <option value="{{ $subjectOption }}">{{ $subjectOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Term</label>
                            <select name="term" class="form-control ot-input" required>
                                <option value="">— Select Term —</option>
                                @for ($t = 1; $t <= 6; $t++)
                                    <option value="Term {{ $t }}">Term {{ $t }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unit</label>
                            <input type="text" name="unit" class="form-control ot-input" placeholder="e.g. Ecosystems" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Module</label>
                            <input type="text" name="module" class="form-control ot-input" placeholder="e.g. Food Chains" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Lesson Title</label>
                            <input type="text" name="lesson_title" class="form-control ot-input" placeholder="e.g. The Energy Web" required>
                        </div>
                    </div>

                    <button type="submit" formaction="{{ route('ai-help-teacher.generate') }}" class="btn ot-btn-primary me-2">
                        {{ $data['button_text'] }}
                    </button>
                    <button type="submit" formaction="{{ route('ai-help-teacher.generate-visual') }}" class="btn ot-btn-primary me-2">
                        Generate Visual Lesson Plan
                    </button>
                    <button type="submit" formaction="{{ route('ai-help-teacher.generate-slides') }}" class="btn ot-btn-primary">
                        Generate as Slides
                    </button>

                    <div id="aiHelperTeacherStatus" class="mt-3 p-2 rounded" style="display:none;"></div>
                    <p class="text-secondary mt-2 mb-0" style="font-size:13px">This can take up to a minute per click.</p>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var form    = document.getElementById('aiHelperTeacherForm');
            var status  = document.getElementById('aiHelperTeacherStatus');
            var buttons = form.querySelectorAll('button[type="submit"]');

            function setStatus(bg, color, text) {
                status.style.display = 'block';
                status.style.background = bg;
                status.style.color = color;
                status.textContent = text;
            }

            function setButtonsDisabled(disabled) {
                buttons.forEach(function (btn) { btn.disabled = disabled; });
            }

            function filenameFromHeader(header) {
                if (!header) return 'lesson-plan.pdf';
                var match = header.match(/filename="?([^"]+)"?/);
                return match ? match[1] : 'lesson-plan.pdf';
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var url   = e.submitter ? e.submitter.formAction : form.action;
                var token = form.querySelector('input[name="_token"]').value;

                setButtonsDisabled(true);
                setStatus('#eaf2ff', '#1e40af', 'Generating your lesson plan… this can take up to a minute, please wait.');

                fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/pdf, text/plain' },
                    body: new FormData(form)
                })
                .then(function (response) {
                    var contentType = response.headers.get('Content-Type') || '';

                    if (!response.ok) {
                        return response.text().then(function (text) {
                            throw new Error(text || 'Something went wrong. Please try again.');
                        });
                    }

                    if (contentType.indexOf('application/pdf') === -1) {
                        return response.text().then(function (text) { return { saved: true, text: text }; });
                    }

                    var filename = filenameFromHeader(response.headers.get('Content-Disposition'));
                    return response.blob().then(function (blob) { return { saved: false, blob: blob, filename: filename }; });
                })
                .then(function (result) {
                    if (result.saved) {
                        setStatus('#e8f7ef', '#166534', result.text || 'Saved to Google Drive!');
                        setButtonsDisabled(false);
                        return;
                    }

                    var objectUrl = URL.createObjectURL(result.blob);
                    var link = document.createElement('a');
                    link.href = objectUrl;
                    link.download = result.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    setTimeout(function () { URL.revokeObjectURL(objectUrl); }, 5000);

                    setStatus('#e8f7ef', '#166534', 'Download complete!');
                    setButtonsDisabled(false);
                })
                .catch(function (err) {
                    setStatus('#fff1f2', '#b91c1c', err.message || 'Something went wrong. Please try again.');
                    setButtonsDisabled(false);
                });
            });
        })();
    </script>
@endsection
