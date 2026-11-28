@extends('backend.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    <div class="page-content">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assessment-slot.index') }}">Assessment Slots</a></li>
                        <li class="breadcrumb-item active">Bulk Add</li>
                    </ol>
                </div>
            </div>
        </div>
        <div class="card ot-card">
            <div class="card-body">
                <p class="text-secondary mb-3">Generates one slot for every day in the range that falls on a checked weekday, at every time listed below. Running this again over an overlapping range is safe — any date + time combination that already exists is skipped, not duplicated.</p>
                <form action="{{ route('assessment-slot.bulk-generate') }}" method="post">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">From date <span class="fillable">*</span></label>
                            <input class="form-control ot-input @error('start_date') is-invalid @enderror" type="date" name="start_date"
                                min="{{ now()->format('Y-m-d') }}" value="{{ old('start_date') }}">
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">To date <span class="fillable">*</span></label>
                            <input class="form-control ot-input @error('end_date') is-invalid @enderror" type="date" name="end_date"
                                min="{{ now()->format('Y-m-d') }}" value="{{ old('end_date') }}">
                            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Seats per slot <span class="fillable">*</span></label>
                            <input class="form-control ot-input @error('capacity') is-invalid @enderror" type="number" min="1" name="capacity" value="{{ old('capacity', 1) }}">
                            @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Days of the week <span class="fillable">*</span></label>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach (['1' => 'Mon', '2' => 'Tue', '3' => 'Wed', '4' => 'Thu', '5' => 'Fri', '6' => 'Sat', '0' => 'Sun'] as $value => $label)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="weekdays[]" value="{{ $value }}" id="wd{{ $value }}"
                                        {{ in_array($value, old('weekdays', ['1', '2', '3', '4', '5'])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="wd{{ $value }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                        @error('weekdays')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Times (one per line, 24-hour, e.g. 09:00) <span class="fillable">*</span></label>
                            <textarea class="form-control ot-input @error('times') is-invalid @enderror" name="times" rows="4" placeholder="09:00&#10;10:00&#10;14:00&#10;15:00">{{ old('times') }}</textarea>
                            @error('times')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-secondary">Each one becomes a 15-minute slot on every matching date.</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status <span class="fillable">*</span></label>
                            <select class="nice-select niceSelect bordered_style wide" name="status">
                                <option value="{{ App\Enums\Status::ACTIVE }}" {{ old('status', 1) == 1 ? 'selected' : '' }}>{{ ___('common.active') }}</option>
                                <option value="{{ App\Enums\Status::INACTIVE }}" {{ old('status', 1) == 0 ? 'selected' : '' }}>{{ ___('common.inactive') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Internal note</label>
                        <input class="form-control ot-input" name="note" value="{{ old('note') }}" placeholder="{{ ___('settings.not_shown_to_visitors') }}">
                    </div>

                    <div class="text-end mt-3">
                        <a href="{{ route('assessment-slot.index') }}" class="btn btn-lg btn-secondary">Cancel</a>
                        <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i> </span>Generate slots</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
