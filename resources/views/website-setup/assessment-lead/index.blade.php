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
                    <form method="get" class="d-flex align-items-center gap-2">
                        <select name="status" class="form-control" onchange="this.form.submit()" style="min-width:180px">
                            <option value="">All statuses</option>
                            @foreach ($data['statuses'] as $key => $label)
                                <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    <th class="serial">{{ ___('common.sr_no') }}</th>
                                    <th>Student</th>
                                    <th>Grade</th>
                                    <th>Subject</th>
                                    <th>Syllabus</th>
                                    <th>Slot</th>
                                    <th>Assigned assessment</th>
                                    <th>Status</th>
                                    <th>Link sent</th>
                                    <th class="action">{{ ___('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['leads'] as $key => $row)
                                    <tr>
                                        <td class="serial">{{ ++$key }}</td>
                                        <td>
                                            <b>{{ $row->student_name }}</b>
                                            <div class="text-secondary" style="font-size:12px">{{ $row->guardian_name }} · {{ $row->email }} · {{ $row->whatsapp_number }}</div>
                                        </td>
                                        <td>Grade {{ $row->grade }}</td>
                                        <td>{{ $row->subject }}</td>
                                        <td>{{ $row->syllabus }}</td>
                                        <td>{{ optional($row->slot)->slot_date?->format('d M Y') }} <br><span class="text-secondary" style="font-size:12px">{{ optional($row->slot)->time_label }}</span></td>
                                        <td>
                                            @if ($row->assigned_assessment_title)
                                                {{ $row->assigned_assessment_title }}
                                            @else
                                                <span class="badge-basic-danger-text">Unmatched — needs a published assessment for Grade {{ $row->grade }}</span>
                                            @endif
                                        </td>
                                        <td><span class="badge-basic-success-text">{{ $row->status_label }}</span></td>
                                        <td>{{ $row->link_sent_at?->format('d M, H:i') ?? '—' }}</td>
                                        <td class="action">
                                            <form method="post" action="{{ route('assessment-lead.resend', $row->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm ot-btn-primary">Resend link</button>
                                            </form>
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
                        <nav><ul class="pagination justify-content-between">{!! $data['leads']->links() !!}</ul></nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
