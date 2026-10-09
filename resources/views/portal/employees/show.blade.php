@extends('backend.master')

@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    @php($employee = $data['employee'])
    <div class="page-content">
        <div class="profile-content">
            <div class="d-flex flex-column flex-lg-row gap-4 gap-lg-0">

                <div class="profile-menu">
                    <div class="profile-menu-head">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <img class="img-fluid rounded-circle" src="{{ @globalAsset($employee->upload['path'] ?? null) }}" alt="{{ $employee->first_name }}">
                            </div>
                            <div class="flex-grow-1">
                                <div class="body">
                                    <h2 class="title">{{ trim($employee->first_name . ' ' . $employee->last_name) }}</h2>
                                    <p class="paragraph">{{ optional($employee->role)->name ?? '—' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-menu-body">
                        <nav>
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('portal-employees.index') }}">&larr; Back to Employees</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>

                <div class="profile-body">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="title">Employee Profile</h2>
                        @if (hasPermission('user_update'))
                            <a href="{{ route('users.edit', $employee->id) }}" class="btn btn-lg ot-btn-primary mb-5">
                                <span class="icon"><i class="fa-solid fa-pen-to-square"></i></span>
                                <span>{{ ___('common.edit') }}</span>
                            </a>
                        @endif
                    </div>

                    <div class="profile-body-form">
                        <div class="form-item">
                            <div class="d-flex justify-content-between align-content-center">
                                <div class="align-self-center">
                                    <h2 class="title">{{ ___('staff.staff_id') }}</h2>
                                    <p class="paragraph">{{ $employee->staff_id ?: '—' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="form-item">
                            <div class="d-flex justify-content-between align-content-center">
                                <div class="align-self-center">
                                    <h2 class="title">{{ ___('common.roles') }}</h2>
                                    <p class="paragraph">{{ optional($employee->role)->name ?? '—' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="form-item">
                            <div class="d-flex justify-content-between align-content-center">
                                <div class="align-self-center">
                                    <h2 class="title">{{ ___('staff.departments') }}</h2>
                                    <p class="paragraph">{{ optional($employee->department)->name ?? '—' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="form-item">
                            <div class="d-flex justify-content-between align-content-center">
                                <div class="align-self-center">
                                    <h2 class="title">{{ ___('staff.select_designation') }}</h2>
                                    <p class="paragraph">{{ optional($employee->designation)->name ?? '—' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="form-item">
                            <div class="d-flex justify-content-between align-content-center">
                                <div class="align-self-center">
                                    <h2 class="title">{{ ___('common.email') }}</h2>
                                    <p class="paragraph">{{ $employee->email ?: '—' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="form-item">
                            <div class="d-flex justify-content-between align-content-center">
                                <div class="align-self-center">
                                    <h2 class="title">{{ ___('common.phone') }}</h2>
                                    <p class="paragraph">{{ $employee->phone ?: '—' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="form-item">
                            <div class="d-flex justify-content-between align-content-center">
                                <div class="align-self-center">
                                    <h2 class="title">Joining date</h2>
                                    <p class="paragraph">{{ $employee->joining_date ? dateFormat($employee->joining_date) : '—' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="form-item">
                            <div class="d-flex justify-content-between align-content-center">
                                <div class="align-self-center">
                                    <h2 class="title">{{ ___('common.status') }}</h2>
                                    <p class="paragraph">
                                        @if ((int) $employee->status === \App\Enums\Status::ACTIVE)
                                            <span class="badge-basic-success-text">{{ ___('common.active') }}</span>
                                        @else
                                            <span class="badge-basic-danger-text">{{ ___('common.inactive') }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
