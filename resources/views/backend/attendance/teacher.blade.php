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

                            <div class="card_header_right d-flex align-items-center gap-3 flex-fill justify-content-end flex-wrap">
                                <div class="single_large_selectBox">
                                    <select class="nice-select niceSelect bordered_style wide" name="role" onchange="this.form.submit()">
                                        <option value="">{{ ___('common.All') }}</option>
                                        @foreach ($data['roles'] as $role)
                                            <option {{ (string) $data['request']->role === (string) $role->id ? 'selected' : '' }}
                                                value="{{ $role->id }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="single_large_selectBox">
                                    <select class="nice-select niceSelect bordered_style wide" name="staff">
                                        <option value="">{{ ___('settings.All Staff') }}</option>
                                        @foreach ($data['staffs'] as $staff)
                                            <option {{ (string) $data['request']->staff === (string) $staff->id ? 'selected' : '' }}
                                                value="{{ $staff->id }}">{{ trim($staff->first_name . ' ' . $staff->last_name) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="single_large_selectBox">
                                    <label class="form-label d-block mb-1">{{ ___('common.Start Date') }}</label>
                                    <input value="{{ $data['request']->date_from }}" name="date_from"
                                        class="form-control ot-input" type="date">
                                </div>

                                <div class="single_large_selectBox">
                                    <label class="form-label d-block mb-1">{{ ___('common.End Date') }}</label>
                                    <input value="{{ $data['request']->date_to }}" name="date_to"
                                        class="form-control ot-input" type="date">
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

        @if (count($data['rows']) > 0)
            <div class="download_print_btns mb-24">
                <form action="{{ route('attendance.teacher-export') }}" method="post">
                    @csrf
                    <input type="hidden" name="role" value="{{ $data['request']->role }}">
                    <input type="hidden" name="staff" value="{{ $data['request']->staff }}">
                    <input type="hidden" name="date_from" value="{{ $data['request']->date_from }}">
                    <input type="hidden" name="date_to" value="{{ $data['request']->date_to }}">
                    <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('common.export_excel') }}
                        <span><i class="fa-solid fa-file-excel"></i></span></button>
                </form>
            </div>
        @endif

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-header"><h4 class="mb-0">{{ $data['title'] }}</h4></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="ot-basic-table ot-table-bg">
                            <thead class="thead">
                                <tr>
                                    <th>{{ ___('common.name') }}</th>
                                    <th>{{ ___('common.Role') }}</th>
                                    <th>{{ ___('common.date') }}</th>
                                    <th>{{ ___('settings.First Login') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['rows'] as $row)
                                    <tr>
                                        <td>{{ trim(($row->staff->first_name ?? '') . ' ' . ($row->staff->last_name ?? '')) }}</td>
                                        <td>{{ optional($row->staff->role ?? null)->name ?? '—' }}</td>
                                        <td>{{ dateFormat($row->date) }}</td>
                                        <td>{{ $row->first_login_at ? $row->first_login_at->format('h:i A') : '—' }}</td>
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
                        {{ $data['rows']->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
