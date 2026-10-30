<?php

namespace App\Models\Portal;

use App\Models\Upload;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ReelProject extends Model
{
    protected $table = 'reel_projects';
    protected $guarded = ['id'];

    protected $casts = [
        'custom_fields' => 'array',
    ];

    // Labelled "Orientation" in the UI (Portrait/Landscape) - the
    // vertical/horizontal values themselves are unchanged, only the
    // wording shown to the user changed.
    public const FORMATS = [
        'vertical'   => 'Portrait (9:16)',
        'horizontal' => 'Landscape (16:9)',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Set when this project was opened from a Content Pool/Reels
     *  suggestion rather than created standalone via "+ Create". */
    public function portalReel()
    {
        return $this->hasOne(PortalReel::class, 'reel_project_id');
    }

    public function scenes()
    {
        return $this->hasMany(ReelScene::class)->orderBy('sort_order');
    }

    public function coverUpload()
    {
        return $this->belongsTo(Upload::class, 'cover_upload_id');
    }

    /** True once a Pillar/Series/custom-fields are the teacher's own to set
     *  (no suggested Reel to read them from instead). */
    public function isStandalone(): bool
    {
        return !$this->portalReel;
    }

    /** The roster available to pick from on each scene (up to 3 per scene)
     *  and for AI scene generation: whichever names are listed in this
     *  reel's own "Character" custom field (set on the suggested Reel when
     *  linked, or on this project's own custom fields when standalone) -
     *  falling back to every active Reel Character if that field isn't
     *  set, so the list is never empty. */
    public function involvedCharacters()
    {
        $reel   = $this->portalReel;
        $fields = $reel ? ($reel->custom_fields ?: []) : ($this->custom_fields ?: []);

        $definitions = PortalReelCustomField::orderBy('sort_order')->get();
        $key = optional($definitions->first(fn ($f) => strtolower(trim($f->label)) === 'character'))->key;
        $names = $key ? array_filter((array) ($fields[$key] ?? [])) : [];

        if ($names) {
            $characters = ReelCharacter::active()->whereIn('name', $names)->orderBy('sort_order')->orderBy('name')->get();
            if ($characters->isNotEmpty()) {
                return $characters;
            }
        }

        return ReelCharacter::active()->orderBy('sort_order')->orderBy('name')->get();
    }
}
