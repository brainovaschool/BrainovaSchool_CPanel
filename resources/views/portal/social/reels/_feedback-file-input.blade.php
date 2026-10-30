{{-- One reusable "attach a feedback file" field, shared by the Approve
     prompt/Canva/video forms. $id: a unique DOM id for this instance. --}}
<div class="ot_fileUploader left-side mb-2">
    <input class="form-control ot-input bn-file-placeholder" type="text" placeholder="No file chosen" readonly>
    <button class="primary-btn-small-input" type="button">
        <label class="btn btn-lg ot-btn-primary" for="{{ $id }}">Attach file (optional)</label>
        <input type="file" class="d-none bn-file-input" id="{{ $id }}" name="feedback_file">
    </button>
</div>
