@php $s = $data['item'] ?? null; @endphp

@if ($s && $s->upload)
    <div class="alert alert-info d-flex align-items-center justify-content-between" style="font-size:.88rem;">
        <span><i class="fa-solid fa-image me-1"></i> Current tile image</span>
        <img src="{{ globalAsset($s->upload->path) }}" alt="" style="height:60px;border-radius:6px;">
    </div>
@endif

<div class="row">
    <div class="col-md-5 mb-3">
        <label class="form-label">Template name</label>
        <input class="form-control ot-input" name="name" maxlength="100"
            value="{{ old('name', $s->name ?? '') }}" placeholder="e.g. Blue Rounded Frame">
        @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Fits <span class="fillable">*</span></label>
        <select class="form-control ot-input @error('orientation') is-invalid @enderror" name="orientation">
            @foreach (App\Models\WebsiteSetup\WatchLearnTemplate::ORIENTATIONS as $key => $label)
                <option value="{{ $key }}" {{ old('orientation', $s->orientation ?? 'landscape') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('orientation')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-secondary">Only offered when adding a video of this same shape.</small>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Tile image {{ $s && $s->upload_id ? '' : '*' }}</label>
        <div class="ot_fileUploader left-side mb-2 @error('image') is-invalid @enderror">
            <input class="form-control" type="text" placeholder="Image" readonly id="placeholder">
            <button class="primary-btn-small-input" type="button">
                <label class="btn btn-lg ot-btn-primary" for="fileBrouse">{{ ___('common.browse') }}</label>
                <input type="file" class="d-none form-control" name="image" accept="image/*" id="fileBrouse">
            </button>
        </div>
        @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <small class="text-secondary">A PNG with a transparent middle works best — the video shows through the cutout, your border art shows around it.</small>
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
