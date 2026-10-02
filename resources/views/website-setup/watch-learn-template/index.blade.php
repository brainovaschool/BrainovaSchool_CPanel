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
                    <div>
                        <h4 class="mb-0">{{ $data['title'] }}</h4>
                        <p class="text-secondary mb-0" style="font-size:.85rem;">Reusable frame/border images — pick one of these for each video on the Watch & Learn page.</p>
                    </div>
                    @if (hasPermission('watch_learn_template_create'))
                        <a href="{{ route('watch-learn-template.create') }}" class="btn btn-lg ot-btn-primary">
                            <span><i class="fa-solid fa-plus"></i> </span>
                            <span>{{ ___('common.add') }}</span>
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    @if (hasPermission('watch_learn_template_delete') || hasPermission('watch_learn_template_update'))
                        @include('backend.partials.bulk-actions-bar')
                    @endif
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    @if (hasPermission('watch_learn_template_delete') || hasPermission('watch_learn_template_update'))
                                        <th style="width:36px"><input type="checkbox" id="bulkSelectAll"></th>
                                    @endif
                                    <th>Preview</th>
                                    <th>Name</th>
                                    <th>Fits</th>
                                    <th>{{ ___('common.status') }}</th>
                                    @if (hasPermission('watch_learn_template_update') || hasPermission('watch_learn_template_delete'))
                                        <th class="action">{{ ___('common.action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['templates'] as $row)
                                    <tr id="row_{{ $row->id }}">
                                        @if (hasPermission('watch_learn_template_delete') || hasPermission('watch_learn_template_update'))
                                            <td><input type="checkbox" class="bulk-row-checkbox" value="{{ $row->id }}"></td>
                                        @endif
                                        <td>
                                            @if ($row->upload)
                                                <img src="{{ globalAsset($row->upload->path) }}" alt="" style="height:50px;border-radius:6px;">
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $row->name ?: '—' }}</td>
                                        <td>{{ App\Models\WebsiteSetup\WatchLearnTemplate::ORIENTATIONS[$row->orientation] ?? $row->orientation }}</td>
                                        <td>
                                            @if ($row->status == App\Enums\Status::ACTIVE)
                                                <span class="badge-basic-success-text">{{ ___('common.active') }}</span>
                                            @else
                                                <span class="badge-basic-danger-text">{{ ___('common.inactive') }}</span>
                                            @endif
                                        </td>
                                        @if (hasPermission('watch_learn_template_update') || hasPermission('watch_learn_template_delete'))
                                            <td class="action">
                                                <div class="dropdown dropdown-action">
                                                    <button type="button" class="btn-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fa-solid fa-ellipsis"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        @if (hasPermission('watch_learn_template_update'))
                                                            <li><a class="dropdown-item" href="{{ route('watch-learn-template.edit', $row->id) }}"><span class="icon mr-8"><i class="fa-solid fa-pen-to-square"></i></span>{{ ___('common.edit') }}</a></li>
                                                        @endif
                                                        @if (hasPermission('watch_learn_template_delete'))
                                                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="delete_row('watch-learn-template/delete', {{ $row->id }})"><span class="icon mr-8"><i class="fa-solid fa-trash-can"></i></span><span>{{ ___('common.delete') }}</span></a></li>
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
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="ot-pagination pagination-content d-flex justify-content-end align-content-center py-3">
                        <nav><ul class="pagination justify-content-between">{!! $data['templates']->links() !!}</ul></nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    @include('backend.partials.delete-ajax')
@endpush
@include('backend.partials.bulk-actions-ajax', ['bulkRoute' => 'watch-learn-template'])
