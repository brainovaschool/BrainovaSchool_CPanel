<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

/** One admin-defined extra field on the Add/Edit Reel form. A `select`
 *  field's options come from either a Social Board Settings block (a
 *  PortalSettingList, `list_key` = that block's key) or the live Reel
 *  Characters roster (`list_key` = SOURCE_REEL_CHARACTERS). */
class PortalReelCustomField extends Model
{
    protected $table = 'portal_reel_custom_fields';
    protected $guarded = ['id'];

    public const TYPE_TEXT     = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_SELECT   = 'select';
    public const TYPE_CHECKBOX = 'checkbox';
    public const TYPE_DATE     = 'date';
    public const TYPE_NUMBER   = 'number';
    public const TYPE_FILE     = 'file';

    public const TYPES = [
        self::TYPE_TEXT     => 'Text box',
        self::TYPE_TEXTAREA => 'Paragraph',
        self::TYPE_SELECT   => 'Dropdown',
        self::TYPE_CHECKBOX => 'Checkbox',
        self::TYPE_DATE     => 'Date',
        self::TYPE_NUMBER   => 'Number',
        self::TYPE_FILE     => 'File/Image upload',
    ];

    /** Sentinel list_key value: a Dropdown field's options come from the
     *  live Reel Characters roster instead of a Settings block. */
    public const SOURCE_REEL_CHARACTERS = '__reel_characters__';

    /** A select field's live options, or [] if its source (a block or
     *  Reel Characters) was renamed/deleted (field still shows, just with
     *  no options). */
    public function options(): array
    {
        if ($this->type !== self::TYPE_SELECT || !$this->list_key) {
            return [];
        }
        if ($this->list_key === self::SOURCE_REEL_CHARACTERS) {
            return ReelCharacter::active()->orderBy('sort_order')->pluck('name')->all();
        }
        return PortalSettingList::valuesFor($this->list_key);
    }
}
