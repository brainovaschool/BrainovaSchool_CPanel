{{--
    Full-width, per-role config: which OTHER roles' leave records this role
    (the one being created/edited) gets extra detail on. Viewer is implicit
    (it's this role), so only the subject side needs picking.

    Expects:
      $roles              Collection<Role> — every selectable role (required)
      $leaveVisibility    ['replacement_day' => [ids], 'half_day_full_day' => [ids]] (optional)
--}}
@php($leaveVisibility = $leaveVisibility ?? [])

<div class="card mt-24">
    <div class="card-header">
        <h5 class="mb-0">Leave — Extra Details Visibility</h5>
    </div>
    <div class="card-body">
        <p class="text-secondary mb-3">
            Which roles' leave requests should this role see the extra staff-only details for — a Guardian
            viewing a Student's leave, for example, never needs these.
        </p>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label d-block">Replacement Day — visible for leave belonging to:</label>
                @foreach ($roles as $role)
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="leave_field_visibility[replacement_day][]"
                               value="{{ $role->id }}" id="rd_{{ $role->id }}"
                               {{ in_array($role->id, $leaveVisibility['replacement_day'] ?? []) ? 'checked' : '' }}>
                        <label class="form-check-label" for="rd_{{ $role->id }}">{{ $role->name }}</label>
                    </div>
                @endforeach
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label d-block">Half Day / Full Day — visible for leave belonging to:</label>
                @foreach ($roles as $role)
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="leave_field_visibility[half_day_full_day][]"
                               value="{{ $role->id }}" id="hd_{{ $role->id }}"
                               {{ in_array($role->id, $leaveVisibility['half_day_full_day'] ?? []) ? 'checked' : '' }}>
                        <label class="form-check-label" for="hd_{{ $role->id }}">{{ $role->name }}</label>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
