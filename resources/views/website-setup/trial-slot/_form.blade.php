@php $s = $data['slot'] ?? null; @endphp

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('common.Date') }} <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('slot_date') is-invalid @enderror" type="date" name="slot_date"
            min="{{ now()->format('Y-m-d') }}"
            value="{{ old('slot_date', optional($s?->slot_date)->format('Y-m-d')) }}">
        @error('slot_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('settings.start_time') }} <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('start_time') is-invalid @enderror" name="start_time"
            value="{{ old('start_time', $s->start_time ?? '') }}" placeholder="e.g. 10:00 AM">
        @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('settings.end_time') }}</label>
        <input class="form-control ot-input" name="end_time" value="{{ old('end_time', $s->end_time ?? '') }}" placeholder="e.g. 11:00 AM">
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('settings.seats') }} <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('capacity') is-invalid @enderror" type="number" min="1" name="capacity"
            value="{{ old('capacity', $s->capacity ?? 1) }}">
        @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @if ($s)<small class="text-secondary">{{ $s->booked_count }} already booked</small>@endif
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('common.status') }} <span class="fillable">*</span></label>
        <select class="nice-select niceSelect bordered_style wide" name="status">
            <option value="{{ App\Enums\Status::ACTIVE }}" {{ old('status', $s->status ?? 1) == 1 ? 'selected' : '' }}>{{ ___('common.active') }}</option>
            <option value="{{ App\Enums\Status::INACTIVE }}" {{ old('status', $s->status ?? 1) == 0 ? 'selected' : '' }}>{{ ___('common.inactive') }}</option>
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('settings.internal_note') }}</label>
        <input class="form-control ot-input" name="note" value="{{ old('note', $s->note ?? '') }}" placeholder="{{ ___('settings.not_shown_to_visitors') }}">
    </div>
</div>

<div class="text-end mt-3">
    <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i> </span>{{ ___('common.submit') }}</button>
</div>
