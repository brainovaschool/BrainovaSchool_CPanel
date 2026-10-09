@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">Team Portal</h4>
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
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    <th>Title</th>
                                    <th>Frequency</th>
                                    <th>Owner</th>
                                    <th>This period</th>
                                    <th style="min-width:420px" class="action">{{ ___('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @foreach ($data['duties'] as $duty)
                                    <tr>
                                        <td>{{ $duty->title }} @if ($duty->key)<span class="badge-basic-info-text">Built-in</span>@endif</td>
                                        <td>{{ ucfirst($duty->freq) }}</td>
                                        <td>{{ $duty->owner ? trim($duty->owner->first_name . ' ' . $duty->owner->last_name) : '— unassigned (admin) —' }}</td>
                                        <td>{{ $duty->isDoneForCurrentPeriod() ? '✓ Done' : '— not yet —' }}</td>
                                        <td class="action">
                                            <form action="{{ route('portal-responsibilities.update', $duty->id) }}" method="post" class="d-flex gap-2 flex-wrap align-items-center">
                                                @csrf
                                                <input type="text" name="title" class="ot-input" value="{{ $duty->title }}" style="max-width:160px" required>
                                                <select name="freq" class="nice-select niceSelect bordered_style">
                                                    <option value="daily" {{ $duty->freq === 'daily' ? 'selected' : '' }}>Daily</option>
                                                    <option value="weekly" {{ $duty->freq === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                                </select>
                                                <select name="owner_staff_id" class="nice-select niceSelect bordered_style" style="min-width:160px">
                                                    <option value="">— admin —</option>
                                                    @foreach ($data['staffList'] as $s)
                                                        <option value="{{ $s->id }}" {{ (int) $duty->owner_staff_id === $s->id ? 'selected' : '' }}>{{ trim($s->first_name . ' ' . $s->last_name) }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm ot-btn-primary">Save</button>
                                            </form>
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
                        <input type="text" name="title" class="ot-input" required maxlength="150">
                    </div>
                    <div>
                        <label class="form-label">Frequency</label>
                        <select name="freq" class="nice-select niceSelect bordered_style">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Owner</label>
                        <select name="owner_staff_id" class="nice-select niceSelect bordered_style" style="min-width:160px">
                            <option value="">— admin —</option>
                            @foreach ($data['staffList'] as $s)
                                <option value="{{ $s->id }}">{{ trim($s->first_name . ' ' . $s->last_name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="min-width:240px">
                        <label class="form-label">Description (optional)</label>
                        <input type="text" name="description" class="ot-input">
                    </div>
                    <button type="submit" class="btn ot-btn-primary">Add</button>
                </form>
            </div>
        </div>
    </div>
@endsection
