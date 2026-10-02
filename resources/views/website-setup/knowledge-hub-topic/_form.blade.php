@php $s = $data['item'] ?? null; @endphp

<div class="row">
    <div class="col-md-8 mb-3">
        <label class="form-label">Topic title <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('title') is-invalid @enderror" name="title" maxlength="200"
            value="{{ old('title', $s->title ?? '') }}" placeholder="e.g. How to build a study routine">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-12 mb-3">
        <label class="form-label">Explanation <span class="fillable">*</span></label>
        <textarea class="form-control ot-textarea @error('explanation') is-invalid @enderror" name="explanation" rows="8"
            placeholder="The full write-up shown under this topic's title">{{ old('explanation', $s->explanation ?? '') }}</textarea>
        @error('explanation')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
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
