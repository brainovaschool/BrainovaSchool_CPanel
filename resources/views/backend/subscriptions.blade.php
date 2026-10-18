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
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h1>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">{{ $data['title'] }}</li>
                    </ol>
                </div>
            </div>
        </div>
        {{-- bradecrumb Area E n d --}}
        @if (!activeSubscriptionExpiryDate())
            <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                <i class="las la-info-circle"></i>
                <div>
                    {{ ___('common.Your Subscription Plan has been expired! Please upgrade your subscription plan') }}
                </div>
            </div>
        @endif
        <div class="row">
            <div class="col-8">
                <!--  table content start -->
                <div class="table-content table-basic">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">{{ $data['title'] }}</h4>
                            <a href="{{ env('APP_URL') }}?school_subdomain={{ getSubdomainName()[0] }}&total_student={{ $data['totalStudents'] }}#Pricing" target="_blank" class="btn btn-lg ot-btn-primary">
                                <span><i class="fa-solid fa-cog"></i> </span>
                                <span class="">{{ ___('common.Upgrade') }}</span>
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered role-table">
                                    <thead class="thead">
                                        <tr>
                                            <th class="serial">{{ ___('common.sr_no') }}</th>
                                            <th class="purchase">{{ ___('common.Plan') }}</th>
                                            <th class="purchase">{{ ___('common.Price') }}</th>
                                            <th class="purchase">{{ ___('common.Limit') }}</th>
                                            <th class="purchase">{{ ___('common.Purchase Date') }}</th>
                                            <th class="purchase">{{ ___('common.Date of Expire') }}</th>
                                            <th class="purchase">{{ ___('common.Trx ID') }}</th>
                                            <th class="purchase">{{ ___('common.Method') }}</th>
                                            <th class="purchase">{{ ___('common.Status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="tbody">
                                        @forelse ($data['subscriptions'] as $key => $row)
                                        <tr id="row_{{ $row->id }}">
                                            <td class="serial">{{ $loop->iteration }}</td>
                                            <td>{{ @$row->name }}</td>
                                            <td>{{ @$row->price }}</td>
                                            <td>
                                                <div class="d-flex flex-column gap-0">
                                                    <small>Student: 
                                                        @if ($row->payment_type == \Modules\MainApp\Enums\PackagePaymentType::PREPAID)
                                                            {{ $row->student_limit }}
                                                        @else
                                                            {{ ___('common.Unlimited') }}
                                                        @endif
                                                    </small>
                                                    <small>Staff: 
                                                        @if ($row->payment_type == \Modules\MainApp\Enums\PackagePaymentType::PREPAID)
                                                            {{ $row->staff_limit }}
                                                        @else
                                                            {{ ___('common.Unlimited') }}
                                                        @endif
                                                    </small>
                                                </div>
                                            </td>
                                            <td>{{ dateFormat(@$row->created_at) }}</td>
                                            <td>{{ $row->expiry_date ? dateFormat(@$row->expiry_date) : ___('common.Lifetime') }}</td>
                                            <td>{{ @$row->trx_id }}</td>
                                            <td>{{ @$row->method }}</td>
                                            <td>
                                                @if (!activeSubscriptionExpiryDate())
                                                    <span class="badge-basic-warning-text">{{ ___('common.Expired') }}</span>
                                                @elseif ($row->status == App\Enums\Status::ACTIVE)
                                                    <span class="badge-basic-success-text">{{ ___('common.active') }}</span>
                                                @else
                                                    <span class="badge-basic-danger-text">{{ ___('common.inactive') }}</span>
                                                @endif
                                            </td>
                                            {{-- <td>{{ @$row->features_name }}</td> --}}
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="100%" class="text-center gray-color">
                                                <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                                                <p class="mb-0 text-center">{{ ___('common.no_data_available') }}</p>
                                                <p class="mb-0 text-center text-secondary font-size-90">
                                                    {{ ___('common.please_add_new_entity_regarding_this_table') }}</p>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <!--  table end -->
                            <!--  pagination start -->

                                <div class="ot-pagination pagination-content d-flex justify-content-end align-content-center py-3">
                                    <nav aria-label="Page navigation example">
                                        <ul class="pagination justify-content-between">
                                            {!! $data['subscriptions']->appends(\Request::capture()->except('page'))->links() !!}
                                        </ul>
                                    </nav>
                                </div>

                            <!--  pagination end -->
                        </div>

                    </div>
                </div>
                <!--  table content end -->
            </div>


            <div class="col-4">
                <!--  table content start -->
                <div class="table-content table-basic">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">{{ ___('common.Package Details') }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered role-table">
                                    <thead class="thead">
                                        <tr>
                                            <th class="serial">{{ ___('common.sr_no') }}</th>
                                            <th class="purchase">{{ ___('common.Features') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="tbody">
                                        @forelse (optional($data['activeSubscription'])->features_name ?? [] as $key => $value)
                                        <tr id="row_{{ $row->id }}">
                                            <td class="serial">{{ $loop->iteration }}</td>
                                            <td>{{ $value }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="100%" class="text-center gray-color">
                                                <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                                                <p class="mb-0 text-center">{{ ___('common.no_data_available') }}</p>
                                                <p class="mb-0 text-center text-secondary font-size-90">
                                                    {{ ___('common.please_add_new_entity_regarding_this_table') }}</p>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <!--  table end -->
                        </div>

                    </div>
                </div>
                <!--  table content end -->
            </div>
        </div>


    </div>
@endsection

@push('script')
    @include('backend.partials.delete-ajax')
@endpush
