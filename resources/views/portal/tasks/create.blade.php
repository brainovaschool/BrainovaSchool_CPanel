@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('portal-tasks.index') }}">Tasks</a></li>
                        <li class="breadcrumb-item">{{ $data['title'] }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-body">
                <form action="{{ route('portal-tasks.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="ot-input" value="{{ old('title') }}" required maxlength="150">
                            @error('title') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assign to</label>
                            <select name="assigned_to" class="nice-select niceSelect bordered_style wide" required>
                                <option value="">Select employee</option>
                                @foreach ($data['employeesList'] as $emp)
                                    <option value="{{ $emp->id }}" {{ (string) old('assigned_to') === (string) $emp->id ? 'selected' : '' }}>{{ trim($emp->first_name . ' ' . $emp->last_name) }} — {{ optional($emp->role)->name }}</option>
                                @endforeach
                            </select>
                            @error('assigned_to') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select name="category" class="nice-select niceSelect bordered_style wide">
                                <option value="">—</option>
                                @foreach ($data['settings']->categories as $cat)
                                    <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="nice-select niceSelect bordered_style wide">
                                @foreach (['Low', 'Medium', 'High'] as $p)
                                    <option value="{{ $p }}" {{ old('priority', 'Medium') === $p ? 'selected' : '' }}>{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Due date</label>
                            <input type="date" name="due_date" class="ot-input" value="{{ old('due_date') }}" required>
                            @error('due_date') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input type="checkbox" class="form-check-input" id="urgent" name="urgent" value="1" {{ old('urgent') ? 'checked' : '' }}>
                                <label class="form-check-label" for="urgent">Mark as urgent</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estimated hours</label>
                            <input type="number" step="0.5" min="0" name="est_hours" class="ot-input" value="{{ old('est_hours') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expected format</label>
                            <input type="text" name="format" class="ot-input" value="{{ old('format') }}" placeholder="e.g. PDF, Reel, Doc">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="ot-input" rows="3">{{ old('description') }}</textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">What does "done" look like? (output)</label>
                            <textarea name="output" class="ot-input" rows="2">{{ old('output') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference links / notes</label>
                            <textarea name="refs" class="ot-input" rows="2">{{ old('refs') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Drive link (optional)</label>
                            <input type="text" name="drive_link" class="ot-input" value="{{ old('drive_link') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference file (optional)</label>
                            <input type="file" name="ref_file" class="ot-input">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Internal notes (not shown to employee unless you say so)</label>
                            <textarea name="notes" class="ot-input" rows="2">{{ old('notes') }}</textarea>
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-lg ot-btn-primary">Assign Task</button>
                            <a href="{{ route('portal-tasks.index') }}" class="btn btn-lg btn-outline-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
