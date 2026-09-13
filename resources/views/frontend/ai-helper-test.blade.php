@extends('frontend.master')

@section('title')
    {{ $data['title'] }}
@endsection

@section('main')

<style>
    .ai-helper-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 32px;
        box-shadow: 0 10px 30px -16px rgba(15, 27, 61, 0.25);
    }
    .ai-helper-card h3 {
        color: #0f1b3d;
        margin-bottom: 24px;
    }
    .ai-helper-field { margin-bottom: 18px; }
    .ai-helper-field label {
        display: block;
        color: #0f1b3d;
        font-weight: 600;
        margin-bottom: 6px;
        font-size: 14px;
    }
    .ai-helper-field input {
        width: 100%;
        box-sizing: border-box;
        padding: 12px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 15px;
        color: #0f1b3d;
        background: #ffffff;
    }
    .ai-helper-field input::placeholder { color: #94a3b8; }
    .ai-helper-btn {
        display: inline-block;
        background: #0097b2;
        color: #ffffff !important;
        border: none;
        border-radius: 30px;
        padding: 12px 22px;
        font-size: 14px;
        font-weight: 600;
        margin: 0 8px 10px 0;
        cursor: pointer;
    }
    .ai-helper-btn:disabled { background: #94a3b8; cursor: not-allowed; }
    .ai-helper-note { color: #475569; font-size: 13px; margin-top: 10px; }
    .ai-helper-status {
        margin-top: 14px;
        padding: 10px 14px;
        border-radius: 8px;
        font-size: 14px;
        display: none;
    }
    .ai-helper-status.is-progress { display: block; background: #eaf2ff; color: #1e40af; }
    .ai-helper-status.is-success { display: block; background: #e8f7ef; color: #166534; }
    .ai-helper-status.is-error { display: block; background: #fff1f2; color: #b91c1c; }
</style>

<div class="section_padding2">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-9">
                <form id="aiHelperForm" action="{{ route('frontend.ai-helper.generate') }}" method="post">
                    @csrf
                    <div class="ai-helper-card">
                        <h3>{{ $data['title'] }}</h3>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="ai-helper-field">
                                    <label>Grade</label>
                                    <input type="text" name="grade" placeholder="e.g. 4th Grade" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="ai-helper-field">
                                    <label>Subject</label>
                                    <input type="text" name="subject" placeholder="e.g. Science" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="ai-helper-field">
                                    <label>Term</label>
                                    <input type="text" name="term" placeholder="e.g. Term 1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="ai-helper-field">
                                    <label>Unit</label>
                                    <input type="text" name="unit" placeholder="e.g. Ecosystems" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="ai-helper-field">
                                    <label>Module</label>
                                    <input type="text" name="module" placeholder="e.g. Food Chains" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="ai-helper-field">
                                    <label>Lesson Title</label>
                                    <input type="text" name="lesson_title" placeholder="e.g. The Energy Web" required>
                                </div>
                            </div>
                        </div>

                        <button type="submit" formaction="{{ route('frontend.ai-helper.generate') }}" class="ai-helper-btn">
                            {{ $data['button_text'] }}
                        </button>
                        <button type="submit" formaction="{{ route('frontend.ai-helper.generate-visual') }}" class="ai-helper-btn">
                            Generate Visual Lesson Plan
                        </button>
                        <button type="submit" formaction="{{ route('frontend.ai-helper.generate-slides') }}" class="ai-helper-btn">
                            Generate as Slides
                        </button>

                        <div id="aiHelperStatus" class="ai-helper-status"></div>

                        <p class="ai-helper-note">This can take up to a minute per click.</p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var form    = document.getElementById('aiHelperForm');
        var status  = document.getElementById('aiHelperStatus');
        var buttons = form.querySelectorAll('.ai-helper-btn');

        function setStatus(kind, text) {
            status.className = 'ai-helper-status is-' + kind;
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
            setStatus('progress', 'Generating your lesson plan… this can take up to a minute, please wait.');

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/pdf, application/json, text/plain'
                },
                body: new FormData(form)
            })
            .then(function (response) {
                var contentType = response.headers.get('Content-Type') || '';

                if (!response.ok) {
                    return response.text().then(function (text) {
                        throw new Error(text || 'Something went wrong. Please try again.');
                    });
                }

                // Delivery mode = Google Drive: server sends back a plain
                // text confirmation instead of a PDF — nothing to download.
                if (contentType.indexOf('application/pdf') === -1) {
                    return response.text().then(function (text) { return { saved: true, text: text }; });
                }

                var filename = filenameFromHeader(response.headers.get('Content-Disposition'));
                return response.blob().then(function (blob) { return { saved: false, blob: blob, filename: filename }; });
            })
            .then(function (result) {
                if (result.saved) {
                    setStatus('success', result.text || 'Saved to Google Drive!');
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

                setStatus('success', 'Download complete!');
                setButtonsDisabled(false);
            })
            .catch(function (err) {
                setStatus('error', err.message || 'Something went wrong. Please try again.');
                setButtonsDisabled(false);
            });
        });
    })();
</script>

@endsection
