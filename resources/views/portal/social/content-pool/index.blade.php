@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <h4 class="bradecrumb-title mb-1">Content Pool</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Content Pool</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                    <table class="ot-basic-table ot-table-bg">
                        <thead class="thead">
                            <tr>
                                <th>Sr no.</th><th>Series</th><th>Topic</th><th>Hook</th>
                                <th>Suggested by</th><th>Designation</th><th>Id</th>
                                <th>Action</th><th>Deadline</th>
                                <th>Prompt link</th><th>Prompt feedback</th>
                                <th>Video link</th><th>Video feedback</th>
                                <th>Upload date</th>
                            </tr>
                        </thead>
                        <tbody class="tbody">
                            @forelse ($data['reels'] as $i => $reel)
                                @php($staff = optional($reel->suggestedBy)->staff)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $reel->series_name ?: '—' }}</td>
                                    <td><a href="{{ route('portal-social-reels.show', $reel->id) }}">{{ $reel->title }}</a></td>
                                    <td>{{ $reel->hook ?: '—' }}</td>
                                    <td>{{ optional($reel->suggestedBy)->name ?: '—' }}</td>
                                    <td>{{ optional($staff?->designation)->name ?: '—' }}</td>
                                    <td>{{ $staff->id ?? '—' }}</td>
                                    <td style="min-width:220px">
                                        @if ($reel->assigned_to || $reel->is_open_paid)
                                            {{ $reel->is_open_paid ? 'Open (Paid Task)' : 'Assigned to ' . trim(optional($reel->assignee)->first_name . ' ' . optional($reel->assignee)->last_name) }}
                                        @else
                                            <form action="{{ route('portal-social-reels.assign', $reel->id) }}" method="post" class="d-flex flex-column gap-1">
                                                @csrf
                                                <select name="assigned_to" class="nice-select niceSelect bordered_style wide">
                                                    <option value="">Assign to...</option>
                                                    @foreach ($data['contentTeam'] as $member)
                                                        <option value="{{ $member->id }}">{{ $member->first_name }} {{ $member->last_name }}</option>
                                                    @endforeach
                                                </select>
                                                <label class="d-inline-flex align-items-center gap-1 mb-0">
                                                    <input type="checkbox" name="open_paid" value="1" class="open-paid-check"> <span class="text-secondary">Open (Paid Task)</span>
                                                </label>
                                                <input type="date" name="deadline" class="form-control ot-input">
                                                <button type="submit" class="btn btn-sm ot-btn-primary">Approve &amp; Assign</button>
                                            </form>
                                        @endif
                                    </td>
                                    <td>{{ $reel->deadline?->format('d M Y') ?: '—' }}</td>
                                    <td>
                                        @if ($reel->prompt_link)<a href="{{ $reel->prompt_link }}" target="_blank">Link</a>@else —@endif
                                    </td>
                                    <td>{{ $reel->prompt_feedback ?: '—' }}</td>
                                    <td>
                                        @if ($reel->drive_link)<a href="{{ $reel->drive_link }}" target="_blank">Link</a>@else —@endif
                                    </td>
                                    <td>{{ $reel->video_feedback ?: '—' }}</td>
                                    <td>{{ $reel->planned_date?->format('d M Y') ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="100%" class="text-center gray-color">{{ ___('common.no_data_available') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('script')
    <script>
        // Event delegation, since every row has its own copy of this
        // checkbox + select (same issue as the Leave Request role toggle
        // elsewhere in this app - nice-select needs the real <select>
        // disabled, a plain checkbox change is a native DOM event).
        document.addEventListener('change', function (e) {
            if (!e.target.classList.contains('open-paid-check')) return;
            var select = e.target.closest('form').querySelector('select[name="assigned_to"]');
            if (!select) return;
            select.disabled = e.target.checked;
            if (window.jQuery) { jQuery(select).niceSelect('update'); }
        });
    </script>
    @endpush
@endsection
