@extends('parent-panel.partials.master')
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

        <div class="card ot-card mb-3">
            <div class="card-body">
                <h6 class="mb-2">Select your child</h6>
                <form action="{{ route('parent-panel-class-content.search') }}" method="post" id="cf">
                    @csrf
                    <input type="hidden" name="student" id="sel_student">

                    @if ($data['students']->isNotEmpty())
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach ($data['students'] as $child)
                                <button type="button"
                                    class="btn {{ $data['student'] && $data['student']->id == $child->id ? 'ot-btn-primary' : 'btn-outline-secondary' }}"
                                    onclick="document.getElementById('sel_student').value={{ $child->id }};document.getElementById('cf').submit();">
                                    {{ $child->first_name }} {{ $child->last_name }}
                                </button>
                            @endforeach
                        </div>
                    @else
                        <p class="text-secondary mb-0">No children are linked to this account. Please contact the school administrator.</p>
                    @endif
                </form>
            </div>
        </div>

        @if ($data['student'])
            <p class="text-secondary mb-3">Modules, lessons and materials {{ $data['student']->first_name }}'s teachers have shared.</p>

            @if ($data['modules']->isEmpty())
                <div class="card ot-card">
                    <div class="card-body text-center">
                        <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                        <p class="mb-0">No class content has been shared yet.</p>
                    </div>
                </div>
            @else
                <div class="row g-3">
                    @foreach ($data['modules'] as $row)
                        <div class="col-md-6 col-lg-4">
                            <div class="card ot-card h-100 cc-module-card">
                                <div class="card-body d-flex flex-column">
                                    <div class="cc-module-icon"><i class="fa-solid fa-book-open"></i></div>
                                    <h5 class="mb-1">{{ $row->title }}</h5>
                                    <p class="text-secondary mb-2" style="font-size:.85rem;">
                                        {{ optional($row->class)->name }}{{ $row->section ? ' - ' . optional($row->section)->name : '' }} — {{ optional($row->subject)->name }}
                                    </p>
                                    @if ($row->description)
                                        <p class="mb-3" style="font-size:.9rem;">{{ \Illuminate\Support\Str::limit($row->description, 110) }}</p>
                                    @endif
                                    <div class="mt-auto d-flex justify-content-between align-items-center">
                                        <span class="badge-basic-success-text">{{ $row->lessons_count }} {{ \Illuminate\Support\Str::plural('lesson', $row->lessons_count) }}</span>
                                        <a href="{{ route('parent-panel-class-content.show', $row->id) }}" class="btn btn-sm ot-btn-primary">Open</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
@endsection

@push('css')
<style>
    .cc-module-card { border-top: 3px solid #2563eb; }
    .cc-module-icon {
        width: 36px; height: 36px; border-radius: 9px; background: #eff6ff; color: #2563eb;
        display: flex; align-items: center; justify-content: center; font-size: 14px; margin-bottom: 8px;
    }
</style>
@endpush
