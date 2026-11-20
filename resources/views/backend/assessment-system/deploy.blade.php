@extends('backend.master')

@section('title')
    Assessment System - Deploy
@endsection

@section('content')
    <div class="table-content table-basic mt-20">
        <div class="card shadow-sm rounded-4 mb-4">
            <div class="card-header bg-light p-4 rounded-top-4">
                <h4 class="mb-0"><i class="fa-solid fa-upload me-2 text-primary"></i>Deploy a new Assessment System file</h4>
            </div>
            <div class="card-body p-4">
                @if (session('deploySuccess'))
                    <div class="alert alert-success">{{ session('deploySuccess') }}</div>
                @endif
                @if (session('deployError'))
                    <div class="alert alert-danger">{{ session('deployError') }}</div>
                @endif
                @if (session('deployWarning'))
                    <div class="alert alert-warning">{{ session('deployWarning') }}</div>
                @endif

                <p class="text-muted">
                    Replaces the live Assessment &amp; Placement System with a file you upload - no git, no cPanel.
                    The file that's live now is always kept as a backup first, so this can be undone.
                </p>
                <p class="text-muted">
                    This only works for a file that already has the Google Sheets/LMS wiring built in
                    (ask your developer for a build of <code>assessment-system/</code>, not a plain HTML export).
                    A file without that wiring will be rejected unless you explicitly confirm you want it anyway.
                </p>

                <form method="POST" action="{{ route('assessment-system.deploy') }}" enctype="multipart/form-data" class="mt-4">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Assessment HTML file</label>
                        <input type="file" name="html" accept=".html,.htm" class="form-control" required>
                    </div>

                    @if (session('pendingUpload'))
                        <div class="form-check mb-3">
                            <input type="checkbox" name="confirm" value="1" id="confirmDeploy" class="form-check-input">
                            <label class="form-check-label" for="confirmDeploy">
                                Deploy it anyway, even though it isn't wired to the Sheet/LMS yet. Re-select the file above and check this to proceed.
                            </label>
                        </div>
                    @endif

                    <button type="submit" class="btn ot-btn-primary">Deploy</button>
                </form>
            </div>
        </div>

        @if (is_dir(public_path('assessment-system/backups')))
            @php
                $backups = collect(\Illuminate\Support\Facades\File::files(public_path('assessment-system/backups')))
                    ->sortByDesc(fn ($f) => $f->getMTime())
                    ->take(10);
            @endphp
            @if ($backups->count())
                <div class="card shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-light p-4 rounded-top-4">
                        <h5 class="mb-0">Recent backups (most recent first)</h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted small">
                            Kept automatically, one per deploy. To roll back, ask your developer to restore
                            one of these over <code>public/assessment-system/brainova-demo.html</code> -
                            this page only uploads forward, it doesn't restore yet.
                        </p>
                        <ul class="mb-0">
                            @foreach ($backups as $b)
                                <li>{{ $b->getFilename() }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        @endif
    </div>
@endsection
