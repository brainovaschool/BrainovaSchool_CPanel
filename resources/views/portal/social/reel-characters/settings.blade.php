@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('portal-social-reel-characters.index') }}">Reel Characters</a></li>
                        <li class="breadcrumb-item">{{ $data['title'] }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <form action="{{ route('portal-social-reel-settings.update') }}" method="post">
            @csrf

            <div class="card ot-card mb-3">
                <div class="card-body">
                    <h4>Global Instructions</h4>
                    <p class="text-secondary">Included in <strong>every</strong> downloaded scene prompt, for every reel — style, educational objectives, dialogue standards, scene continuity, and anything else that should always apply.</p>
                    <textarea name="reel_global_instructions" rows="8" class="form-control ot-textarea"
                        placeholder="e.g. Keep the tone warm and encouraging. Every reel should teach one clear idea. Scenes should feel like one continuous story, not disconnected clips...">{{ old('reel_global_instructions', $data['globalInstructions']) }}</textarea>
                </div>
            </div>

            <div class="card ot-card mb-3">
                <div class="card-body">
                    <h4>AI Scene Generation Prompt <span class="text-secondary" style="font-weight:normal;">(advanced, optional)</span></h4>
                    <p class="text-secondary">
                        This is the instruction set the AI follows when a teacher clicks "AI: Generate Scenes" — write it however you like (tone, camera work, lighting, humor, pacing, story structure, anything), as detailed as you want.
                        Leave it blank to use a sensible built-in default instead.
                    </p>
                    <p class="text-secondary">
                        Use these placeholders anywhere in your text — they're filled in automatically for each reel:
                        <br><code>@{{title}}</code>, <code>@{{hook}}</code>, <code>@{{description}}</code>, <code>@{{format}}</code>, <code>@{{target_duration}}</code>, <code>@{{scene_count_instruction}}</code> (a ready-made sentence telling the AI how many scenes to make), <code>@{{characters}}</code> (the list of involved characters with their appearance/voice/rules).
                    </p>
                    <p class="text-secondary">
                        You don't need to ask for JSON or worry about output format — the system adds its own technical instructions after your prompt automatically so the generated scenes always land back into the editable Scene Builder correctly.
                        You also don't need to write the opening hook line or the closing "Think Smart / Learn Smarter" line yourself — those are added automatically to whichever scene is marked First/Last.
                    </p>
                    <textarea name="reel_ai_prompt_template" rows="20" class="form-control ot-textarea" style="font-family:monospace;font-size:13px;"
                        placeholder="e.g. VISUAL STYLE: Premium futuristic Brainova educational animation. FORMAT: @{{format}}...">{{ old('reel_ai_prompt_template', $data['aiPromptTemplate']) }}</textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-lg ot-btn-primary">Save</button>
        </form>
    </div>
@endsection
