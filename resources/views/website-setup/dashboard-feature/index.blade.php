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

        <div class="table-content table-basic mt-20">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h4 class="mb-0">{{ $data['title'] }}</h4>
                    <div class="btn-group">
                        <a href="{{ route('dashboard-features.index', ['portal' => 'student']) }}"
                            class="btn {{ $data['portal'] === 'student' ? 'ot-btn-primary' : 'btn-outline-secondary' }}">{{ ___('common.student') }}</a>
                        <a href="{{ route('dashboard-features.index', ['portal' => 'teacher']) }}"
                            class="btn {{ $data['portal'] === 'teacher' ? 'ot-btn-primary' : 'btn-outline-secondary' }}">{{ ___('common.teacher') }}</a>
                        <a href="{{ route('dashboard-features.index', ['portal' => 'parent']) }}"
                            class="btn {{ $data['portal'] === 'parent' ? 'ot-btn-primary' : 'btn-outline-secondary' }}">{{ ___('common.parent') }}</a>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-secondary mb-3">
                        Turn dashboard sections on or off per portal. Disabled sections simply stop showing to that portal's users — nothing is deleted.
                        Removing a row from this list resets that section back to its default of "enabled" (it reappears here, still switched on, the next time the site's data is migrated).
                    </p>

                    @if (hasPermission('dashboard_features_delete') || hasPermission('dashboard_features_update'))
                        @include('backend.partials.bulk-actions-bar')
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    @if (hasPermission('dashboard_features_delete') || hasPermission('dashboard_features_update'))
                                        <th style="width:36px"><input type="checkbox" id="bulkSelectAll"></th>
                                    @endif
                                    <th class="serial">{{ ___('common.sr_no') }}</th>
                                    <th>{{ ___('common.title') }}</th>
                                    <th>{{ ___('common.description') }}</th>
                                    <th style="width:120px">{{ ___('common.status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['features'] as $key => $row)
                                    <tr id="row_{{ $row->id }}">
                                        @if (hasPermission('dashboard_features_delete') || hasPermission('dashboard_features_update'))
                                            <td><input type="checkbox" class="bulk-row-checkbox" value="{{ $row->id }}"></td>
                                        @endif
                                        <td class="serial">{{ ++$key }}</td>
                                        <td>{{ $row->label }}</td>
                                        <td class="text-secondary" style="max-width:420px;">{{ $row->description }}</td>
                                        <td>
                                            @if (hasPermission('dashboard_features_update'))
                                                <a href="javascript:void(0);" onclick="toggleFeature({{ $row->id }}, {{ $row->status ? 0 : 1 }})">
                                                    @if ($row->status == App\Enums\Status::ACTIVE)
                                                        <span class="badge-basic-success-text">{{ ___('common.enabled') }}</span>
                                                    @else
                                                        <span class="badge-basic-danger-text">{{ ___('common.disabled') }}</span>
                                                    @endif
                                                </a>
                                            @else
                                                @if ($row->status == App\Enums\Status::ACTIVE)
                                                    <span class="badge-basic-success-text">{{ ___('common.enabled') }}</span>
                                                @else
                                                    <span class="badge-basic-danger-text">{{ ___('common.disabled') }}</span>
                                                @endif
                                            @endif
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
@endsection

@push('script')
    @include('backend.partials.delete-ajax')
    <script>
        function toggleFeature(id, newStatus) {
            $.ajax({
                type: 'POST',
                dataType: 'json',
                data: { ids: [id], status: newStatus },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                url: "{{ route('dashboard-features.bulk-status') }}",
            }).done(function (response) {
                if (response[1] !== 'error') {
                    location.reload();
                } else {
                    Swal.fire({ icon: response[1], title: response[2], text: response[0], confirmButtonText: response[3] });
                }
            }).fail(function () {
                Swal.fire('{{ ___('common.opps') }}...', '{{ ___('common.something_went_wrong_with_ajax') }}', 'error');
            });
        }
    </script>
@endpush
@include('backend.partials.bulk-actions-ajax', ['bulkRoute' => 'dashboard-features'])
