@php $s = $data['item'] ?? null; @endphp

<input type="hidden" name="orientation" value="{{ $data['orientation'] }}">

<div class="alert alert-info" style="font-size:.88rem;">
    <i class="fa-solid fa-circle-info me-1"></i> This is a <strong>{{ ucfirst($data['orientation']) }}</strong> video. To add the other shape, go back and use the other "Add" button — orientation can't be changed after creating.
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Video link <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('video_url') is-invalid @enderror" name="video_url"
            value="{{ old('video_url', $s->video_url ?? '') }}"
            placeholder="A YouTube, Facebook or Instagram link">
        @error('video_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Caption (optional)</label>
        <input class="form-control ot-input" name="title" maxlength="150"
            value="{{ old('title', $s->title ?? '') }}" placeholder="Shown under the video">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Tab</label>
        <select class="form-control ot-input @error('tab_id') is-invalid @enderror" name="tab_id">
            <option value="">No tab</option>
            @foreach ($data['tabs'] as $tab)
                <option value="{{ $tab->id }}" {{ old('tab_id', $s->tab_id ?? '') == $tab->id ? 'selected' : '' }}>{{ $tab->title }}</option>
            @endforeach
        </select>
        @error('tab_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-secondary">Manage tabs at Website Setup &rarr; Watch & Learn &rarr; Tabs.</small>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Tile template</label>
        <select class="form-control ot-input @error('template_id') is-invalid @enderror" name="template_id">
            <option value="">No frame — plain video</option>
            @foreach ($data['templates'] as $template)
                <option value="{{ $template->id }}" {{ old('template_id', $s->template_id ?? '') == $template->id ? 'selected' : '' }}>{{ $template->name ?: ('Template #' . $template->id) }}</option>
            @endforeach
        </select>
        @error('template_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @if ($data['templates']->isEmpty())
            <small class="text-secondary">No {{ $data['orientation'] }} templates yet — add one at Website Setup &rarr; Watch & Learn &rarr; Tile Templates.</small>
        @endif
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('common.Serial') }}</label>
        <input class="form-control ot-input" name="sort_order" type="number" min="0" value="{{ old('sort_order', $s->sort_order ?? 0) }}">
    </div>
</div>

<div class="row">
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
