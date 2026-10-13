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
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"> {{ ___('common.home') }} </a></li>
                            <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">{{ $data['title'] }}</a></li>
                            <li class="breadcrumb-item">{{ ___('common.edit') }}</li>

                        </ol>
                </div>
            </div>
        </div>
        {{-- bradecrumb Area E n d --}}

        <div class="card ot-card">
            <div class="card-body">
                <form action="{{ route('user.permission.update', @$data['staff']->user->id) }}" enctype="multipart/form-data" method="post"
                      id="visitForm">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-lg-3">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="exampleDataList" class="form-label ">{{ ___('common.name') }} <span
                                            class="fillable">*</span></label>
                                    <input readonly class="form-control ot-input @error('name') is-invalid @enderror" name="name"
                                           value="{{ @$data['staff']->user->name }}" list="datalistOptions" id="exampleDataList"
                                           placeholder="{{ ___('common.enter_name') }}">
                                    @error('name')
                                    <div id="validationServer04Feedback" class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="exampleDataList" class="form-label ">{{ ___('common.role') }} <span
                                            class="fillable">*</span></label>
                                    <input readonly class="form-control ot-input @error('name') is-invalid @enderror" name="name"
                                           value="{{ @$data['role']->name }}" list="datalistOptions" id="exampleDataList"
                                           placeholder="{{ ___('common.enter_name') }}">
                                    @error('name')
                                    <div id="validationServer04Feedback" class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div>

                            </div>
                        </div>
                        <div class="col-lg-9">
                            @include('backend.roles._permission-picker', ['permissionGroups' => $data['permissionGroups'], 'selectedPermissions' => $data['user_permissions'] ?? []])
                        </div>

                        <div class="col-md-12 mt-24">
                            <div class="text-end">
                                <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i>
                                    </span>{{ ___('common.update') }}</button>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>
@endsection
