@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">My Responsibilities</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">My Responsibilities</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr><th>Title</th><th>Frequency</th><th>Description</th><th>This period</th><th class="action">{{ ___('common.action') }}</th></tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['duties'] as $duty)
                                    <tr>
                                        <td>{{ $duty->title }}</td>
                                        <td>{{ ucfirst($duty->freq) }}</td>
                                        <td>{{ $duty->description }}</td>
                                        <td>
                                            @if ($duty->isDoneForCurrentPeriod())
                                                <span class="badge-basic-success-text">Done</span>
                                            @else
                                                <span class="badge-basic-warning-text">Not yet</span>
                                            @endif
                                        </td>
                                        <td class="action">
                                            @unless ($duty->isDoneForCurrentPeriod())
                                                <form action="{{ route('portal-responsibilities.tick', $duty->id) }}" method="post">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm ot-btn-success">Mark Done</button>
                                                </form>
                                            @endunless
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center gray-color">
                                            <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                                            <p class="mb-0">You don't have any responsibilities assigned right now.</p>
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
