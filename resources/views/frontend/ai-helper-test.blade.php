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
    .ai-helper-note { color: #475569; font-size: 13px; margin-top: 10px; }
</style>

<div class="section_padding2">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-9">
                <form action="{{ route('frontend.ai-helper.generate') }}" method="post">
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
                        <p class="ai-helper-note">This can take up to a minute — the page will just look like it's
                            loading, then a PDF will download automatically.</p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
