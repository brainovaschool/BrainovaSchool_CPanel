@php $s = $data['item'] ?? null; @endphp

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('common.category') }} <span class="fillable">*</span></label>
        <select class="form-control ot-input @error('category') is-invalid @enderror" name="category">
            <option value="avatar" {{ old('category', $s->category ?? $data['category'] ?? 'avatar') == 'avatar' ? 'selected' : '' }}>{{ ___('settings.avatar_look') }}</option>
            <option value="accessory" {{ old('category', $s->category ?? '') == 'accessory' ? 'selected' : '' }}>{{ ___('settings.accessory') }}</option>
        </select>
        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-secondary">An "avatar look" is a full image students can choose; an "accessory" is a small badge shown on the corner of it.</small>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('common.title') }} <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('name') is-invalid @enderror" name="name"
            value="{{ old('name', $s->name ?? '') }}" placeholder="e.g. Space Explorer Kea">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('settings.price_in_coins') }} <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('price_coins') is-invalid @enderror" name="price_coins" type="number" min="0"
            value="{{ old('price_coins', $s->price_coins ?? 0) }}">
        @error('price_coins')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-secondary">Price 0 means every student owns it automatically, as a free starter option.</small>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('common.image') }} @if(!$s) <span class="fillable">*</span> @endif</label>
        <div class="ot_fileUploader left-side mb-2 @error('image') is-invalid @enderror">
            <input class="form-control" type="text" placeholder="{{ ___('common.image') }}" readonly id="placeholder">
            <button class="primary-btn-small-input" type="button">
                <label class="btn btn-lg ot-btn-primary" for="fileBrouse">{{ ___('common.browse') }}</label>
                <input type="file" class="d-none form-control" name="image" accept="image/*" id="fileBrouse">
            </button>
        </div>
        @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @if ($s && $s->image)
            <img src="{{ globalAsset($s->image) }}" alt="{{ $s->name }}" class="mt-2" style="height:70px;border-radius:50%;">
        @endif
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('common.Serial') }}</label>
        <input class="form-control ot-input @error('sort_order') is-invalid @enderror" name="sort_order" type="number" min="0"
            value="{{ old('sort_order', $s->sort_order ?? 0) }}">
        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('common.status') }} <span class="fillable">*</span></label>
        <select class="form-control ot-input @error('status') is-invalid @enderror" name="status">
            <option value="{{ App\Enums\Status::ACTIVE }}" {{ old('status', $s->status ?? 1) == 1 ? 'selected' : '' }}>{{ ___('common.active') }}</option>
            <option value="{{ App\Enums\Status::INACTIVE }}" {{ old('status', $s->status ?? 1) == 0 ? 'selected' : '' }}>{{ ___('common.inactive') }}</option>
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="text-end mt-3">
    <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i> </span>{{ ___('common.submit') }}</button>
</div>
