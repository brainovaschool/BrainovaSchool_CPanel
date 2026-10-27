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
                            <input type="text" name="title" class="form-control ot-input" value="{{ old('title') }}" required maxlength="150">
                            @error('title') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assign to</label>
                            <select name="assigned_to" id="assigned_to" class="nice-select niceSelect bordered_style wide">
                                <option value="">— Select employee —</option>
                                @foreach ($data['employeesList'] as $emp)
                                    <option value="{{ $emp->id }}" {{ (string) old('assigned_to') === (string) $emp->id ? 'selected' : '' }}>{{ trim($emp->first_name . ' ' . $emp->last_name) }} — {{ optional($emp->role)->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-secondary mb-0" style="font-size:.8rem" id="assignHint">Required, unless this is a paid task left open to claim.</p>
                            @error('assigned_to') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" class="form-check-input" id="paid" name="paid" value="1" {{ old('paid') ? 'checked' : '' }} onchange="document.getElementById('amountWrap').style.display = this.checked ? '' : 'none'; document.getElementById('visibleToWrap').style.display = this.checked ? '' : 'none';">
                                <label class="form-check-label" for="paid">This is a paid task</label>
                            </div>
                        </div>
                        <div class="col-md-3" id="amountWrap" style="{{ old('paid') ? '' : 'display:none' }}">
                            <label class="form-label">Amount ({{ Setting('currency_symbol') }})</label>
                            <input type="number" step="0.01" min="0" name="amount" class="form-control ot-input" value="{{ old('amount') }}">
                            @error('amount') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6" id="visibleToWrap" style="{{ old('paid') ? '' : 'display:none' }}">
                            <label class="form-label">Visible to (optional)</label>
                            <select name="visible_to_staff_ids[]" class="form-control select2_multy wide" multiple data-placeholder="Visible to everyone">
                                @foreach ($data['employeesList'] as $emp)
                                    <option value="{{ $emp->id }}">{{ trim($emp->first_name . ' ' . $emp->last_name) }} — {{ optional($emp->role)->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-secondary mb-0" style="font-size:.8rem">Leave blank to let any employee claim it — pick specific people to restrict who can see and claim this task.</p>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select name="category" class="nice-select niceSelect bordered_style wide">
                                <option value="">—</option>
                                @foreach ($data['taskCategories'] as $cat)
                                    <option value="{{ $cat->name }}" {{ old('category') === $cat->name ? 'selected' : '' }}>{{ $cat->name }}</option>
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
                            <input type="date" name="due_date" class="form-control ot-input" value="{{ old('due_date') }}" required>
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
                            <input type="number" step="0.5" min="0" name="est_hours" class="form-control ot-input" value="{{ old('est_hours') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expected format</label>
                            <input type="text" name="format" class="form-control ot-input" value="{{ old('format') }}" placeholder="e.g. PDF, Reel, Doc">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control ot-input" rows="3">{{ old('description') }}</textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">What does "done" look like? (output)</label>
                            <textarea name="output" class="form-control ot-input" rows="2">{{ old('output') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference links / notes</label>
                            <textarea name="refs" class="form-control ot-input" rows="2">{{ old('refs') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Drive link (optional)</label>
                            <input type="text" name="drive_link" class="form-control ot-input" value="{{ old('drive_link') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference file (optional)</label>
                            <div class="ot_fileUploader left-side mb-1">
                                <input class="form-control ot-input bn-file-placeholder" type="text" placeholder="No file chosen" readonly>
                                <button class="primary-btn-small-input" type="button">
                                    <label class="btn btn-lg ot-btn-primary" for="refFileInput">Browse</label>
                                    <input type="file" class="d-none bn-file-input" id="refFileInput" name="ref_file">
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Internal notes (not shown to employee unless you say so)</label>
                            <textarea name="notes" class="form-control ot-input" rows="2">{{ old('notes') }}</textarea>
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

    @push('script')
    <script>
        // main.js hides every input[type=file] on every page - clicking
        // the visible "Browse" <label> still opens the picker natively via
        // its for="", so this only needs to mirror the chosen filename
        // into the readonly text box next to it.
        document.addEventListener('change', function (e) {
            if (!e.target.classList.contains('bn-file-input')) return;
            var wrapper = e.target.closest('.ot_fileUploader');
            var placeholder = wrapper ? wrapper.querySelector('.bn-file-placeholder') : null;
            if (placeholder) {
                placeholder.value = e.target.files.length ? e.target.files[0].name : '';
            }
        });
    </script>
    @endpush
@endsection
