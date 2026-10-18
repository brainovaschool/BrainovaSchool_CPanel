@php
    $selectedTaskId = $selectedTaskId ?? null;
    $selectedActivity = $selectedActivity ?? null;
    $selectedValue = $selectedTaskId ? 'task:' . $selectedTaskId : ($selectedActivity ?: '');
@endphp
<select name="task_or_activity" class="nice-select niceSelect bordered_style work-log-select" onchange="
    var v=this.value; var f=this.form;
    if (v.indexOf('task:')===0){ f.task_id.value=v.slice(5); f.activity.value=''; }
    else { f.task_id.value=''; f.activity.value=v; }
">
    <option value="">Pick one...</option>
    <optgroup label="My Tasks">
        @foreach ($myTasks as $t)
            <option value="task:{{ $t->id }}" {{ $selectedValue === 'task:' . $t->id ? 'selected' : '' }}>{{ $t->title }}</option>
        @endforeach
    </optgroup>
    <optgroup label="Activities">
        @foreach ($activities as $act)
            <option value="{{ $act }}" {{ $selectedValue === $act ? 'selected' : '' }}>{{ $act }}</option>
        @endforeach
        <option value="Other" {{ $selectedValue === 'Other' ? 'selected' : '' }}>Other (specify in notes)</option>
    </optgroup>
</select>
<input type="hidden" name="task_id" value="{{ $selectedTaskId }}">
<input type="hidden" name="activity" value="{{ $selectedActivity }}">
