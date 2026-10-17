@extends('backend.master')

@section('title')
    {{ $title }}
@endsection

@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $title }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">{{ $title }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-header">
                <h4>Pick a school</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">Choose which school to manage, then check the modules its staff can see. Unchecking a module hides it everywhere for that school -- its sidebar link and the pages themselves.</p>

                <form method="GET" action="{{ route('school-features.index') }}" class="mb-4" style="max-width:420px">
                    <select name="school" class="form-select ot-input" onchange="this.form.submit()">
                        @foreach ($tenants as $t)
                            <option value="{{ $t['id'] }}" {{ $selectedId === $t['id'] ? 'selected' : '' }}>
                                {{ $t['name'] }} ({{ $t['domain'] }})
                            </option>
                        @endforeach
                    </select>
                </form>

                @if (session('success'))
                    <div class="alert alert-success" style="max-width:600px;word-break:break-word">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger" style="max-width:600px">{{ session('error') }}</div>
                @endif

                @if ($selectedId)
                    <form method="POST" action="{{ route('school-features.reset-password') }}" class="mb-4"
                        onsubmit="return confirm('Reset the Super Admin password for this school? Their current password will stop working immediately.')">
                        @csrf
                        <input type="hidden" name="tenant_id" value="{{ $selectedId }}">
                        <button type="submit" class="btn btn-outline-danger btn-sm">Reset Super Admin Password</button>
                    </form>
                @endif

                @if ($selectedId)
                    <form method="POST" action="{{ route('school-features.update') }}" style="max-width:420px">
                        @csrf
                        <input type="hidden" name="tenant_id" value="{{ $selectedId }}">

                        <div class="row">
                            @foreach ($allFeatures as $key => $label)
                                <div class="col-12">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="features[]" value="{{ $key }}"
                                            id="feature_{{ $key }}" {{ in_array($key, $selectedFeatures) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="feature_{{ $key }}">{{ $label }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="submit" class="btn ot-btn-primary mt-3">{{ ___('common.save') }}</button>
                    </form>
                @else
                    <p class="text-muted">No schools registered yet.</p>
                @endif
            </div>
        </div>

    </div>
@endsection
