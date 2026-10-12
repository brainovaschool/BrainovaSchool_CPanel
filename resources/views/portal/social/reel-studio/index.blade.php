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
                    <h4 class="mb-0">Your Reels</h4>
                    <a href="{{ route('portal-social-reel-studio.create') }}" class="btn ot-btn-primary">New Reel</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    <th>{{ ___('common.sr_no') }}</th>
                                    <th>Title</th>
                                    <th>Topic</th>
                                    <th>By</th>
                                    <th>Scenes</th>
                                    <th>{{ ___('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['projects'] as $key => $project)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $project->title }}</td>
                                        <td>{{ $project->topic }}</td>
                                        <td>{{ $project->creator->name ?? '—' }}</td>
                                        <td>{{ $project->scenes()->count() }}</td>
                                        <td>
                                            <a href="{{ route('portal-social-reel-studio.show', $project->id) }}" class="btn btn-sm ot-btn-primary">Open</a>
                                            <form action="{{ route('portal-social-reel-studio.destroy', $project->id) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this reel and all its scenes?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">{{ ___('common.delete') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center gray-color">
                                            <p class="mb-0 text-center">No reels yet — create one to get started.</p>
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
