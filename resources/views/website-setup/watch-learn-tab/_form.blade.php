@php $s = $data['item'] ?? null; @endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Tab title <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('title') is-invalid @enderror" name="title" maxlength="100"
            value="{{ old('title', $s->title ?? '') }}" placeholder="e.g. Science Facts">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('common.Serial') }}</label>
        <input class="form-control ot-input" name="sort_order" type="number" min="0" value="{{ old('sort_order', $s->sort_order ?? 0) }}">
    </div>
    <div class="col-md-3 mb-3">
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
