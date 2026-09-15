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
                        <li class="breadcrumb-item"><a href="{{ route('ai-helper.index') }}">{{ ___('settings.ai_helper') }}</a></li>
                        <li class="breadcrumb-item">{{ $data['title'] }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="table-content table-basic mt-20">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ $data['title'] }}</h4>
                    <a href="{{ route('ai-helper.index') }}" class="btn ot-btn-primary">Back to AI Helper Settings</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    <th>{{ ___('common.sr_no') }}</th>
                                    <th>Date &amp; Time</th>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Tool</th>
                                    <th>What was asked</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['logs'] as $key => $row)
                                    <tr>
                                        <td>{{ $data['logs']->firstItem() + $key }}</td>
                                        <td>{{ $row->created_at->format('d M Y, h:i A') }}</td>
                                        <td>{{ $row->user_name ?: '—' }}</td>
                                        <td>
                                            @if ($row->user_role === 'teacher')
                                                <span class="badge-basic-success-text">Teacher</span>
                                            @else
                                                <span class="badge-basic-primary-text">Student</span>
                                            @endif
                                        </td>
                                        <td>{{ str_replace('_', ' ', $row->tool) }}</td>
                                        <td>{{ $row->summary }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center gray-color">
                                            <p class="mb-0 text-center">{{ ___('common.no_data_available') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="ot-pagination pagination-content d-flex justify-content-end align-content-center py-3">
                        <nav aria-label="Page navigation example">
                            <ul class="pagination justify-content-between">
                                {!! $data['logs']->links() !!}
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
