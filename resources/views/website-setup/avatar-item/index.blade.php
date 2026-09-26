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

        @if (hasPermission('avatar_item_update'))
            <div class="card mb-24">
                <div class="card-header"><h5 class="mb-0">My Learning Island — top banner</h5></div>
                <div class="card-body">
                    <p class="text-secondary mb-3">Shown across the top of the "My Island" tab on the student's page. One image, shared by every student.</p>
                    <form action="{{ route('avatar-item.island-image') }}" method="post" enctype="multipart/form-data" class="row align-items-end">
                        @csrf
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Banner image</label>
                            <div class="ot_fileUploader left-side mb-2">
                                <input class="form-control" type="text" placeholder="{{ ___('common.image') }}" readonly id="islandImagePlaceholder">
                                <button class="primary-btn-small-input" type="button">
                                    <label class="btn btn-lg ot-btn-primary" for="islandImageFile">{{ ___('common.browse') }}</label>
                                    <input type="file" class="d-none form-control" name="island_top_image" accept="image/*" id="islandImageFile">
                                </button>
                            </div>
                            <small class="text-secondary">Any size works — the page automatically shapes itself to fit your picture exactly, so nothing is ever cropped. A wide, landscape photo (like 1920&times;1080) reads best as a banner.</small>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-lg ot-btn-primary w-100">{{ ___('common.save') }}</button>
                        </div>
                    </form>
                    @if (setting('island_top_image'))
                        <img src="{{ globalAsset(setting('island_top_image')) }}" alt="My Island banner" class="mt-2" style="max-height:120px;border-radius:12px;">
                    @else
                        <p class="text-secondary mb-0" style="font-size:.85rem;">No banner uploaded yet — students will see a placeholder until one is added.</p>
                    @endif
                </div>
            </div>

            @push('script')
            <script>
            (function () {
                var input = document.getElementById('islandImageFile');
                var box   = document.getElementById('islandImagePlaceholder');
                if (!input || !box) return;
                input.addEventListener('change', function () {
                    box.placeholder = input.files && input.files[0] ? input.files[0].name : 'Image';
                });
            })();
            </script>
            @endpush
        @endif

        <div class="table-content table-basic mt-20">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="btn-group">
                        @foreach (App\Models\LearningEngine\AvatarItem::enabledCategories() as $catKey => $catLabel)
                            <a href="{{ route('avatar-item.index', ['category' => $catKey]) }}"
                                class="btn {{ $data['category'] === $catKey ? 'ot-btn-primary' : 'btn-outline-secondary' }}">{{ $catLabel }}</a>
                        @endforeach
                    </div>
                    @if (hasPermission('avatar_item_create'))
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-lg btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#bulkUploadPanel">
                                <span><i class="fa-solid fa-layer-group"></i> </span>
                                <span>Upload many</span>
                            </button>
                            <a href="{{ route('avatar-item.create', ['category' => $data['category']]) }}" class="btn btn-lg ot-btn-primary">
                                <span><i class="fa-solid fa-plus"></i> </span>
                                <span>{{ ___('common.add') }}</span>
                            </a>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    <p class="text-secondary mb-3">
                        @if ($data['category'] === 'avatar')
                            The whole character — every student always has exactly one of these equipped. Set a price in Coins to make one purchasable, or 0 to give it to every student for free.
                        @elseif ($data['category'] === 'outfit')
                            A clothing layer worn over the outfit (shirt, hoodie, dress, etc.). Optional — a student can go without one.
                        @elseif ($data['category'] === 'hat')
                            A headwear layer worn on top of everything else (cap, headphones, goggles, etc.). Optional.
                        @elseif ($data['category'] === 'hub')
                            A themed zone on My Learning Island, e.g. CodeNova, AI Spark Lab, Math Quest. Upload its picture and set where it sits on the island banner. Price in Coins is unused here.
                        @elseif ($data['category'] === 'building')
                            A structure that belongs to one hub — pick which hub below, then set where the building sits on the island banner. Price in Coins is unused here.
                        @else
                            Small extras a student can wear several of at once (glasses, backpack, a held prop, ...). Optional.
                        @endif
                    </p>
                    @if (in_array($data['category'], App\Models\LearningEngine\AvatarItem::ISLAND_CATEGORIES))
                        <p class="text-secondary mb-3">
                            <i class="fa-solid fa-circle-info"></i>
                            These sit on top of the island banner, so a transparent-background PNG works best. Any size is fine — the placement editor on the next screen scales it to where you drag it.
                        </p>
                    @else
                        <p class="text-secondary mb-3">
                            <i class="fa-solid fa-circle-info"></i>
                            For layers to line up on the student's avatar, upload every image (across all categories) at the exact same canvas size and with the character in the exact same position — e.g. 500&times;650px, transparent background. Mismatched artwork will still work, it just won't line up visually.
                        </p>
                    @endif

                    @if (hasPermission('avatar_item_create'))
                        <div class="collapse mb-3 {{ $errors->bulkUpload->any() ? 'show' : '' }}" id="bulkUploadPanel">
                            <form action="{{ route('avatar-item.bulk-store') }}" method="post" enctype="multipart/form-data"
                                class="p-3" style="border:1px dashed #d7dbe0; border-radius:12px; background:#fbfeff;">
                                @csrf
                                <input type="hidden" name="category" value="{{ $data['category'] }}">

                                @if ($errors->bulkUpload->any())
                                    <div class="alert alert-danger">
                                        <ul class="mb-0 ps-3">
                                            @foreach ($errors->bulkUpload->all() as $bulkError)
                                                <li>{{ $bulkError }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div id="bulkClientError" class="alert alert-danger" style="display:none;"></div>

                                <div class="row align-items-end">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Pick several images at once</label>
                                        {{-- Same browse-button widget the rest of the app uses; a bare
                                             file input renders near-invisible against this theme. --}}
                                        <div class="ot_fileUploader left-side mb-2">
                                            <input class="form-control" type="text" placeholder="No files chosen yet" readonly id="bulkPlaceholder">
                                            <button class="primary-btn-small-input" type="button">
                                                <label class="btn btn-lg ot-btn-primary" for="bulkFiles">{{ ___('common.browse') }}</label>
                                                <input type="file" class="d-none form-control" name="images[]" accept="image/*" id="bulkFiles" multiple>
                                            </button>
                                        </div>
                                        <small class="text-secondary">Hold Ctrl (or Cmd) to pick several. Each file becomes one {{ strtolower(App\Models\LearningEngine\AvatarItem::CATEGORIES[$data['category']]) }}, named after the file.</small>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">{{ ___('settings.price_in_coins') }}</label>
                                        <input type="number" class="form-control ot-input" name="price_coins" min="0" value="0">
                                        <small class="text-secondary">Applied to all of them.</small>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <button class="btn btn-lg ot-btn-primary w-100"><i class="fa-solid fa-upload"></i> Upload all</button>
                                    </div>
                                </div>
                                <p class="text-secondary mb-0" style="font-size:.82rem;">
                                    They all start at this category's default position — open each one afterwards to fine-tune where it sits.
                                    Most servers cap a single upload at around 20 files, so use a couple of batches if you have more.
                                </p>
                            </form>

                            @push('script')
                            <script>
                            (function () {
                                var input = document.getElementById('bulkFiles');
                                var box   = document.getElementById('bulkPlaceholder');
                                var alert = document.getElementById('bulkClientError');
                                if (!input || !box) return;

                                var form     = input.closest('form');
                                var PER_FILE = 5 * 1024 * 1024;

                                function show(message) {
                                    if (!alert) return;
                                    alert.textContent = message;
                                    alert.style.display = message ? '' : 'none';
                                }

                                input.addEventListener('change', function () {
                                    var files = input.files || [];
                                    box.placeholder = files.length === 0 ? 'No files chosen yet'
                                        : files.length === 1 ? files[0].name
                                        : files.length + ' files chosen';

                                    var tooBig = [];
                                    for (var i = 0; i < files.length; i++) {
                                        if (files[i].size > PER_FILE) tooBig.push(files[i].name);
                                    }

                                    show(tooBig.length
                                        ? 'These are over 5 MB and need to be smaller: ' + tooBig.join(', ')
                                        : '');
                                });

                                // Catch the empty submit before it round-trips and looks like nothing happened.
                                if (form) {
                                    form.addEventListener('submit', function (e) {
                                        if (!input.files || input.files.length === 0) {
                                            e.preventDefault();
                                            show('Choose at least one image first — use the Browse button.');
                                        }
                                    });
                                }
                            })();
                            </script>
                            @endpush
                        </div>
                    @endif

                    @if (hasPermission('avatar_item_delete') || hasPermission('avatar_item_update'))
                        @include('backend.partials.bulk-actions-bar')
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    @if (hasPermission('avatar_item_delete') || hasPermission('avatar_item_update'))
                                        <th style="width:36px"><input type="checkbox" id="bulkSelectAll"></th>
                                    @endif
                                    <th style="width:70px">{{ ___('common.image') }}</th>
                                    <th>{{ ___('common.title') }}</th>
                                    <th>{{ ___('settings.price_in_coins') }}</th>
                                    <th>{{ ___('common.Serial') }}</th>
                                    <th>{{ ___('common.status') }}</th>
                                    @if (hasPermission('avatar_item_update') || hasPermission('avatar_item_delete'))
                                        <th class="action">{{ ___('common.action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['items'] as $row)
                                    <tr id="row_{{ $row->id }}">
                                        @if (hasPermission('avatar_item_delete') || hasPermission('avatar_item_update'))
                                            <td><input type="checkbox" class="bulk-row-checkbox" value="{{ $row->id }}"></td>
                                        @endif
                                        <td>
                                            @if ($row->image)
                                                <img src="{{ globalAsset($row->image) }}" alt="{{ $row->name }}" style="height:44px;width:44px;border-radius:50%;object-fit:cover;">
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $row->name }}</td>
                                        <td>{{ $row->price_coins > 0 ? $row->price_coins . ' 🪙' : ___('common.free') }}</td>
                                        <td>{{ $row->sort_order }}</td>
                                        <td>
                                            @if ($row->status == App\Enums\Status::ACTIVE)
                                                <span class="badge-basic-success-text">{{ ___('common.active') }}</span>
                                            @else
                                                <span class="badge-basic-danger-text">{{ ___('common.inactive') }}</span>
                                            @endif
                                        </td>
                                        @if (hasPermission('avatar_item_update') || hasPermission('avatar_item_delete'))
                                            <td class="action">
                                                <div class="dropdown dropdown-action">
                                                    <button type="button" class="btn-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fa-solid fa-ellipsis"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        @if (hasPermission('avatar_item_update'))
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('avatar-item.edit', $row->id) }}">
                                                                    <span class="icon mr-8"><i class="fa-solid fa-pen-to-square"></i></span>{{ ___('common.edit') }}
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if (hasPermission('avatar_item_delete'))
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0);"
                                                                    onclick="delete_row('avatar-item/delete', {{ $row->id }})">
                                                                    <span class="icon mr-8"><i class="fa-solid fa-trash-can"></i></span>
                                                                    <span>{{ ___('common.delete') }}</span>
                                                                </a>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </td>
                                        @endif
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
                                {!! $data['items']->links() !!}
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    @include('backend.partials.delete-ajax')
@endpush
@include('backend.partials.bulk-actions-ajax', ['bulkRoute' => 'avatar-item'])
