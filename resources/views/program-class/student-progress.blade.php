@extends('backend.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">{{ $data['student']->full_name }} — {{ $data['program']->title }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item active">Student Progress</li>
                    </ol>
                </div>
                <div class="col-sm-4 text-sm-end">
                    <a href="{{ app(\App\Repositories\WebsiteSetup\ProgramClassRepository::class)->courseDetailsUrl($data['program']) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Back to Course Details
                    </a>
                </div>
            </div>
        </div>

        @php($pr = $data['progress'])

        <div class="cc-pr-cards mb-3">
            <div class="cc-pr-stat">
                <p class="cc-pr-stat-label">Items Graded</p>
                <p class="cc-pr-stat-value">{{ $pr['graded'] }}/{{ $pr['totalSlots'] }}</p>
            </div>
            <div class="cc-pr-stat">
                <p class="cc-pr-stat-label">Average</p>
                <p class="cc-pr-stat-value">{{ $pr['pct'] !== null ? $pr['pct'] . '%' : '—' }}</p>
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-body">
                @if (empty($pr['lessonRows']))
                    <p class="text-secondary mb-0">No gradable lessons in this course yet.</p>
                @else
                    <div class="table-responsive">
                        <table class="cc-pr-table">
                            <thead>
                                <tr>
                                    <th>Lesson</th>
                                    <th>Item</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pr['lessonRows'] as $row)
                                    @foreach ($row['items'] as $item)
                                        <tr>
                                            <td>{{ $row['class']->title }}</td>
                                            <td>{{ $item['title'] }}</td>
                                            <td>
                                                @if ($item['marks'] !== null)
                                                    {{ $item['marks'] }}{{ $item['max_marks'] ? ' / ' . $item['max_marks'] : '' }}
                                                @else
                                                    <span class="text-secondary">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($item['marks'] !== null)
                                                    <span class="text-success">Graded</span>
                                                @elseif ($item['mark'] && $item['mark']->isSubmitted())
                                                    <a href="{{ $item['mark']->downloadUrl() }}" target="_blank" rel="noopener">Submitted — view</a>
                                                @else
                                                    <span class="text-secondary">Not submitted</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('css')
<style>
    .cc-pr-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; }
    .cc-pr-stat {
        background: #ffffff;
        border: 1px solid var(--ot-border-basic-table, #e4e7ec);
        border-radius: 16px;
        padding: 1rem 1.1rem;
    }
    .cc-pr-stat-label { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #8b93a3; margin: 0 0 0.35rem; }
    .cc-pr-stat-value { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; }
    .cc-pr-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .cc-pr-table th { text-align: left; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; color: #8b93a3; padding: 0.5rem 0.6rem; border-bottom: 1px solid var(--ot-border-basic-table, #e4e7ec); }
    .cc-pr-table td { padding: 0.55rem 0.6rem; border-bottom: 1px solid #f1f3f6; color: #334155; }
    .cc-pr-table tbody tr:last-child td { border-bottom: none; }
</style>
@endpush
