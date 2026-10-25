@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">Suggest</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Suggest</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card mb-24">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h4 class="mb-0">Suggest Content</h4>
                <a href="{{ route('portal-social-suggest-reel.create') }}" class="btn ot-btn-primary">+ Add</a>
            </div>
            <div class="card-body">
                <p class="text-secondary mb-0">Got an idea for content? Click "+ Add", pick the series, and give the topic and hook — everything else is filled in for you. An admin or coordinator will review it on the Reels page.</p>
            </div>
        </div>

        @php($pageCategories = [
            'Dashboard', 'Online Admission', 'Subscriptions', 'Student Info', 'Id Cards', 'Certificates',
            'Academic', 'Routines', 'Attendance', 'Leave', 'Fees', 'Examination', 'Online Examination',
            'Staff Workspace', 'Teacher Tools', 'Communication', 'Transportation', 'Live Class', 'Library',
            'Accounts', 'Report', 'Language', 'Staff Manage', 'Subscribers', 'Contact Message',
            'Course Catalogue', 'Learning Engine', 'Website Setup', 'Gallery', 'App Settings',
            'Manage School Features', 'Settings',
        ])
        <div class="card ot-card mb-24">
            <div class="card-header"><h4 class="mb-0">Page Fixes</h4></div>
            <div class="card-body">
                <div class="table-responsive mb-24">
                    <table class="ot-basic-table ot-table-bg">
                        <thead class="thead"><tr><th>Title</th><th>Description</th><th>Page</th><th>Status</th><th class="action">{{ ___('common.action') }}</th></tr></thead>
                        <tbody class="tbody">
                            @forelse ($data['fixes'] as $fix)
                                <tr>
                                    <td>{{ $fix->title }}</td>
                                    <td>{{ $fix->description }}</td>
                                    <td>{{ $fix->page_category ?: '—' }}</td>
                                    <td>
                                        @if ($fix->status === 'fixed')<span class="badge-basic-success-text">Fixed</span>
                                        @elseif ($fix->status === 'in_progress')<span class="badge-basic-warning-text">In progress</span>
                                        @else <span class="badge-basic-danger-text">Open</span>@endif
                                    </td>
                                    <td class="action">
                                        @if ($data['canManage'])
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

                @if ($data['canManage'])
                    <h4 class="mb-3">Add a fix</h4>
                    <form action="{{ route('portal-social-page-fixes.store') }}" method="post">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control ot-input" required maxlength="150">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Description</label>
                                <input type="text" name="description" class="form-control ot-input">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Page</label>
                                <select name="page_category" class="nice-select niceSelect bordered_style wide">
                                    <option value="">— pick a page —</option>
                                    @foreach ($pageCategories as $cat)
                                        <option value="{{ $cat }}">{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn ot-btn-primary w-100">Add</button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
