@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    @php $character = $data['character']; @endphp
    <div class="page-content">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('portal-social-reel-characters.index') }}">Reel Characters</a></li>
                        <li class="breadcrumb-item">{{ $data['title'] }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card mb-3">
            <div class="card-body">
                <form action="{{ route('portal-social-reel-characters.update', $character->id) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('portal.social.reel-characters._form', ['character' => $character])
                    <div class="mt-3">
                        <button type="submit" class="btn btn-lg ot-btn-primary">Save Changes</button>
                        <a href="{{ route('portal-social-reel-characters.index') }}" class="btn btn-lg btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-header"><h4 class="mb-0">Costume Images</h4></div>
            <div class="card-body">
                <p class="text-secondary">Add as many outfit/costume reference images as this character needs — each can be downloaded any time.</p>

                @if ($character->costumes->isNotEmpty())
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        @foreach ($character->costumes as $costume)
                            <div class="border rounded p-2 text-center" style="width:150px;">
                                @if ($costume->upload)
                                    <img src="{{ globalAsset($costume->upload->path) }}" style="width:100%;height:150px;object-fit:cover;" class="mb-1">
                                @endif
                                <p class="mb-1" style="font-size:12px;">{{ $costume->label ?: '—' }}</p>
                                <div class="d-flex gap-1 justify-content-center">
                                    @if ($costume->upload)
                                        <a href="{{ globalAsset($costume->upload->path) }}" download class="btn btn-sm btn-outline-secondary">Download</a>
                                    @endif
                                    <form action="{{ route('portal-social-reel-characters.costumes.destroy', [$character->id, $costume->id]) }}" method="post"
                                          onsubmit="return confirm('Delete this costume image?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('portal-social-reel-characters.costumes.store', $character->id) }}" method="post" enctype="multipart/form-data" class="d-flex gap-2 align-items-end flex-wrap">
                    @csrf
                    <div>
                        <label class="form-label">Label (optional)</label>
                        <input type="text" name="label" class="form-control ot-input" placeholder="e.g. Winter outfit" style="width:200px;">
                    </div>
                    <div class="ot_fileUploader left-side mb-0">
                        <input class="form-control ot-input bn-file-placeholder" type="text" placeholder="No file chosen" readonly>
                        <button class="primary-btn-small-input" type="button">
                            <label class="btn btn-lg ot-btn-primary" for="costumeImage">{{ ___('common.browse') }}</label>
                            <input type="file" class="d-none bn-file-input" id="costumeImage" name="costume_image" accept="image/*" required>
                        </button>
                    </div>
                    <button type="submit" class="btn ot-btn-primary">Add Costume</button>
                </form>
            </div>
        </div>
    </div>
@endsection
