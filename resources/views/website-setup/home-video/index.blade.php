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
                        <p class="text-secondary mb-0" style="font-size:.85rem;">Paste a link — YouTube, Facebook, Instagram, or one you host yourself. Nothing is ever uploaded to this site.</p>
                    </div>
                    @if (hasPermission('home_video_create'))
                        <a href="{{ route('home-video.create') }}" class="btn btn-lg ot-btn-primary">
                            <span><i class="fa-solid fa-plus"></i> </span>
                            <span>{{ ___('common.add') }}</span>
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    @if (hasPermission('home_video_delete') || hasPermission('home_video_update'))
                        @include('backend.partials.bulk-actions-bar')
                    @endif
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    @if (hasPermission('home_video_delete') || hasPermission('home_video_update'))
                                        <th style="width:36px"><input type="checkbox" id="bulkSelectAll"></th>
                                    @endif
                                    <th>Title</th>
                                    <th>Link</th>
                                    <th>Type</th>
                                    <th>Orientation</th>
                                    <th>Autoplay</th>
                                    <th>{{ ___('common.status') }}</th>
                                    @if (hasPermission('home_video_update') || hasPermission('home_video_delete'))
                                        <th class="action">{{ ___('common.action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['videos'] as $row)
                                    @php $embed = $row->resolveEmbed(); @endphp
                                    <tr id="row_{{ $row->id }}">
                                        @if (hasPermission('home_video_delete') || hasPermission('home_video_update'))
                                            <td><input type="checkbox" class="bulk-row-checkbox" value="{{ $row->id }}"></td>
                                        @endif
                                        <td>{{ $row->title ?: '—' }}</td>
                                        <td><a href="{{ $row->video_url }}" target="_blank" rel="noopener" style="word-break:break-all;">{{ \Illuminate\Support\Str::limit($row->video_url, 45) }}</a></td>
                                        <td><span class="badge-basic-success-text">{{ ucfirst($embed['type']) }}</span></td>
                                        <td>{{ App\Models\WebsiteSetup\HomeVideo::ORIENTATIONS[$row->orientation] ?? $row->orientation }}</td>
                                        <td>
                                            @if ($row->autoplay && $row->autoplaySupported())
                                                <span class="badge-basic-success-text">On</span>
                                            @elseif ($row->autoplay)
                                                <span class="badge-basic-warning-text" title="Instagram embeds can't autoplay">Requested, not supported</span>
                                            @else
                                                <span class="text-secondary">Off</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($row->status == App\Enums\Status::ACTIVE)
                                                <span class="badge-basic-success-text">{{ ___('common.active') }}</span>
                                            @else
                                                <span class="badge-basic-danger-text">{{ ___('common.inactive') }}</span>
                                            @endif
                                        </td>
                                        @if (hasPermission('home_video_update') || hasPermission('home_video_delete'))
                                            <td class="action">
                                                <div class="dropdown dropdown-action">
                                                    <button type="button" class="btn-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fa-solid fa-ellipsis"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        @if (hasPermission('home_video_update'))
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('home-video.edit', $row->id) }}">
                                                                    <span class="icon mr-8"><i class="fa-solid fa-pen-to-square"></i></span>{{ ___('common.edit') }}
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if (hasPermission('home_video_delete'))
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0);"
                                                                    onclick="delete_row('home-video/delete', {{ $row->id }})">
                                                                    <span class="icon mr-8"><i class="fa-solid fa-trash-can"></i></span>
                                                                    <span>{{ ___('common.delete') }}</span>
                                                                </a>
                                                            </li>
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
                        <nav>
                            <ul class="pagination justify-content-between">
                                {!! $data['videos']->links() !!}
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    @include('backend.partials.delete-ajax')
@endpush
@include('backend.partials.bulk-actions-ajax', ['bulkRoute' => 'home-video'])
