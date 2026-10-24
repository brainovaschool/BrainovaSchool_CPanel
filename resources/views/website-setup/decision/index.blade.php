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

        <p class="text-secondary mb-24">
            Controls which extra details a role can see about another role's records — for example, a Guardian viewing
            their child's leave never needs to see a staff-only field like the replacement work day.
        </p>

        <div class="row">
            <div class="col-3">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Add Rule</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('decision.store') }}" method="post">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Category <span class="fillable">*</span></label>
                                <select class="nice-select niceSelect bordered_style wide @error('category') is-invalid @enderror" name="category">
                                    <option value="">Select Category</option>
                                    @foreach ($data['categories'] as $key => $label)
                                        <option value="{{ $key }}" {{ old('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('category')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Whose Record (subject)</label>
                                <select class="nice-select niceSelect bordered_style wide" name="subject_role_id">
                                    <option value="">Any Role</option>
                                    @foreach ($data['roles'] as $role)
                                        <option value="{{ $role->id }}" {{ old('subject_role_id') == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Who Sees It (viewer)</label>
                                <select class="nice-select niceSelect bordered_style wide" name="viewer_role_id">
                                    <option value="">Any Role</option>
                                    @foreach ($data['roles'] as $role)
                                        <option value="{{ $role->id }}" {{ old('viewer_role_id') == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label d-block">Fields To Show</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="show_replacement_day" value="1" id="show_replacement_day">
                                    <label class="form-check-label" for="show_replacement_day">Replacement Day</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="show_half_day_full_day" value="1" id="show_half_day_full_day">
                                    <label class="form-check-label" for="show_half_day_full_day">Half Day / Full Day</label>
                                </div>
                            </div>

                            <button class="btn btn-lg ot-btn-primary" type="submit">{{ ___('common.save') }}</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-9">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Configured Rules</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered role-table">
                                <thead class="thead">
                                    <tr>
                                        <th>Category</th>
                                        <th>Whose Record</th>
                                        <th>Who Sees It</th>
                                        <th>Replacement Day</th>
                                        <th>Half Day / Full Day</th>
                                        <th class="action">{{ ___('common.Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="tbody">
                                    @forelse ($data['rules'] as $rule)
                                        <tr>
                                            <td>{{ $data['categories'][$rule->category] ?? $rule->category }}</td>
                                            <td>{{ optional($rule->subjectRole)->name ?? 'Any Role' }}</td>
                                            <td>{{ optional($rule->viewerRole)->name ?? 'Any Role' }}</td>
                                            <td>{{ $rule->show_replacement_day ? 'Yes' : 'No' }}</td>
                                            <td>{{ $rule->show_half_day_full_day ? 'Yes' : 'No' }}</td>
                                            <td class="action">
                                                <form action="{{ route('decision.delete', $rule->id) }}" method="post" onsubmit="return confirm('{{ ___('alert.are_you_sure') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="dropdown-item" type="submit"><i class="fa-solid fa-trash-can"></i> {{ ___('common.delete') }}</button>
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
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
