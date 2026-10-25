<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

/** One admin-defined extra field on the Add/Edit Reel form. A `select`
 *  field's options come from the Social Board Settings block (a
 *  PortalSettingList) whose key matches `list_key`. */
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

    public const TYPES = [
        self::TYPE_TEXT     => 'Text box',
        self::TYPE_TEXTAREA => 'Paragraph',
        self::TYPE_SELECT   => 'Dropdown',
        self::TYPE_CHECKBOX => 'Checkbox',
        self::TYPE_DATE     => 'Date',
        self::TYPE_NUMBER   => 'Number',
    ];

    /** A select field's live options, or [] if its source block was
     *  renamed/deleted (field still shows, just with no options). */
    public function options(): array
    {
        if ($this->type !== self::TYPE_SELECT || !$this->list_key) {
            return [];
        }
        return PortalSettingList::valuesFor($this->list_key);
    }
}
