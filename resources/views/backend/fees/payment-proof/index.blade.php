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
                    <div>
                        <h4 class="mb-0">Payment Proof Review</h4>
                        <p class="text-secondary mb-0" style="font-size:.85rem;">Fees a parent or student says they already paid another way (JazzCash, EasyPaisa, bank transfer, or cash) — nothing here counts as paid until you approve it.</p>
                    </div>
                    <div class="btn-group">
                        <a href="{{ route('fees-payment-proof.index') }}" class="btn btn-sm {{ $data['status'] === 'pending' ? 'ot-btn-primary' : 'btn-outline-secondary' }}">Pending</a>
                        <a href="{{ route('fees-payment-proof.index', ['status' => 'approved']) }}" class="btn btn-sm {{ $data['status'] === 'approved' ? 'ot-btn-primary' : 'btn-outline-secondary' }}">Approved</a>
                        <a href="{{ route('fees-payment-proof.index', ['status' => 'rejected']) }}" class="btn btn-sm {{ $data['status'] === 'rejected' ? 'ot-btn-primary' : 'btn-outline-secondary' }}">Rejected</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    <th>Student</th>
                                    <th>Fee</th>
                                    <th>Method</th>
                                    <th>Claimed amount</th>
                                    <th>Paid date</th>
                                    <th>Submitted</th>
                                    <th class="action">{{ ___('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['proofs'] as $row)
                                    <tr id="row_{{ $row->id }}">
                                        <td>{{ trim(optional($row->student)->first_name . ' ' . optional($row->student)->last_name) ?: '—' }}</td>
                                        <td>{{ optional(optional($row->feesAssignChildren)->feesMaster)->type->name ?? '—' }}</td>
                                        <td>{{ \App\Models\Fees\FeesPaymentProof::METHODS[$row->payment_method] ?? $row->payment_method }}</td>
                                        <td>{{ Setting('currency_symbol') }} {{ number_format($row->amount_claimed, 2) }}</td>
                                        <td>{{ \Carbon\Carbon::parse($row->paid_date)->format('d M Y') }}</td>
                                        <td>{{ $row->created_at->diffForHumans() }}</td>
                                        <td class="action">
                                            <a href="{{ route('fees-payment-proof.show', $row->id) }}" class="btn btn-sm ot-btn-primary">Review</a>
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
                                {!! $data['proofs']->links() !!}
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
