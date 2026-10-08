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
                        <h4 class="mb-0">Approve Class Content</h4>
                        <p class="text-secondary mb-0" style="font-size:.85rem;">Everything here has already been checked by a coordinator. Approving makes it visible to students and parents.</p>
                    </div>
                    <div class="btn-group">
                        <a href="{{ route('class-content-admin.index') }}" class="btn btn-sm {{ !$data['status'] ? 'ot-btn-primary' : 'btn-outline-secondary' }}">Awaiting approval ({{ $data['counts'][\App\Models\ClassContent\ClassContentModule::COORDINATOR_REVIEWED] }})</a>
                        <a href="{{ route('class-content-admin.index', ['status' => \App\Models\ClassContent\ClassContentModule::APPROVED]) }}" class="btn btn-sm {{ $data['status'] === \App\Models\ClassContent\ClassContentModule::APPROVED ? 'ot-btn-primary' : 'btn-outline-secondary' }}">Approved ({{ $data['counts'][\App\Models\ClassContent\ClassContentModule::APPROVED] }})</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    <th>Title</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                    <th>Coordinator</th>
                                    <th>Status</th>
                                    <th>Sent to admin</th>
                                    <th class="action">{{ ___('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['modules'] as $row)
                                    <tr id="row_{{ $row->id }}">
                                        <td>{{ $row->title }}</td>
                                        <td>{{ optional($row->class)->name }}{{ $row->section ? ' - ' . optional($row->section)->name : '' }}</td>
                                        <td>{{ optional($row->subject)->name }}</td>
                                        <td>{{ trim(optional($row->creator)->first_name . ' ' . optional($row->creator)->last_name) ?: '—' }}</td>
                                        <td>{{ trim(optional($row->coordinator)->first_name . ' ' . optional($row->coordinator)->last_name) ?: '—' }}</td>
                                        <td>@include('class-content._status-badge', ['status' => $row->review_status])</td>
                                        <td class="text-secondary" style="font-size:.85rem;">{{ $row->coordinator_reviewed_at ? $row->coordinator_reviewed_at->diffForHumans() : '—' }}</td>
                                        <td class="action">
                                            <a href="{{ route('class-content-admin.show', $row->id) }}" class="btn btn-sm ot-btn-primary">Review</a>
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
                                {!! $data['modules']->links() !!}
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
