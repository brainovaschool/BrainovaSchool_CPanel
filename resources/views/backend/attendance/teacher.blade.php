@extends('backend.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    <div class="page-content">
        {{-- bradecrumb Area S t a r t --}}
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
        {{-- bradecrumb Area E n d --}}

        <div class="row">
            <div class="col-12">
                <div class="card ot-card mb-24 position-relative z_1">
                    <form action="{{ route('attendance.teacher') }}" method="get">
                        <div class="card-header d-flex align-items-center gap-4 flex-wrap">
                            <h3 class="mb-0">{{ ___('common.Filtering') }}</h3>

                            <div class="card_header_right d-flex align-items-end gap-3 flex-wrap justify-content-end">
                                <div class="single_large_selectBox">
                                    <label class="form-label d-block mb-1">{{ ___('common.Role') }}</label>
                                    <select class="nice-select niceSelect bordered_style wide" name="role">
                                        <option value="">{{ ___('common.All') }}</option>
                                        @foreach ($data['roles'] as $role)
                                            <option {{ (string) $data['request']->role === (string) $role->id ? 'selected' : '' }}
                                                value="{{ $role->id }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="single_large_selectBox">
                                    <label class="form-label d-block mb-1">{{ ___('settings.Staff') }}</label>
                                    <select class="nice-select niceSelect bordered_style wide" name="staff">
                                        <option value="">{{ ___('common.All') }}</option>
                                        @foreach ($data['staffs'] as $staff)
                                            <option {{ (string) $data['request']->staff === (string) $staff->id ? 'selected' : '' }}
                                                value="{{ $staff->id }}">{{ trim($staff->first_name . ' ' . $staff->last_name) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="single_large_selectBox">
                                    <label class="form-label d-block mb-1">{{ ___('common.Month') }}</label>
                                    <input value="{{ $data['request']->month }}" name="month"
                                        class="form-control ot-input" type="month">
                                </div>

                                <button class="btn btn-lg ot-btn-primary">
                                    {{ ___('common.Search') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if (count($data['staffList']) > 0)
            <div class="download_print_btns mb-24">
                <form action="{{ route('attendance.teacher-export') }}" method="post">
                    @csrf
                    <input type="hidden" name="role" value="{{ $data['request']->role }}">
                    <input type="hidden" name="staff" value="{{ $data['request']->staff }}">
                    <input type="hidden" name="month" value="{{ $data['request']->month }}">
                    <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('common.export_excel') }}
                        <span><i class="fa-solid fa-file-excel"></i></span></button>
                </form>
            </div>
        @endif

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-header"><h4 class="mb-0">{{ $data['title'] }}</h4></div>
                <div class="card-body">
                    <div class="text-end mb-3">
                        <strong>
                            <span class="text-success">{{ ___('common.Present') }} = P</span>
                            <span class="text-danger">{{ ___('common.Absent') }} = A</span>
                            <span class="text-primary">{{ ___('common.Leave') }} = L</span>
                            <span class="text-warning">{{ ___('common.Half Leave') }} = H</span>
                        </strong>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="purchase">{{ ___('common.name') }}</th>
                                    <th class="purchase">{{ ___('common.Role') }}</th>
                                    @foreach ($data['days'] as $date)
                                        <th>{{ \Carbon\Carbon::parse($date)->format('j') }}</th>
                                    @endforeach
                                    <th class="purchase text-success">P</th>
                                    <th class="purchase text-danger">A</th>
                                    <th class="purchase text-primary">L</th>
                                    <th class="purchase text-warning">H</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data['staffList'] as $staff)
                                    <tr>
                                        <td>{{ trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? '')) }}</td>
                                        <td>{{ optional($staff->role)->name ?? '—' }}</td>
                                        @foreach ($data['days'] as $date)
                                            @php($status = $data['statusGrid'][$staff->id][$date] ?? 'A')
                                            <td>
                                                @if ($status === 'P')
                                                    <span class="text-success">P</span>
                                                @elseif ($status === 'L')
                                                    <span class="text-primary">L</span>
                                                @elseif ($status === 'H')
                                                    <span class="text-warning">H</span>
                                                @else
                                                    <span class="text-danger">A</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td><span class="text-success">{{ $data['summary'][$staff->id]['P'] ?? 0 }}</span></td>
                                        <td><span class="text-danger">{{ $data['summary'][$staff->id]['A'] ?? 0 }}</span></td>
                                        <td><span class="text-primary">{{ $data['summary'][$staff->id]['L'] ?? 0 }}</span></td>
                                        <td><span class="text-warning">{{ $data['summary'][$staff->id]['H'] ?? 0 }}</span></td>
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
                    <div class="pagination-area mt-20">
                        {{ $data['staffList']->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
