@extends('backend.master')

@section('title')
    Assessment System - Staff Access
@endsection

@section('content')
    <div class="table-content table-basic mt-20">
        <div class="card shadow-sm rounded-4 mb-4">
            <div class="card-header bg-light p-4 rounded-top-4">
                <h4 class="mb-0"><i class="fa-solid fa-user-shield me-2 text-primary"></i>Which staff roles can see the Assessment System</h4>
            </div>
            <div class="card-body p-4">
                @if (session('deploySuccess'))
                    <div class="alert alert-success">{{ session('deploySuccess') }}</div>
                @endif

                <p class="text-muted">
                    Administrators always have access. Check every other role below that should
                    see the <b>Assessment System</b> link and be able to open it - everything else stays hidden.
                </p>
                <p class="text-muted">
                    Which <b>students</b> can see it is controlled separately, on the existing
                    <a href="{{ route('student-feature-access.index') }}">Student Feature Access</a> page
                    (look for the "Assessment System" tab there).
                </p>

                <form method="POST" action="{{ route('assessment-system.access-update') }}" class="mt-4">
                    @csrf
                    @forelse ($roles as $role)
                        <div class="form-check mb-2">
                            <input type="checkbox" name="role_ids[]" value="{{ $role->id }}"
                                   id="role_{{ $role->id }}" class="form-check-input"
                                   {{ in_array((int) $role->id, $allowed, true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="role_{{ $role->id }}">{{ $role->name }}</label>
                        </div>
                    @empty
                        <p class="text-muted">No other roles found.</p>
                    @endforelse

                    <button type="submit" class="btn ot-btn-primary mt-3">Save</button>
                </form>
            </div>
        </div>
    </div>
@endsection
