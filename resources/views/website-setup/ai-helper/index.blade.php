@extends('backend.master')

@section('title')
    {{ @$data['title'] }}
@endsection

@section('content')
    <div class="page-content">

        {{-- bradecrumb Area S t a r t --}}
        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h1>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">{{ $data['title'] }}</li>
                    </ol>
                </div>
            </div>
        </div>
        {{-- bradecrumb Area E n d --}}

        <div class="card ot-card">
            <div class="card-header">
                <h4>{{ ___('settings.ai_helper') }}</h4>
                <p class="text-secondary mb-0">This is a private test page — it is not linked anywhere on the
                    site. Visit it directly at:
                    <code>{{ url('/homeschool-ai-preview') }}</code>
                </p>
            </div>
            <div class="card-body">
                <form action="{{ route('ai-helper.update') }}" method="post" id="visitForm">
                    @csrf
                    <div class="row mb-3">

                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Gemini API Key <span class="fillable">*</span></label>
                            <input type="text" name="ai_helper_api_key"
                                class="form-control ot-input @error('ai_helper_api_key') is-invalid @enderror"
                                value="{{ Setting('ai_helper_api_key') }}" placeholder="Paste your Gemini API key here">
                            @error('ai_helper_api_key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Model</label>
                            <input type="text" name="ai_helper_model"
                                class="form-control ot-input @error('ai_helper_model') is-invalid @enderror"
                                value="{{ Setting('ai_helper_model') ?: 'gemini-2.0-flash' }}"
                                placeholder="gemini-2.0-flash">
                            <small class="text-secondary">Only change this if Google renames/retires the default
                                model.</small>
                            @error('ai_helper_model')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Page Title</label>
                            <input type="text" name="ai_helper_page_title"
                                class="form-control ot-input @error('ai_helper_page_title') is-invalid @enderror"
                                value="{{ Setting('ai_helper_page_title') }}" placeholder="AI Helper (Preview)">
                            @error('ai_helper_page_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Button Text</label>
                            <input type="text" name="ai_helper_button_text"
                                class="form-control ot-input @error('ai_helper_button_text') is-invalid @enderror"
                                value="{{ Setting('ai_helper_button_text') }}" placeholder="Help">
                            @error('ai_helper_button_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Question Label (shown above the input box)</label>
                            <input type="text" name="ai_helper_question_label"
                                class="form-control ot-input @error('ai_helper_question_label') is-invalid @enderror"
                                value="{{ Setting('ai_helper_question_label') }}"
                                placeholder="Enter a number">
                            @error('ai_helper_question_label')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Prompt (the instructions sent to the AI, before the visitor's
                                own input)</label>
                            <textarea name="ai_helper_system_prompt" rows="6"
                                class="form-control ot-textarea @error('ai_helper_system_prompt') is-invalid @enderror"
                                placeholder="You are a helpful assistant. The user will give you a number. Reply with just one short sentence telling them whether it is even or odd.">{{ Setting('ai_helper_system_prompt') }}</textarea>
                            @error('ai_helper_system_prompt')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    <div class="col-md-12 mt-3">
                        <div class="text-end">
                            @if (hasPermission('ai_helper_update'))
                                <button class="btn btn-lg ot-btn-primary">
                                    <span><i class="fa-solid fa-save"></i></span>{{ ___('common.update') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
