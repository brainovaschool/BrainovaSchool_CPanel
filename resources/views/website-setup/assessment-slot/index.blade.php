@extends('backend.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    <div class="page-content">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">{{ $data['title'] }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="table-content table-basic mt-20">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ $data['title'] }}</h4>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-lg btn-outline-danger" onclick="cleanup_past_slots()">
                            <span><i class="fa-solid fa-broom"></i> </span><span>Clean Up Past Slots</span>
                        </button>
                        <a href="{{ route('assessment-slot.bulk-create') }}" class="btn btn-lg btn-secondary">
                            <span><i class="fa-solid fa-layer-group"></i> </span><span>Bulk Add</span>
                        </a>
                        <a href="{{ route('assessment-slot.create') }}" class="btn btn-lg ot-btn-primary">
                            <span><i class="fa-solid fa-plus"></i> </span><span>{{ ___('common.add') }}</span>
                        </a>
                    </div>
                    <form id="cleanupPastForm" method="post" action="{{ route('assessment-slot.cleanup-past') }}" class="d-none">
                        @csrf
                    </form>
                </div>
                <div class="card-body">
                    <form method="get" class="d-flex align-items-end gap-2 flex-wrap mb-3 p-2 border rounded">
                        <div>
                            <label class="form-label small mb-1">Day: from</label>
                            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
                        </div>
                        <div>
                            <label class="form-label small mb-1">to</label>
                            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
                        </div>
                        <div>
                            <label class="form-label small mb-1">Time: from</label>
                            <input type="time" name="time_from" class="form-control form-control-sm" value="{{ request('time_from') }}">
                        </div>
                        <div>
                            <label class="form-label small mb-1">to</label>
                            <input type="time" name="time_to" class="form-control form-control-sm" value="{{ request('time_to') }}">
                        </div>
                        <div>
                            <label class="form-label small mb-1">Status</label>
                            <select name="status" class="form-control form-control-sm" style="max-width:130px">
                                <option value="">All</option>
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>{{ ___('common.active') }}</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>{{ ___('common.inactive') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label small mb-1">Slots</label>
                            <select name="availability" class="form-control form-control-sm" style="max-width:160px">
                                <option value="">All</option>
                                <option value="open" {{ request('availability') === 'open' ? 'selected' : '' }}>Has seats left</option>
                                <option value="full" {{ request('availability') === 'full' ? 'selected' : '' }}>Fully booked</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm ot-btn-primary">Filter</button>
                        @if (collect(request()->only(['from_date', 'to_date', 'time_from', 'time_to', 'status', 'availability']))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty())
                            <a href="{{ route('assessment-slot.index') }}" class="btn btn-sm btn-secondary">Clear</a>
                        @endif
                    </form>
                    @include('backend.partials.bulk-actions-bar')
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3 p-2 border rounded" id="bulkEditBar">
                        <span class="small text-secondary">Bulk edit selected (check rows below first):</span>
                        <input type="number" min="1" id="bulkEditCapacity" class="form-control form-control-sm" style="max-width:110px" placeholder="New seats">
                        <select id="bulkEditStatus" class="form-control form-control-sm" style="max-width:140px">
                            <option value="">Leave status as-is</option>
                            <option value="1">{{ ___('common.active') }}</option>
                            <option value="0">{{ ___('common.inactive') }}</option>
                        </select>
                        <button type="button" class="btn btn-sm ot-btn-primary" onclick="bulk_edit_slots()">Apply</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    <th style="width:36px"><input type="checkbox" id="bulkSelectAll"></th>
                                    <th class="serial">{{ ___('common.sr_no') }}</th>
                                    <th>{{ ___('common.Date') }}</th>
                                    <th>{{ ___('settings.time') }}</th>
                                    <th>{{ ___('settings.seats') }}</th>
                                    <th>{{ ___('settings.booked') }}</th>
                                    <th>{{ ___('common.status') }}</th>
                                    <th class="action">{{ ___('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['slots'] as $key => $row)
                                    <tr id="row_{{ $row->id }}">
                                        <td><input type="checkbox" class="bulk-row-checkbox" value="{{ $row->id }}"></td>
                                        <td class="serial">{{ ++$key }}</td>
                                        <td>{{ $row->slot_date?->format('D, d M Y') }}</td>
                                        <td>{{ $row->time_label }}</td>
                                        <td>{{ $row->capacity }}</td>
                                        <td>{{ $row->booked_count }} <small class="text-secondary">({{ $row->remaining }} left)</small></td>
                                        <td>
                                            @if ($row->status == App\Enums\Status::ACTIVE)
                                                <span class="badge-basic-success-text">{{ ___('common.active') }}</span>
                                            @else
                                                <span class="badge-basic-danger-text">{{ ___('common.inactive') }}</span>
                                            @endif
                                        </td>
                                        <td class="action">
                                            <div class="dropdown dropdown-action">
                                                <button type="button" class="btn-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa-solid fa-ellipsis"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('assessment-slot.edit', $row->id) }}"><span class="icon mr-8"><i class="fa-solid fa-pen-to-square"></i></span>{{ ___('common.edit') }}</a></li>
                                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="delete_row('assessment-slot/delete', {{ $row->id }})"><span class="icon mr-8"><i class="fa-solid fa-trash-can"></i></span><span>{{ ___('common.delete') }}</span></a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center gray-color">
                                            <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                                            <p class="mb-0 text-center">{{ ___('common.no_data_available') }}</p>
                                            <p class="mb-0 text-center text-secondary font-size-90">Add a date and time so visitors can book an assessment slot at {{ url('/book-assessment') }}.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="ot-pagination pagination-content d-flex justify-content-end align-content-center py-3">
                        <nav><ul class="pagination justify-content-between">{!! $data['slots']->links() !!}</ul></nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    @include('backend.partials.delete-ajax')
    <script>
        function cleanup_past_slots() {
            Swal.fire({
                title: 'Clean up past slots?',
                text: 'Removes every slot whose date and time have already passed, whether or not it was booked. A booking already made keeps its own record of when it was for - only the empty slot row itself goes away. This can\'t be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, clean up',
                cancelButtonText: 'Cancel',
            }).then(function (confirmed) {
                if (confirmed.isConfirmed) {
                    document.getElementById('cleanupPastForm').submit();
                }
            });
        }

        function bulk_edit_slots() {
            var ids = $('.bulk-row-checkbox:checked').map(function () { return $(this).val(); }).get();
            if (!ids.length) { Swal.fire('Select at least one row first.'); return; }
            var capacity = $('#bulkEditCapacity').val();
            var status = $('#bulkEditStatus').val();
            if (!capacity && status === '') { Swal.fire('Set a new seat count and/or a status first.'); return; }
            $.ajax({
                type: 'POST',
                dataType: 'json',
                data: { ids: ids, capacity: capacity, status: status },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                url: "{{ route('assessment-slot.bulk-edit') }}",
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
        }
    </script>
@endpush
@include('backend.partials.bulk-actions-ajax', ['bulkRoute' => 'assessment-slot'])
