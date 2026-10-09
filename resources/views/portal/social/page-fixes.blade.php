@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">Social Board</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Page Fixes</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="table-content table-basic mb-24">
            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered class-table">
                        <thead class="thead"><tr><th>Title</th><th>Description</th><th>Owner</th><th>Status</th><th class="action">{{ ___('common.action') }}</th></tr></thead>
                        <tbody class="tbody">
                            @forelse ($data['fixes'] as $fix)
                                <tr>
                                    <td>{{ $fix->title }}</td>
                                    <td>{{ $fix->description }}</td>
                                    <td>{{ $fix->owner ? trim($fix->owner->first_name . ' ' . $fix->owner->last_name) : '—' }}</td>
                                    <td>
                                        @if ($fix->status === 'fixed')<span class="badge-basic-success-text">Fixed</span>
                                        @elseif ($fix->status === 'in_progress')<span class="badge-basic-warning-text">In progress</span>
                                        @else <span class="badge-basic-danger-text">Open</span>@endif
                                    </td>
                                    <td class="action">
                                        @if ($data['canManage'] || ($data['myStaffId'] && $data['myStaffId'] === $fix->owner_staff_id))
                                            <form action="{{ route('portal-social-page-fixes.status', $fix->id) }}" method="post" class="d-flex gap-1">
                                                @csrf
                                                <select name="status" class="nice-select niceSelect bordered_style" style="min-width:120px" onchange="this.form.submit()">
                                                    @foreach (['open' => 'Open', 'in_progress' => 'In progress', 'fixed' => 'Fixed'] as $key => $label)
                                                        <option value="{{ $key }}" {{ $fix->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="100%" class="text-center gray-color">{{ ___('common.no_data_available') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if ($data['canManage'])
            <div class="card ot-card">
                <div class="card-header"><h4 class="mb-0">Add a fix</h4></div>
                <div class="card-body">
                    <form action="{{ route('portal-social-page-fixes.store') }}" method="post" class="d-flex gap-2 flex-wrap align-items-end">
                        @csrf
                        <div><label class="form-label">Title</label><input type="text" name="title" class="ot-input" required maxlength="150"></div>
                        <div style="min-width:240px"><label class="form-label">Description</label><input type="text" name="description" class="ot-input"></div>
                        <div>
                            <label class="form-label">Owner</label>
                            <select name="owner_staff_id" class="nice-select niceSelect bordered_style">
                                <option value="">— unassigned —</option>
                                @foreach ($data['staffList'] as $s)
                                    <option value="{{ $s->id }}">{{ trim($s->first_name . ' ' . $s->last_name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn ot-btn-primary">Add</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
