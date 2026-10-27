<?php

namespace App\Models\Portal;

use App\Models\Upload;
use Illuminate\Database\Eloquent\Model;

/** A Task category's own SOP (instructions text and/or an attached
 *  file) - matched against PortalTask.category by name. */
class PortalTaskCategory extends Model
{
    protected $table = 'portal_task_categories';
    protected $guarded = ['id'];

    public function sopUpload()
    {
        return $this->belongsTo(Upload::class, 'sop_upload_id');
    }
}
