@php
    $s = $data['item'] ?? null;
    $currentLinkType = old('linkable_type', $s->linkable_type ?? '');
@endphp

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Base <span class="fillable">*</span></label>
        <select class="form-control ot-input @error('hub_id') is-invalid @enderror" name="hub_id">
            <option value="">{{ ___('common.select') }}</option>
            @foreach ($data['hubs'] as $hub)
                <option value="{{ $hub->id }}" {{ old('hub_id', $s->hub_id ?? '') == $hub->id ? 'selected' : '' }}>{{ $hub->name }}</option>
            @endforeach
        </select>
        @error('hub_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Building it unlocks (optional)</label>
        <select class="form-control ot-input @error('building_id') is-invalid @enderror" name="building_id">
            <option value="">No building — bonus/side quest</option>
            @foreach ($data['buildings'] as $building)
                <option value="{{ $building->id }}" {{ old('building_id', $s->building_id ?? '') == $building->id ? 'selected' : '' }}>
                    {{ $building->name }} ({{ optional($building->parent)->name }})
                </option>
            @endforeach
        </select>
        @error('building_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-secondary">Finishing this mission (reaching {{ \App\Repositories\LearningEngine\StudentMissionRepository::MASTERY_THRESHOLD }}% on the linked work below) unlocks this building.</small>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Theme <span class="fillable">*</span></label>
        <select class="form-control ot-input @error('theme') is-invalid @enderror" name="theme">
            @foreach (App\Models\LearningEngine\Mission::THEMES as $key => $label)
                <option value="{{ $key }}" {{ old('theme', $s->theme ?? 'treasure') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('theme')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-secondary">Only shown to a student who has this theme chosen for their current term.</small>
    </div>

    <div class="col-md-8 mb-3">
        <label class="form-label">Title <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('title') is-invalid @enderror" name="title"
            value="{{ old('title', $s->title ?? '') }}" placeholder="e.g. The Old Sailor's Map">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Character <span class="fillable">*</span></label>
        <select class="form-control ot-input @error('character') is-invalid @enderror" name="character">
            @foreach (App\Models\LearningEngine\CharacterLine::CHARACTERS as $key => $label)
                <option value="{{ $key }}" {{ old('character', $s->character ?? 'brainbot') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('character')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Opening line <span class="fillable">*</span></label>
        <textarea class="form-control ot-textarea @error('intro_line') is-invalid @enderror" name="intro_line" rows="3" maxlength="600"
            placeholder="What the character says when a student opens this mission">{{ old('intro_line', $s->intro_line ?? '') }}</textarea>
        @error('intro_line')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Ending line <span class="fillable">*</span></label>
        <textarea class="form-control ot-textarea @error('ending_line') is-invalid @enderror" name="ending_line" rows="3" maxlength="600"
            placeholder="Shown once the student finishes — what the mission's ending looks like">{{ old('ending_line', $s->ending_line ?? '') }}</textarea>
        @error('ending_line')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-8 mb-3">
        <label class="form-label">Clues <span class="fillable">*</span></label>
        <textarea class="form-control ot-textarea @error('clue_lines') is-invalid @enderror" name="clue_lines" rows="6" placeholder="One clue per line, 3 to 7 lines. Each one is revealed as the student makes progress on the linked work below.">{{ old('clue_lines', $s && $s->clue_lines ? implode("\n", $s->clue_lines) : '') }}</textarea>
        @error('clue_lines')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-secondary">One line each — the first clue shows at the first sign of progress, the last one right before the mission finishes.</small>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Reward note (optional)</label>
        <input class="form-control ot-input" name="reward_note" maxlength="150"
            value="{{ old('reward_note', $s->reward_note ?? '') }}" placeholder="e.g. Unlocks the CodeNova Robotics Lab">
        @error('reward_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-secondary">Shown to the family alongside "Unlocked by: ..." — what this was for.</small>
    </div>
</div>

<div class="card mt-2 mb-3">
    <div class="card-body">
        <h5 class="mb-1">What real work this mission is wrapped around</h5>
        <p class="text-secondary" style="font-size:.86rem;">
            The student's actual mark on this Homework or Online Exam <strong>is</strong> the mission's progress — clues
            reveal as it climbs, and the mission finishes at {{ \App\Repositories\LearningEngine\StudentMissionRepository::MASTERY_THRESHOLD }}%. Leave this blank to publish the story first and link the work later.
        </p>
        <div class="row">
            <div class="col-md-4 mb-2">
                <label class="form-label">Type</label>
                <select class="form-control ot-input" name="linkable_type" id="linkableType">
                    <option value="">Not linked yet</option>
                    @foreach (App\Models\LearningEngine\Mission::LINKABLE_TYPES as $key => $label)
                        <option value="{{ $key }}" {{ $currentLinkType === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-8 mb-2" id="linkHomeworkWrap" style="{{ $currentLinkType === 'homework' ? '' : 'display:none;' }}">
                <label class="form-label">Homework</label>
                <select class="form-control ot-input" name="linkable_id_homework">
                    <option value="">{{ ___('common.select') }}</option>
                    @foreach ($data['homeworks'] as $hw)
                        <option value="{{ $hw->id }}" {{ $currentLinkType === 'homework' && old('linkable_id', $s->linkable_id ?? '') == $hw->id ? 'selected' : '' }}>
                            {{ optional($hw->subject)->name }} — {{ \Carbon\Carbon::parse($hw->date)->format('d M Y') }} (marks: {{ $hw->marks }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-8 mb-2" id="linkExamWrap" style="{{ $currentLinkType === 'online_exam' ? '' : 'display:none;' }}">
                <label class="form-label">Online Exam</label>
                <select class="form-control ot-input" name="linkable_id_online_exam">
                    <option value="">{{ ___('common.select') }}</option>
                    @foreach ($data['exams'] as $exam)
                        <option value="{{ $exam->id }}" {{ $currentLinkType === 'online_exam' && old('linkable_id', $s->linkable_id ?? '') == $exam->id ? 'selected' : '' }}>
                            {{ $exam->name }} — {{ optional($exam->subject)->name }} (total: {{ $exam->total_mark }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('common.Serial') }}</label>
        <input class="form-control ot-input" name="sort_order" type="number" min="0" value="{{ old('sort_order', $s->sort_order ?? 0) }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('common.status') }} <span class="fillable">*</span></label>
        <select class="form-control ot-input" name="status">
            <option value="{{ App\Enums\Status::ACTIVE }}" {{ old('status', $s->status ?? 1) == 1 ? 'selected' : '' }}>{{ ___('common.active') }}</option>
            <option value="{{ App\Enums\Status::INACTIVE }}" {{ old('status', $s->status ?? 1) == 0 ? 'selected' : '' }}>{{ ___('common.inactive') }}</option>
        </select>
    </div>
</div>

<div class="text-end mt-3">
    <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i> </span>{{ ___('common.submit') }}</button>
</div>

@push('script')
<script>
(function () {
    var typeSel  = document.getElementById('linkableType');
    var hwWrap   = document.getElementById('linkHomeworkWrap');
    var examWrap = document.getElementById('linkExamWrap');
    var hwSelect = hwWrap.querySelector('select');
    var examSelect = examWrap.querySelector('select');
    if (!typeSel) return;

    function sync() {
        var isHw   = typeSel.value === 'homework';
        var isExam = typeSel.value === 'online_exam';
        hwWrap.style.display   = isHw ? '' : 'none';
        examWrap.style.display = isExam ? '' : 'none';
        hwSelect.name   = isHw ? 'linkable_id' : 'linkable_id_homework';
        examSelect.name = isExam ? 'linkable_id' : 'linkable_id_online_exam';
    }

    typeSel.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
