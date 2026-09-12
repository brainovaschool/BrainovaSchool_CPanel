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
                    @if (hasPermission('trial_slot_create'))
                        <a href="{{ route('trial-slot.create') }}" class="btn btn-lg ot-btn-primary">
                            <span><i class="fa-solid fa-plus"></i> </span><span>{{ ___('common.add') }}</span>
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    @if (hasPermission('trial_slot_delete') || hasPermission('trial_slot_update'))
                        @include('backend.partials.bulk-actions-bar')
                    @endif
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    @if (hasPermission('trial_slot_delete') || hasPermission('trial_slot_update'))
                                        <th style="width:36px"><input type="checkbox" id="bulkSelectAll"></th>
                                    @endif
                                    <th class="serial">{{ ___('common.sr_no') }}</th>
                                    <th>{{ ___('common.Date') }}</th>
                                    <th>{{ ___('settings.time') }}</th>
                                    <th>{{ ___('settings.seats') }}</th>
                                    <th>{{ ___('settings.booked') }}</th>
                                    <th>{{ ___('common.status') }}</th>
                                    @if (hasPermission('trial_slot_update') || hasPermission('trial_slot_delete'))
                                        <th class="action">{{ ___('common.action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['slots'] as $key => $row)
                                    <tr id="row_{{ $row->id }}">
                                        @if (hasPermission('trial_slot_delete') || hasPermission('trial_slot_update'))
                                            <td><input type="checkbox" class="bulk-row-checkbox" value="{{ $row->id }}"></td>
                                        @endif
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
                                        @if (hasPermission('trial_slot_update') || hasPermission('trial_slot_delete'))
                                            <td class="action">
                                                <div class="dropdown dropdown-action">
                                                    <button type="button" class="btn-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fa-solid fa-ellipsis"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        @if (hasPermission('trial_slot_update'))
                                                            <li><a class="dropdown-item" href="{{ route('trial-slot.edit', $row->id) }}"><span class="icon mr-8"><i class="fa-solid fa-pen-to-square"></i></span>{{ ___('common.edit') }}</a></li>
                                                        @endif
                                                        @if (hasPermission('trial_slot_delete'))
                                                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="delete_row('trial-slot/delete', {{ $row->id }})"><span class="icon mr-8"><i class="fa-solid fa-trash-can"></i></span><span>{{ ___('common.delete') }}</span></a></li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center gray-color">
                                            <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                                            <p class="mb-0 text-center">{{ ___('common.no_data_available') }}</p>
                                            <p class="mb-0 text-center text-secondary font-size-90">{{ ___('settings.add_slots_note') }}</p>
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
@endpush
@include('backend.partials.bulk-actions-ajax', ['bulkRoute' => 'trial-slot'])
