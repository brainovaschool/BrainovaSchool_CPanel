@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <h4 class="bradecrumb-title mb-1">Bulk Import Reels</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('portal-social-reels.index') }}">Reels</a></li>
                        <li class="breadcrumb-item">Bulk Import</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-16">
                    <p class="mb-0 text-secondary">Add a lot of reel ideas in one go — download the sample sheet, open it in Excel, fill in your own rows, then upload it back here.</p>
                    <a href="{{ route('portal-social-reels.import.sample') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-download"></i> Download the sample sheet
                    </a>
                </div>

                <div class="card ot-card bg-light-subtle mb-24">
                    <div class="card-body">
                        <strong>Each row in the sheet becomes one reel idea. One column per piece of information:</strong>
                        <ul class="mb-0 mt-2">
                            <li><strong>Topic</strong> — required. What the reel is about.</li>
                            <li><strong>Series</strong> — optional. Must match an existing series name exactly, or leave blank.</li>
                            <li><strong>Hook, Pillar, Format, Note</strong> — all optional, same as on the Add Reel form.</li>
                            <li><strong>Paid</strong> — write <code>Yes</code> to make it a paid, open-to-claim task. Leave blank (or write <code>No</code>) for a plain idea.</li>
                            <li><strong>Amount</strong> — required if Paid is Yes. The payment for that task.</li>
                            <li><strong>Deadline</strong> — optional date, used either way. Type it as <code>2026-12-05</code> (year-month-day) so it can't be misread.</li>
                            <li><strong>Assign To</strong> — optional. A staff name (first + last name, exactly as it appears on the Reels page) to assign it to directly instead of marking it Paid.</li>
                            <li><strong>Visible To</strong> — optional, only used when Paid is Yes. One or more staff names separated by commas, if the paid task should only be open to specific people instead of everyone.</li>
                        </ul>
                    </div>
                </div>

                @unless ($data['canAssign'])
                    <div class="alert alert-warning">You can add reel ideas, but you don't have permission to assign them or mark them as paid tasks here — the Paid/Amount/Assign To/Visible To columns will be ignored for you.</div>
                @endunless

                <form action="{{ route('portal-social-reels.import.submit') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label">Filled-in Excel file</label>
                            <div class="ot_fileUploader left-side mb-0">
                                <input class="form-control ot-input bn-file-placeholder" type="text" placeholder="No file chosen" readonly>
                                <button class="primary-btn-small-input" type="button">
                                    <label class="btn btn-lg ot-btn-primary" for="reelImportFile">Browse</label>
                                    <input type="file" class="d-none bn-file-input" id="reelImportFile" name="file" accept=".xlsx,.xls,.csv" required>
                                </button>
                            </div>
                            @error('file')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-lg ot-btn-primary w-100"><i class="fa-solid fa-upload"></i> Import</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('script')
    <script>
        document.addEventListener('change', function (e) {
            if (e.target.id !== 'reelImportFile') return;
            var wrapper = e.target.closest('.ot_fileUploader');
            var placeholder = wrapper ? wrapper.querySelector('.bn-file-placeholder') : null;
            if (placeholder) {
                placeholder.value = e.target.files.length ? e.target.files[0].name : '';
            }
        });
    </script>
    @endpush
@endsection
