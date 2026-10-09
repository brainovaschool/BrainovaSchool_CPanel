@include('common.fee-pay.fee-pay-style')

<div class="modal-content">
    <div class="modal-header modal-header-image">
        <h5 class="modal-title">I already paid — submit proof</h5>
        <button type="button" class="m-0 btn-close d-flex justify-content-center align-items-center" data-bs-dismiss="modal" aria-label="Close"><i class="fa fa-times text-white" aria-hidden="true"></i></button>
    </div>
    <form action="{{ $formRoute }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="fees_assign_children_id" value="{{ $feeAssignChildren->id }}">
        <div class="modal-body p-4">
            <p class="text-secondary" style="font-size:.85rem;">
                Paid by JazzCash, EasyPaisa, bank transfer, or cash? Upload a screenshot or photo of the payment here.
                A staff member will check it and confirm — this won't be marked as paid until then.
            </p>
            <div class="row">
                <div class="col-12 mb-3">
                    <label class="form-label">For</label>
                    <input class="form-control ot-input bg-light" value="{{ optional($feeAssignChildren->feesMaster)->type->name ?? '—' }}" readonly>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">How did you pay? <span class="fillable">*</span></label>
                    <select class="form-control ot-input" name="payment_method" required>
                        @foreach (\App\Models\Fees\FeesPaymentProof::METHODS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Amount paid ({{ Setting('currency_symbol') }}) <span class="fillable">*</span></label>
                    <input class="form-control ot-input" name="amount_claimed" type="number" step="0.01" min="0"
                        value="{{ $feeAssignChildren->feesMaster?->amount }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Date paid <span class="fillable">*</span></label>
                    <input class="form-control ot-input" name="paid_date" type="date" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Transaction / reference number (optional)</label>
                    <input class="form-control ot-input" name="transaction_reference" maxlength="150">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Screenshot or photo of payment <span class="fillable">*</span></label>
                    <input class="form-control ot-input" name="proof_file" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                    <small class="text-secondary">Image or PDF, up to 5 MB.</small>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Note (optional)</label>
                    <textarea class="form-control ot-textarea" name="note" rows="2" maxlength="1000"></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary py-2 px-4" data-bs-dismiss="modal">{{ ___('ui_element.cancel') }}</button>
            <button type="submit" class="btn ot-btn-primary">Submit for review</button>
        </div>
    </form>
</div>
