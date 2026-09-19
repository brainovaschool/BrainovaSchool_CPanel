@php $s = $data['item'] ?? null; @endphp

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('common.category') }} <span class="fillable">*</span></label>
        <select class="form-control ot-input @error('category') is-invalid @enderror" name="category">
            @foreach (App\Models\LearningEngine\AvatarItem::CATEGORIES as $catKey => $catLabel)
                <option value="{{ $catKey }}" {{ old('category', $s->category ?? $data['category'] ?? 'avatar') == $catKey ? 'selected' : '' }}>{{ $catLabel }}</option>
            @endforeach
        </select>
        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-secondary">Base Character is the body itself (always one worn); Outfit and Hat are optional single layers; Accessory can be worn several at once.</small>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('common.title') }} <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('name') is-invalid @enderror" name="name"
            value="{{ old('name', $s->name ?? '') }}" placeholder="e.g. Space Explorer Kea">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">{{ ___('settings.price_in_coins') }} <span class="fillable">*</span></label>
        <input class="form-control ot-input @error('price_coins') is-invalid @enderror" name="price_coins" type="number" min="0"
            value="{{ old('price_coins', $s->price_coins ?? 0) }}">
        @error('price_coins')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-secondary">Price 0 means every student owns it automatically, as a free starter option.</small>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ ___('common.image') }} @if(!$s) <span class="fillable">*</span> @endif</label>
        <div class="ot_fileUploader left-side mb-2 @error('image') is-invalid @enderror">
            <input class="form-control" type="text" placeholder="{{ ___('common.image') }}" readonly id="placeholder">
            <button class="primary-btn-small-input" type="button">
                <label class="btn btn-lg ot-btn-primary" for="fileBrouse">{{ ___('common.browse') }}</label>
                <input type="file" class="d-none form-control" name="image" accept="image/*" id="fileBrouse">
            </button>
        </div>
        @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @if ($s && $s->image)
            <img src="{{ globalAsset($s->image) }}" alt="{{ $s->name }}" class="mt-2" style="height:70px;border-radius:50%;">
        @endif
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('common.Serial') }}</label>
        <input class="form-control ot-input @error('sort_order') is-invalid @enderror" name="sort_order" type="number" min="0"
            value="{{ old('sort_order', $s->sort_order ?? 0) }}">
        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">{{ ___('common.status') }} <span class="fillable">*</span></label>
        <select class="form-control ot-input @error('status') is-invalid @enderror" name="status">
            <option value="{{ App\Enums\Status::ACTIVE }}" {{ old('status', $s->status ?? 1) == 1 ? 'selected' : '' }}>{{ ___('common.active') }}</option>
            <option value="{{ App\Enums\Status::INACTIVE }}" {{ old('status', $s->status ?? 1) == 0 ? 'selected' : '' }}>{{ ___('common.inactive') }}</option>
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

@php
    $placementDefaults = App\Models\LearningEngine\AvatarItem::DEFAULT_PLACEMENT[$s->category ?? $data['category'] ?? 'avatar']
        ?? App\Models\LearningEngine\AvatarItem::DEFAULT_PLACEMENT['accessory'];
@endphp

<div class="card mt-2 mb-3" id="placementCard">
    <div class="card-body">
        <h5 class="mb-1">Where it sits on the avatar</h5>
        <p class="text-secondary" style="font-size:.86rem;">
            Drag the sliders until the item sits correctly on the character below. Whatever you set here is
            how every student will wear it. A Base Character needs no adjustment — it always fills the frame.
        </p>

        <div class="row">
            <div class="col-md-5 mb-3">
                <div id="placementStage"
                    style="position:relative; width:100%; max-width:260px; aspect-ratio:1/1; margin:0 auto;
                           background:#f4f6f7; border:1px solid #e7e9ee; border-radius:14px; overflow:hidden;">
                    <img id="placementBody" alt=""
                        style="position:absolute; left:50%; top:50%; width:100%; transform:translate(-50%,-50%); display:none;">
                    <img id="placementItem" alt=""
                        style="position:absolute; display:none;">
                    <div id="placementEmpty" class="text-secondary"
                        style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; text-align:center; font-size:.8rem; padding:12px;">
                        Choose an image above to see it here.
                    </div>
                </div>

                @if (!empty($data['bodies']) && count($data['bodies']))
                    <div class="mt-2">
                        <label class="form-label" style="font-size:.8rem;">Preview on</label>
                        <select class="form-control ot-input" id="placementBodyPicker">
                            @foreach ($data['bodies'] as $body)
                                <option value="{{ $body->image ? globalAsset($body->image) : '' }}">{{ $body->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-secondary">Check the fit against each character — one placement is used for all of them.</small>
                    </div>
                @else
                    <p class="text-secondary mt-2" style="font-size:.8rem;">
                        No Base Characters added yet, so there's nothing to preview against. Add one first, then come back to position this item.
                    </p>
                @endif
            </div>

            <div class="col-md-7">
                <div class="mb-3">
                    <label class="form-label">Across <span class="text-secondary" id="posXOut"></span></label>
                    <input type="range" class="form-range" id="posXRange" min="0" max="100" step="0.5"
                        value="{{ old('pos_x', $s->pos_x ?? $placementDefaults['pos_x']) }}">
                    <input type="hidden" name="pos_x" id="posXInput" value="{{ old('pos_x', $s->pos_x ?? $placementDefaults['pos_x']) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Up / down <span class="text-secondary" id="posYOut"></span></label>
                    <input type="range" class="form-range" id="posYRange" min="0" max="100" step="0.5"
                        value="{{ old('pos_y', $s->pos_y ?? $placementDefaults['pos_y']) }}">
                    <input type="hidden" name="pos_y" id="posYInput" value="{{ old('pos_y', $s->pos_y ?? $placementDefaults['pos_y']) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Size <span class="text-secondary" id="scaleOut"></span></label>
                    <input type="range" class="form-range" id="scaleRange" min="5" max="200" step="0.5"
                        value="{{ old('scale', $s->scale ?? $placementDefaults['scale']) }}">
                    <input type="hidden" name="scale" id="scaleInput" value="{{ old('scale', $s->scale ?? $placementDefaults['scale']) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Tilt <span class="text-secondary" id="rotationOut"></span></label>
                    <input type="range" class="form-range" id="rotationRange" min="-180" max="180" step="1"
                        value="{{ old('rotation', $s->rotation ?? $placementDefaults['rotation']) }}">
                    <input type="hidden" name="rotation" id="rotationInput" value="{{ old('rotation', $s->rotation ?? $placementDefaults['rotation']) }}">
                </div>

                <button type="button" class="btn btn-outline-secondary btn-sm" id="placementReset">
                    <i class="fa-solid fa-rotate-left"></i> Reset to default for this category
                </button>
            </div>
        </div>
    </div>
</div>

<div class="text-end mt-3">
    <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i> </span>{{ ___('common.submit') }}</button>
</div>

@push('script')
<script>
(function () {
    var DEFAULTS = @json(App\Models\LearningEngine\AvatarItem::DEFAULT_PLACEMENT);

    var stage      = document.getElementById('placementStage');
    var bodyImg    = document.getElementById('placementBody');
    var itemImg    = document.getElementById('placementItem');
    var emptyNote  = document.getElementById('placementEmpty');
    var bodyPicker = document.getElementById('placementBodyPicker');
    var categorySel = document.querySelector('select[name="category"]');
    var fileInput  = document.getElementById('fileBrouse');
    var card       = document.getElementById('placementCard');
    if (!stage) return;

    var controls = {
        pos_x:    { range: document.getElementById('posXRange'),     input: document.getElementById('posXInput'),     out: document.getElementById('posXOut'),     suffix: '%' },
        pos_y:    { range: document.getElementById('posYRange'),     input: document.getElementById('posYInput'),     out: document.getElementById('posYOut'),     suffix: '%' },
        scale:    { range: document.getElementById('scaleRange'),    input: document.getElementById('scaleInput'),    out: document.getElementById('scaleOut'),    suffix: '%' },
        rotation: { range: document.getElementById('rotationRange'), input: document.getElementById('rotationInput'), out: document.getElementById('rotationOut'), suffix: '°' }
    };

    // The existing image, if we're editing an item that already has one.
    var currentItemSrc = @json($s && $s->image ? globalAsset($s->image) : null);

    function render() {
        var isBase = categorySel && categorySel.value === 'avatar';

        // A base character always fills the stage, so its sliders do nothing —
        // hide them rather than offer a control that has no effect.
        card.querySelector('.col-md-7').style.display = isBase ? 'none' : '';

        if (bodyPicker && bodyPicker.value && !isBase) {
            bodyImg.src = bodyPicker.value;
            bodyImg.style.display = '';
        } else {
            bodyImg.style.display = 'none';
        }

        if (currentItemSrc) {
            emptyNote.style.display = 'none';
            itemImg.src = currentItemSrc;
            itemImg.style.display = '';

            if (isBase) {
                itemImg.style.left = '50%';
                itemImg.style.top = '50%';
                itemImg.style.width = '100%';
                itemImg.style.transform = 'translate(-50%,-50%)';
            } else {
                itemImg.style.left = controls.pos_x.range.value + '%';
                itemImg.style.top = controls.pos_y.range.value + '%';
                itemImg.style.width = controls.scale.range.value + '%';
                itemImg.style.transform = 'translate(-50%,-50%) rotate(' + controls.rotation.range.value + 'deg)';
            }
        } else {
            itemImg.style.display = 'none';
            emptyNote.style.display = bodyImg.style.display === 'none' ? 'flex' : 'none';
        }

        Object.keys(controls).forEach(function (key) {
            var c = controls[key];
            c.input.value = c.range.value;
            c.out.textContent = c.range.value + c.suffix;
        });
    }

    Object.keys(controls).forEach(function (key) {
        controls[key].range.addEventListener('input', render);
    });

    if (bodyPicker)  bodyPicker.addEventListener('change', render);
    if (categorySel) categorySel.addEventListener('change', function () {
        applyDefaults(categorySel.value);
        render();
    });

    function applyDefaults(category) {
        var d = DEFAULTS[category] || DEFAULTS.accessory;
        controls.pos_x.range.value    = d.pos_x;
        controls.pos_y.range.value    = d.pos_y;
        controls.scale.range.value    = d.scale;
        controls.rotation.range.value = d.rotation;
    }

    document.getElementById('placementReset').addEventListener('click', function () {
        applyDefaults(categorySel ? categorySel.value : 'accessory');
        render();
    });

    // Preview the file the admin just picked, before it's ever uploaded.
    if (fileInput) {
        fileInput.addEventListener('change', function (e) {
            var file = e.target.files && e.target.files[0];
            if (!file) return;
            if (currentItemSrc && currentItemSrc.indexOf('blob:') === 0) {
                URL.revokeObjectURL(currentItemSrc);
            }
            currentItemSrc = URL.createObjectURL(file);
            render();
        });
    }

    render();
})();
</script>
@endpush
