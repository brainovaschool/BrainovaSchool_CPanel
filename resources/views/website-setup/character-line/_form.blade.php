@php $s = $data['item'] ?? null; @endphp

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Character <span class="fillable">*</span></label>
        <select class="form-control ot-input @error('character') is-invalid @enderror" name="character">
            @foreach (App\Models\LearningEngine\CharacterLine::CHARACTERS as $key => $label)
                <option value="{{ $key }}" {{ old('character', $s->character ?? 'brainbot') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('character')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Context <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('context') is-invalid @enderror" name="context" list="knownContexts"
            value="{{ old('context', $s->context ?? '') }}" placeholder="e.g. welcome, encouragement, mistake_review">
        <datalist id="knownContexts">
            @foreach ($data['contexts'] ?? [] as $context)
                <option value="{{ $context }}">
            @endforeach
        </datalist>
        @error('context')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-secondary">The moment this line is used in. Pick an existing one from the list, or type a new one if a feature needs it.</small>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('common.status') }} <span class="fillable">*</span></label>
        <select class="form-control ot-input @error('status') is-invalid @enderror" name="status">
            <option value="{{ App\Enums\Status::ACTIVE }}" {{ old('status', $s->status ?? 1) == 1 ? 'selected' : '' }}>{{ ___('common.active') }}</option>
            <option value="{{ App\Enums\Status::INACTIVE }}" {{ old('status', $s->status ?? 1) == 0 ? 'selected' : '' }}>{{ ___('common.inactive') }}</option>
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-8 mb-3">
        <label class="form-label">Line <span class="fillable">*</span></label>
        <textarea class="form-control ot-textarea @error('line') is-invalid @enderror" name="line" rows="2" maxlength="500"
            placeholder="What the character says, in their own voice">{{ old('line', $s->line ?? '') }}</textarea>
        @error('line')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-secondary">Several lines can share the same character and context — one shows at random each time, so it doesn't repeat itself every visit.</small>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('common.Serial') }}</label>
        <input class="form-control ot-input @error('sort_order') is-invalid @enderror" name="sort_order" type="number" min="0"
            value="{{ old('sort_order', $s->sort_order ?? 0) }}">
        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="text-end mt-3">
    <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i> </span>{{ ___('common.submit') }}</button>
</div>
