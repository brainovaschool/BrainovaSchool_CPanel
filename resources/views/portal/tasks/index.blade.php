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
                        <li class="breadcrumb-item">Tasks</li>
                    </ol>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <a href="{{ route('portal-tasks.create') }}" class="btn btn-lg ot-btn-primary">+ Assign a Task</a>
                </div>
            </div>
        </div>

        <div class="col-12 p-0">
            <form action="{{ route('portal-tasks.index') }}" method="get">
                <div class="card ot-card mb-24 position-relative z_1">
                    <div class="card-header d-flex align-items-center gap-4 flex-wrap">
                        <h3 class="mb-0">{{ ___('common.Filtering') }}</h3>

                        <div class="card_header_right d-flex align-items-center gap-3 flex-fill justify-content-end flex-wrap">
                            <div class="single_large_selectBox">
                                <select class="nice-select niceSelect bordered_style wide" name="status">
                                    <option value="">All statuses</option>
                                    @foreach (['assigned' => 'Assigned', 'in_progress' => 'In progress', 'submitted' => 'Submitted', 'under_review' => 'Under review', 'revision' => 'Revision required', 'completed' => 'Completed'] as $key => $label)
                                        <option value="{{ $key }}" {{ ($data['filters']['status'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="single_large_selectBox">
                                <select class="nice-select niceSelect bordered_style wide" name="assigned_to">
                                    <option value="">All employees</option>
                                    @foreach ($data['employeesList'] as $emp)
                                        <option value="{{ $emp->id }}" {{ (string) ($data['filters']['assigned_to'] ?? '') === (string) $emp->id ? 'selected' : '' }}>{{ trim($emp->first_name . ' ' . $emp->last_name) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="single_large_selectBox">
                                <select class="nice-select niceSelect bordered_style wide" name="category">
                                    <option value="">All categories</option>
                                    @foreach ($data['settings']->categories as $cat)
                                        <option value="{{ $cat }}" {{ ($data['filters']['category'] ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="single_large_selectBox">
                                <input type="text" class="form-control ot-input" name="search" placeholder="Search title" value="{{ $data['filters']['search'] ?? '' }}">
                            </div>

                            <div class="single_large_selectBox">
                                <select class="nice-select niceSelect bordered_style wide" name="show">
                                    @foreach (['active' => 'Active', 'archived' => 'Archived', 'all' => 'All'] as $key => $label)
                                        <option value="{{ $key }}" {{ ($data['filters']['show'] ?? 'active') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('common.Search') }}</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="d-none align-items-center gap-2 mb-3 p-2 border rounded flex-wrap" id="bulkActionsBar">
            <span class="me-2"><strong id="bulkSelectedCount">0</strong> {{ ___('common.selected') }}</span>
            <select class="nice-select niceSelect bordered_style" id="bulkReassignTo" style="min-width: 180px;">
                <option value="">Reassign to…</option>
                @foreach ($data['employeesList'] as $emp)
                    <option value="{{ $emp->id }}">{{ trim($emp->first_name . ' ' . $emp->last_name) }}</option>
                @endforeach
            </select>
            <button type="button" class="btn btn-sm ot-btn-primary" onclick="bulk_reassign()">Reassign</button>
            <button type="button" class="btn btn-sm btn-outline-success" onclick="bulk_status(1)">
                <i class="fa-solid fa-check"></i> {{ ___('common.activate') }}
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="bulk_status(0)">
                <i class="fa-solid fa-ban"></i> {{ ___('common.deactivate') }}
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulk_delete()">
                <i class="fa-solid fa-trash-can"></i> {{ ___('common.delete_selected') }}
            </button>
        </div>

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="ot-basic-table ot-table-bg">
                            <thead class="thead">
                                <tr>
                                    <th><input type="checkbox" id="bulkSelectAll"></th>
                                    <th>Title</th>
                                    <th>Employee</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Due</th>
                                    <th>Status</th>
                                    <th>Score</th>
                                    <th class="action">{{ ___('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['tasks'] as $task)
                                    <tr id="row_{{ $task->id }}" class="{{ $task->active_status ? '' : 'opacity-50' }}">
                                        <td><input type="checkbox" class="bulk-row-checkbox" value="{{ $task->id }}"></td>
                                        <td>
                                            {{ $task->title }}
                                            @if ($task->urgent)<span class="badge-basic-danger-text">Urgent</span>@endif
                                            @unless ($task->active_status)<span class="badge-basic-secondary-text">Archived</span>@endunless
                                        </td>
                                        <td>{{ optional($task->assignee)->first_name }} {{ optional($task->assignee)->last_name }}</td>
                                        <td>{{ $task->category ?: '—' }}</td>
                                        <td>{{ $task->priority }}</td>
                                        <td>
                                            {{ $task->due_date?->format('d M Y') }}
                                            @if ($task->is_overdue)
                                                <span class="badge-basic-danger-text">Overdue</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge-basic-info-text">{{ str_replace('_', ' ', ucfirst($task->status)) }}</span>
                                        </td>
                                        <td>{{ $task->final_score !== null ? $task->final_score . '/10' : '—' }}</td>
                                        <td class="action">
                                            <a href="{{ route('portal-tasks.show', $task->id) }}" class="btn btn-sm ot-btn-primary">Open</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center gray-color">
                                            <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                                            <p class="mb-0 text-center">{{ ___('common.no_data_available') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="ot-pagination pagination-content d-flex justify-content-end align-content-center py-3">
                        <nav>
                            <ul class="pagination justify-content-between">
                                {!! $data['tasks']->appends(\Request::capture()->except('page'))->links() !!}
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
<script>
(function () {
    function bulkSelectedIds() {
        return $('.bulk-row-checkbox:checked').map(function () { return $(this).val(); }).get();
    }

    function bulkToggleBar() {
        var n = bulkSelectedIds().length;
        $('#bulkSelectedCount').text(n);
        if (n > 0) {
            $('#bulkActionsBar').removeClass('d-none').addClass('d-flex');
        } else {
            $('#bulkActionsBar').addClass('d-none').removeClass('d-flex');
        }
    }

    $(document).on('change', '.bulk-row-checkbox', function () {
        var all = $('.bulk-row-checkbox').length === $('.bulk-row-checkbox:checked').length;
        $('#bulkSelectAll').prop('checked', all);
        bulkToggleBar();
    });

    $(document).on('change', '#bulkSelectAll', function () {
        $('.bulk-row-checkbox').prop('checked', this.checked);
        bulkToggleBar();
    });

    window.bulk_delete = function () {
        var ids = bulkSelectedIds();
        if (!ids.length) return;

        Swal.fire({
            title: $('#alert_title').val(),
            text: $('#alert_subtitle').val(),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: $('#alert_yes_btn').val(),
            cancelButtonText: $('#alert_cancel_btn').val(),
        }).then(function (confirmed) {
            if (!confirmed.isConfirmed) return;

            $.ajax({
                type: 'POST',
                dataType: 'json',
                data: { ids: ids },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                url: "{{ route('portal-tasks.bulk-delete') }}",
            }).done(function (response) {
                Swal.fire({
                    icon: response[1],
                    title: response[2],
                    text: response[0],
                    showCloseButton: true,
                    confirmButtonText: response[3],
                });
                if (response[1] !== 'error') {
                    setTimeout(function () { location.reload(); }, 900);
                }
            }).fail(function () {
                Swal.fire('{{ ___('common.opps') }}...', '{{ ___('common.something_went_wrong_with_ajax') }}', 'error');
            });
        });
    };

    window.bulk_status = function (status) {
        var ids = bulkSelectedIds();
        if (!ids.length) return;

        $.ajax({
            type: 'POST',
            dataType: 'json',
            data: { ids: ids, status: status },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            url: "{{ route('portal-tasks.bulk-status') }}",
        }).done(function (response) {
            Swal.fire({
                icon: response[1],
                title: response[2],
                text: response[0],
                showCloseButton: true,
                confirmButtonText: response[3],
            });
            if (response[1] !== 'error') {
                setTimeout(function () { location.reload(); }, 900);
            }
        }).fail(function () {
            Swal.fire('{{ ___('common.opps') }}...', '{{ ___('common.something_went_wrong_with_ajax') }}', 'error');
        });
    };

    window.bulk_reassign = function () {
        var ids = bulkSelectedIds();
        var staffId = $('#bulkReassignTo').val();
        if (!ids.length) return;
        if (!staffId) {
            Swal.fire('{{ ___('common.opps') }}...', 'Pick who to reassign these to first.', 'warning');
            return;
        }

        $.ajax({
            type: 'POST',
            dataType: 'json',
            data: { ids: ids, assigned_to: staffId },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            url: "{{ route('portal-tasks.bulk-reassign') }}",
        }).done(function (response) {
            Swal.fire({
                icon: response[1],
                title: response[2],
                text: response[0],
                showCloseButton: true,
                confirmButtonText: response[3],
            });
            if (response[1] !== 'error') {
                setTimeout(function () { location.reload(); }, 900);
            }
        }).fail(function () {
            Swal.fire('{{ ___('common.opps') }}...', '{{ ___('common.something_went_wrong_with_ajax') }}', 'error');
        });
    };
})();
</script>
@endpush
