@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
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
                    <div>
                        <a href="{{ route('portal-social-settings.edit') }}" class="btn btn-outline-secondary me-2">Global Instructions</a>
                        <a href="{{ route('portal-social-reel-characters.create') }}" class="btn ot-btn-primary">Add Character</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="ot-basic-table ot-table-bg">
                            <thead class="thead">
                                <tr>
                                    <th>{{ ___('common.sr_no') }}</th>
                                    <th>Portrait</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Sort</th>
                                    <th>{{ ___('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['characters'] as $key => $character)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>
                                            @if ($character->portrait)
                                                <img src="{{ globalAsset($character->portrait->path) }}" style="height:50px;">
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $character->name }}</td>
                                        <td>
                                            @if ($character->status)
                                                <span class="badge-basic-success-text">Active</span>
                                            @else
                                                <span class="badge-basic-danger-text">Inactive</span>
                                            @endif
                                        </td>
                                        <td>{{ $character->sort_order }}</td>
                                        <td>
                                            <a href="{{ route('portal-social-reel-characters.edit', $character->id) }}" class="btn btn-sm ot-btn-primary">{{ ___('common.edit') }}</a>
                                            <form action="{{ route('portal-social-reel-characters.destroy', $character->id) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this character?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">{{ ___('common.delete') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center gray-color">
                                            <p class="mb-0 text-center">No characters yet — add one to get started.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
