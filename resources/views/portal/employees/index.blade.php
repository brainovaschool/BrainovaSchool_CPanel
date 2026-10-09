@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">Team Portal</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Employees</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="col-12 p-0">
            <form action="{{ route('portal-employees.index') }}" method="get">
                <div class="card ot-card mb-24 position-relative z_1">
                    <div class="card-header d-flex align-items-center gap-4 flex-wrap">
                        <h3 class="mb-0">{{ ___('common.Filtering') }}</h3>

                        <div class="card_header_right d-flex align-items-center gap-3 flex-fill justify-content-end flex-wrap">
                            <div class="single_large_selectBox">
                                <select class="nice-select niceSelect bordered_style wide" name="role_id">
                                    <option value="">All roles</option>
                                    @foreach ($data['roles'] as $role)
                                        <option value="{{ $role->id }}" {{ (int) $data['selectedRole'] === $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="single_large_selectBox">
                                <input type="text" class="ot-input" name="search" placeholder="Search name or email" value="{{ $data['search'] }}">
                            </div>

                            <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('common.Search') }}</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h4 class="mb-0">Employees</h4>
                        <p class="text-secondary mb-0" style="font-size:.85rem;">Everyone with a Staff account — Teacher, Accounting, HR, or any role you create. Reusing the existing Staff records, nothing new to maintain here.</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    <th>{{ ___('common.image') }}</th>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Department</th>
                                    <th>Designation</th>
                                    <th>Email / Phone</th>
                                    <th>{{ ___('common.status') }}</th>
                                    <th class="action">{{ ___('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['employees'] as $employee)
                                    <tr>
                                        <td>
                                            <div class="user-avatar">
                                                <img src="{{ @globalAsset($employee->upload['path'] ?? null, '40X40.webp') }}" alt="{{ $employee->first_name }}">
                                            </div>
                                        </td>
                                        <td>{{ trim($employee->first_name . ' ' . $employee->last_name) ?: '—' }}</td>
                                        <td>{{ optional($employee->role)->name ?? '—' }}</td>
                                        <td>{{ optional($employee->department)->name ?? '—' }}</td>
                                        <td>{{ optional($employee->designation)->name ?? '—' }}</td>
                                        <td>{{ $employee->email ?: '—' }}<br>{{ $employee->phone ?: '' }}</td>
                                        <td>
                                            @if ((int) $employee->status === \App\Enums\Status::ACTIVE)
                                                <span class="badge-basic-success-text">{{ ___('common.active') }}</span>
                                            @else
                                                <span class="badge-basic-danger-text">{{ ___('common.inactive') }}</span>
                                            @endif
                                        </td>
                                        <td class="action">
                                            <a href="{{ route('portal-employees.show', $employee->id) }}" class="btn btn-sm ot-btn-primary">View</a>
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
                                {!! $data['employees']->appends(\Request::capture()->except('page'))->links() !!}
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
