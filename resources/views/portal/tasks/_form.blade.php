@php
    $task = $data['task'] ?? null;
    $old = fn ($field, $default = null) => old($field, $task->{$field} ?? $default);
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control ot-input" value="{{ $old('title') }}" required maxlength="150">
        @error('title') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
    @unless ($task)
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
    @endunless
</div>

@unless ($task)
    <div class="row g-3 mt-0">
        <div class="col-md-3">
            <div class="form-check mt-4">
                <input type="checkbox" class="form-check-input" id="paid" name="paid" value="1" {{ old('paid') ? 'checked' : '' }} onchange="document.getElementById('amountWrap').style.display = this.checked ? '' : 'none'; document.getElementById('visibilityCard').style.display = this.checked ? '' : 'none';">
                <label class="form-check-label" for="paid">This is a paid task</label>
            </div>
        </div>
        <div class="col-md-3" id="amountWrap" style="{{ old('paid') ? '' : 'display:none' }}">
            <label class="form-label">Amount ({{ Setting('currency_symbol') }})</label>
            <input type="number" step="0.01" min="0" name="amount" class="form-control ot-input" value="{{ old('amount') }}">
            @error('amount') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="card ot-card bg-light-subtle mt-3 mb-0" id="visibilityCard" style="{{ old('paid') ? '' : 'display:none' }}">
        <div class="card-body">
            <h5 class="mb-1">Who can see and claim it</h5>
            <p class="text-secondary mb-3" style="font-size:.8rem">Leave everything below blank to let any employee claim it. Pick a designation to open it to a whole group (e.g. all Coordinators) — then optionally add specific people on top of that, or hide it from one or two specific people even though their designation would otherwise include them.</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Open to designation(s) (optional)</label>
                    <select name="visible_to_role_ids[]" class="form-control select2_multy wide" multiple data-placeholder="Any designation">
                        @foreach ($data['rolesList'] as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Also show to these people (optional)</label>
                    <select name="visible_to_staff_ids[]" class="form-control select2_multy wide" multiple data-placeholder="No one extra">
                        @foreach ($data['employeesList'] as $emp)
                            <option value="{{ $emp->id }}">{{ trim($emp->first_name . ' ' . $emp->last_name) }} — {{ optional($emp->role)->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Hide from these people (optional)</label>
                    <select name="hidden_from_staff_ids[]" class="form-control select2_multy wide" multiple data-placeholder="No one excluded">
                        @foreach ($data['employeesList'] as $emp)
                            <option value="{{ $emp->id }}">{{ trim($emp->first_name . ' ' . $emp->last_name) }} — {{ optional($emp->role)->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
@else
    <p class="text-secondary mt-2 mb-0" style="font-size:.85rem">Who it's assigned to, paid-task settings and visibility are managed from the task page itself (Reassign / Mark Paid), not here.</p>
@endunless

<div class="row g-3 mt-0">
    <div class="col-md-4">
        <label class="form-label">Category</label>
        <select name="category" class="nice-select niceSelect bordered_style wide">
            <option value="">—</option>
            @foreach ($data['taskCategories'] as $cat)
                <option value="{{ $cat->name }}" {{ $old('category') === $cat->name ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Priority</label>
        <select name="priority" class="nice-select niceSelect bordered_style wide">
            @foreach (['Low', 'Medium', 'High'] as $p)
                <option value="{{ $p }}" {{ $old('priority', 'Medium') === $p ? 'selected' : '' }}>{{ $p }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Due date</label>
        <input type="date" name="due_date" class="form-control ot-input" value="{{ $old('due_date') instanceof \Carbon\Carbon ? $old('due_date')->format('Y-m-d') : $old('due_date') }}" required>
        @error('due_date') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row g-3 mt-0">
    <div class="col-md-4">
        <div class="form-check mt-4">
            <input type="checkbox" class="form-check-input" id="urgent" name="urgent" value="1" {{ $old('urgent') ? 'checked' : '' }}>
            <label class="form-check-label" for="urgent">Mark as urgent</label>
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label">Estimated hours</label>
        <input type="number" step="0.5" min="0" name="est_hours" class="form-control ot-input" value="{{ $old('est_hours') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Expected format</label>
        <input type="text" name="format" class="form-control ot-input" value="{{ $old('format') }}" placeholder="e.g. PDF, Reel, Doc">
    </div>
</div>

<div class="row g-3 mt-0">
    <div class="col-md-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control ot-input" rows="3">{{ $old('description') }}</textarea>
    </div>
    <div class="col-md-12">
        <label class="form-label">What does "done" look like? (output)</label>
        <textarea name="output" class="form-control ot-input" rows="2">{{ $old('output') }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label">Reference links / notes</label>
        <textarea name="refs" class="form-control ot-input" rows="2">{{ $old('refs') }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label">Drive link (optional)</label>
        <input type="text" name="drive_link" class="form-control ot-input" value="{{ $old('drive_link') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Reference file (optional)</label>
        @if ($task && $task->refUpload)
            <p class="text-secondary mb-1" style="font-size:.85rem"><a href="{{ globalAsset($task->refUpload->path) }}" target="_blank">Current file</a> — uploading a new one replaces it.</p>
        @endif
        <div class="ot_fileUploader left-side mb-1">
            <input class="form-control ot-input bn-file-placeholder" type="text" placeholder="No file chosen" readonly>
            <button class="primary-btn-small-input" type="button">
                <label class="btn btn-lg ot-btn-primary" for="refFileInput">Browse</label>
                <input type="file" class="d-none bn-file-input" id="refFileInput" name="ref_file">
            </button>
            <button type="button" class="btn btn-lg btn-outline-secondary bn-file-clear" title="Remove the selected file">Remove</button>
        </div>
        <small class="text-secondary">Picking a file again after Browse — or just closing the picker without choosing one — does not clear it; use Remove instead.</small>
    </div>
    <div class="col-md-6">
        <label class="form-label">Internal notes (not shown to employee unless you say so)</label>
        <textarea name="notes" class="form-control ot-input" rows="2">{{ $old('notes') }}</textarea>
    </div>

    <div class="col-md-12">
        <button type="submit" class="btn btn-lg ot-btn-primary">{{ $task ? 'Save Changes' : 'Assign Task' }}</button>
        <a href="{{ $task ? route('portal-tasks.show', $task->id) : route('portal-tasks.index') }}" class="btn btn-lg btn-outline-secondary">Cancel</a>
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

    // Re-opening the Browse dialog and cancelling it leaves the previous
    // selection in place (normal browser behaviour) - this is the only
    // way to actually clear it before submitting.
    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('bn-file-clear')) return;
        var wrapper = e.target.closest('.ot_fileUploader');
        var fileInput = wrapper ? wrapper.querySelector('.bn-file-input') : null;
        var placeholder = wrapper ? wrapper.querySelector('.bn-file-placeholder') : null;
        if (fileInput) fileInput.value = '';
        if (placeholder) placeholder.value = '';
    });
</script>
@endpush
