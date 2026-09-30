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

        <!--  table content start -->
        <div class="table-content table-basic mt-20">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ $data['title'] }}</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    <th class="serial">{{ ___('common.sr_no') }}</th>
                                    <th class="purchase">Parent name</th>
                                    <th class="purchase">WhatsApp number</th>
                                    <th class="purchase">Child's grade</th>
                                    <th class="purchase">Program</th>
                                    <th class="purchase">Submitted</th>
                                    @if (authHasPermission('program_waitlist_delete'))
                                        <th class="action text-center">{{ ___('common.action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['waitlist'] as $key => $row)
                                <tr id="row_{{ $row->id }}">
                                    <td class="serial">{{ ++$key }}</td>
                                    <td>{{ $row->parent_name }}</td>
                                    <td><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $row->whatsapp_number) }}" target="_blank" rel="noopener">{{ $row->whatsapp_number }}</a></td>
                                    <td>{{ $row->child_grade ?: '—' }}</td>
                                    <td>{{ $row->category->name ?? '—' }}</td>
                                    <td>{{ $row->created_at->format('d M Y, h:i A') }}</td>
                                    @if (authHasPermission('program_waitlist_delete'))
                                        <td class="action text-center">
                                            <a href="javascript:void(0);" class="text-danger"
                                                onclick="delete_row('program-waitlist/delete', {{ $row->id }})">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </td>
                                    @endif
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="100%" class="text-center gray-color">
                                        <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                                        <p class="mb-0 text-center">{{ ___('common.no_data_available') }}</p>
                                        <p class="mb-0 text-center text-secondary font-size-90">
                                            No one has joined a program waitlist yet.</p>
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
                                    {!!$data['waitlist']->links() !!}
                                </ul>
                            </nav>
                        </div>

                    <!--  pagination end -->
                </div>
            </div>
        </div>
        <!--  table content end -->

    </div>
@endsection

@push('script')
    @include('backend.partials.delete-ajax')
@endpush
