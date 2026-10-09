@extends('backend.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    @php
        $item = $data['item'];
        $proofPath = optional($item->proofUpload)->path;
        $isPdf = $proofPath && str_ends_with(strtolower($proofPath), '.pdf');
    @endphp
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('fees-payment-proof.index') }}">Payment Proof Review</a></li>
                        <li class="breadcrumb-item active">#{{ $item->id }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card ot-card">
                    <h5 class="mb-3">Uploaded proof</h5>
                    @if ($proofPath && $isPdf)
                        <a href="{{ globalAsset($proofPath) }}" target="_blank" rel="noopener" class="btn ot-btn-primary">
                            <i class="fa-solid fa-file-pdf me-1"></i> Open PDF
                        </a>
                    @elseif ($proofPath)
                        <a href="{{ globalAsset($proofPath) }}" target="_blank" rel="noopener">
                            <img src="{{ globalAsset($proofPath) }}" alt="Payment proof" style="max-width:100%;border-radius:10px;border:1px solid #e2e8f0;">
                        </a>
                    @else
                        <p class="text-secondary mb-0">No file on record.</p>
                    @endif
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card ot-card mb-3">
                    <h5 class="mb-3">Submission details</h5>
                    <table class="table table-borderless mb-0">
                        <tr><td class="text-secondary" style="width:180px;">Student</td><td><strong>{{ trim(optional($item->student)->first_name . ' ' . optional($item->student)->last_name) ?: '—' }}</strong></td></tr>
                        <tr><td class="text-secondary">Fee</td><td>{{ optional(optional($item->feesAssignChildren)->feesMaster)->type->name ?? '—' }}</td></tr>
                        <tr><td class="text-secondary">Claimed to have paid via</td><td>{{ \App\Models\Fees\FeesPaymentProof::METHODS[$item->payment_method] ?? $item->payment_method }}</td></tr>
                        <tr><td class="text-secondary">Amount claimed</td><td>{{ Setting('currency_symbol') }} {{ number_format($item->amount_claimed, 2) }}</td></tr>
                        <tr><td class="text-secondary">Date paid</td><td>{{ \Carbon\Carbon::parse($item->paid_date)->format('d M Y') }}</td></tr>
                        <tr><td class="text-secondary">Reference</td><td>{{ $item->transaction_reference ?: '—' }}</td></tr>
                        <tr><td class="text-secondary">Note from submitter</td><td>{{ $item->note ?: '—' }}</td></tr>
                        <tr><td class="text-secondary">Status</td><td>
                            @if ($item->status === 'pending')
                                <span class="badge-basic-warning-text">Pending review</span>
                            @elseif ($item->status === 'approved')
                                <span class="badge-basic-success-text">Approved</span>
                            @else
                                <span class="badge-basic-danger-text">Rejected</span>
                            @endif
                        </td></tr>
                        @if ($item->status !== 'pending')
                            <tr><td class="text-secondary">Reviewed by</td><td>{{ optional($item->reviewer)->name ?? '—' }} @if($item->reviewed_at), {{ $item->reviewed_at->format('d M Y') }}@endif</td></tr>
                            @if ($item->status === 'rejected')
                                <tr><td class="text-secondary">Reason</td><td>{{ $item->rejection_reason }}</td></tr>
                            @endif
                        @endif
                    </table>
                </div>

                @if ($item->status === 'pending')
                    <div class="card ot-card">
                        <h5 class="mb-1"><i class="fa-solid fa-stamp text-primary me-1"></i> Your decision</h5>
                        <p class="text-secondary mb-3" style="font-size:.85rem;">Check the uploaded proof matches what's claimed before approving — the amount here is what actually gets recorded as paid.</p>

                        <form action="{{ route('fees-payment-proof.approve', $item->id) }}" method="post" class="mb-3" onsubmit="return confirm('Approve this and mark the fee as paid?');">
                            @csrf
                            <div class="row align-items-end g-2">
                                <div class="col-md-5">
                                    <label class="form-label">Confirmed amount ({{ Setting('currency_symbol') }})</label>
                                    <input class="form-control ot-input" name="confirmed_amount" type="number" step="0.01" min="0" value="{{ $item->amount_claimed }}" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Note (optional)</label>
                                    <input class="form-control ot-input" name="note" maxlength="1000">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn ot-btn-primary w-100"><i class="fa-solid fa-check"></i> Approve</button>
                                </div>
                            </div>
                        </form>

                        <form action="{{ route('fees-payment-proof.reject', $item->id) }}" method="post" onsubmit="return confirm('Reject this submission?');">
                            @csrf
                            <div class="row align-items-end g-2">
                                <div class="col-md-10">
                                    <label class="form-label">Reason for rejecting</label>
                                    <input class="form-control ot-input" name="rejection_reason" maxlength="1000" required placeholder="e.g. Screenshot doesn't show the amount clearly, please resubmit">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-outline-danger w-100">Reject</button>
                                </div>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
