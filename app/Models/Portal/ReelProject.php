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

    /** Every active Reel Character - the full roster available to pick
     *  from on each scene (up to 3 per scene) and for AI scene generation.
     *  No project-level pre-selection step anymore. */
    public function involvedCharacters()
    {
        return ReelCharacter::active()->orderBy('sort_order')->orderBy('name')->get();
    }
}
