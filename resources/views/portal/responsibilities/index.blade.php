@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">Task Board</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Responsibilities</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-header"><h4 class="mb-0">Duties</h4></div>
                <div class="card-body">
                    @include('backend.partials.bulk-actions-bar', ['bulkStatusToggle' => true])
                    <div class="table-responsive">
                        <table class="ot-basic-table ot-table-bg">
                            <thead class="thead">
                                <tr>
                                    <th style="width:36px"><input type="checkbox" id="bulkSelectAll"></th>
                                    <th style="min-width:160px">Title</th>
                                    <th style="min-width:110px">Frequency</th>
                                    <th style="min-width:180px">Assigned to</th>
                                    <th>Active</th>
                                    <th style="min-width:200px">Details</th>
                                    <th>This period</th>
                                    <th style="min-width:160px" class="action">{{ ___('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @php($ownerIdsOf = fn ($duty) => $duty->owners->pluck('id')->all())
                                @php($formId = fn ($duty) => 'dutyForm' . $duty->id)
                                @foreach ($data['duties'] as $duty)
                                    <tr id="row_{{ $duty->id }}">
                                        <td><input type="checkbox" class="bulk-row-checkbox" value="{{ $duty->id }}"></td>
                                        <td>
                                            <input type="text" name="title" form="{{ $formId($duty) }}" class="form-control ot-input" value="{{ $duty->title }}" required>
                                            @if ($duty->key)<span class="badge-basic-info-text d-block mt-1" style="width:fit-content">Built-in</span>@endif
                                        </td>
                                        <td>
                                            <select name="freq" form="{{ $formId($duty) }}" class="form-control ot-input">
                                                <option value="daily" {{ $duty->freq === 'daily' ? 'selected' : '' }}>Daily</option>
                                                <option value="weekly" {{ $duty->freq === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="owner_staff_ids[]" form="{{ $formId($duty) }}" multiple class="form-control select2_multy">
                                                @foreach ($data['staffList'] as $s)
                                                    <option value="{{ $s->id }}" {{ in_array($s->id, $ownerIdsOf($duty)) ? 'selected' : '' }}>{{ trim($s->first_name . ' ' . $s->last_name) }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input" name="is_active" form="{{ $formId($duty) }}" value="1" {{ $duty->is_active ? 'checked' : '' }}>
                                        </td>
                                        <td>
                                            <textarea name="description" form="{{ $formId($duty) }}" class="form-control ot-input" rows="1">{{ $duty->description }}</textarea>
                                        </td>
                                        <td>{{ $duty->isDoneForCurrentPeriod() ? '✓ Done' : '— not yet —' }}</td>
                                        <td class="action">
                                            <form id="{{ $formId($duty) }}" action="{{ route('portal-responsibilities.update', $duty->id) }}" method="post">@csrf</form>
                                            <button type="submit" form="{{ $formId($duty) }}" class="btn btn-sm ot-btn-primary">Save</button>
                                            @unless ($duty->key)
                                                <form action="{{ route('portal-responsibilities.destroy', $duty->id) }}" method="post" class="d-inline" onsubmit="return confirm('Remove this responsibility?');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary mt-1">Remove</button>
                                                </form>
                                            @endunless
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card ot-card mt-4">
            <div class="card-header"><h4 class="mb-0">Add a Responsibility</h4></div>
            <div class="card-body">
                <form action="{{ route('portal-responsibilities.store') }}" method="post" class="d-flex gap-2 flex-wrap align-items-end">
                    @csrf
                    <div>
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control ot-input" required maxlength="150">
                    </div>
                    <div>
                        <label class="form-label">Frequency</label>
                        <select name="freq" class="form-control ot-input">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Assigned to</label>
                        <select name="owner_staff_ids[]" multiple class="form-control select2_multy" style="min-width:200px">
                            @foreach ($data['staffList'] as $s)
                                <option value="{{ $s->id }}">{{ trim($s->first_name . ' ' . $s->last_name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex align-items-center gap-1 mb-2">
                        <input type="checkbox" class="form-check-input" name="is_active" value="1" id="newDutyActive" checked>
                        <label class="form-label mb-0" for="newDutyActive">Active</label>
                    </div>
                    <div style="min-width:240px">
                        <label class="form-label">Details (optional)</label>
                        <input type="text" name="description" class="form-control ot-input">
                    </div>
                    <button type="submit" class="btn ot-btn-primary">Add</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
    @include('backend.partials.delete-ajax')
@endpush
@include('backend.partials.bulk-actions-ajax', ['bulkRoute' => 'portal/responsibilities'])
