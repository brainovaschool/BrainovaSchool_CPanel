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
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h4 class="mb-0">Course Lessons — Coordinator Review</h4>
                        <p class="text-secondary mb-0" style="font-size:.85rem;">Check a teacher's course lesson in detail, leave feedback, and approve it to make it live on Course Details.</p>
                    </div>
                    @php
                        $needsAttention = $data['counts'][\App\Models\WebsiteSetup\ProgramClass::SUBMITTED] + $data['counts'][\App\Models\WebsiteSetup\ProgramClass::CHANGES_REQUESTED];
                    @endphp
                    <div class="btn-group">
                        <a href="{{ route('program-class-coordinator.index') }}" class="btn btn-sm {{ !$data['status'] ? 'ot-btn-primary' : 'btn-outline-secondary' }}">Needs attention ({{ $needsAttention }})</a>
                        <a href="{{ route('program-class-coordinator.index', ['status' => \App\Models\WebsiteSetup\ProgramClass::APPROVED]) }}" class="btn btn-sm {{ $data['status'] === \App\Models\WebsiteSetup\ProgramClass::APPROVED ? 'ot-btn-primary' : 'btn-outline-secondary' }}">Approved ({{ $data['counts'][\App\Models\WebsiteSetup\ProgramClass::APPROVED] }})</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    <th>Lesson</th>
                                    <th>Course</th>
                                    <th>Teacher</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th class="action">{{ ___('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['classes'] as $row)
                                    <tr id="row_{{ $row->id }}">
                                        <td>{{ $row->title }}</td>
                                        <td>{{ optional($row->program)->title }}</td>
                                        <td>{{ trim(optional($row->creator)->first_name . ' ' . optional($row->creator)->last_name) ?: '—' }}</td>
                                        <td>@include('program-class._status-badge', ['status' => $row->review_status])</td>
                                        <td class="text-secondary" style="font-size:.85rem;">{{ $row->submitted_at ? $row->submitted_at->diffForHumans() : '—' }}</td>
                                        <td class="action">
                                            <a href="{{ route('program-class-coordinator.show', $row->id) }}" class="btn btn-sm ot-btn-primary">Review</a>
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
                                {!! $data['classes']->links() !!}
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
