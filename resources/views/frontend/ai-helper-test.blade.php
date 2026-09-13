@extends('frontend.master')

@section('title')
    {{ $data['title'] }}
@endsection

@section('main')

<div class="section_padding2">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-9">
                <form action="{{ route('frontend.ai-helper.generate') }}" method="post">
                    @csrf
                    <div class="contact_form_box mb_30">
                        <div class="section__title mb_30">
                            <h3 class="mb-0 text-white">{{ $data['title'] }}</h3>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="primary_label">Grade</label>
                                <input type="text" name="grade" class="primary_input mb_30" placeholder="e.g. 4th Grade" required>
                            </div>
                            <div class="col-md-6">
                                <label class="primary_label">Subject</label>
                                <input type="text" name="subject" class="primary_input mb_30" placeholder="e.g. Science" required>
                            </div>
                            <div class="col-md-6">
                                <label class="primary_label">Term</label>
                                <input type="text" name="term" class="primary_input mb_30" placeholder="e.g. Term 1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="primary_label">Unit</label>
                                <input type="text" name="unit" class="primary_input mb_30" placeholder="e.g. Ecosystems" required>
                            </div>
                            <div class="col-md-6">
                                <label class="primary_label">Module</label>
                                <input type="text" name="module" class="primary_input mb_30" placeholder="e.g. Food Chains" required>
                            </div>
                            <div class="col-md-6">
                                <label class="primary_label">Lesson Title</label>
                                <input type="text" name="lesson_title" class="primary_input mb_30" placeholder="e.g. The Energy Web" required>
                            </div>
                        </div>

                        <button type="submit" formaction="{{ route('frontend.ai-helper.generate') }}"
                            class="theme_btn submit-btn text-center d-inline-flex gap_14 align-items-center m-0 me-2">
                            {{ $data['button_text'] }}
                        </button>
                        <button type="submit" formaction="{{ route('frontend.ai-helper.generate-visual') }}"
                            class="theme_btn submit-btn text-center d-inline-flex gap_14 align-items-center m-0 me-2">
                            Generate Visual Lesson Plan
                        </button>
                        <button type="submit" formaction="{{ route('frontend.ai-helper.generate-slides') }}"
                            class="theme_btn submit-btn text-center d-inline-flex gap_14 align-items-center m-0">
                            Generate as Slides
                        </button>
                        <p class="text-white mt-3 mb-0" style="opacity:.8">This can take up to a minute — the page
                            will just look like it's loading, then a PDF will download automatically.</p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
