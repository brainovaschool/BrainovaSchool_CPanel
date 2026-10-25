<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Which staff Role(s) populate the Reel/Content "Assign to" dropdown -
 *  an admin-configurable multi-pick instead of the unrelated "Content"
 *  PortalResponsibility list (which most tenants never set up). */
return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('portal_settings', 'content_assignee_role_ids')) {
                $table->json('content_assignee_role_ids')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('portal_settings', function (Blueprint $table) {
            if (Schema::hasColumn('portal_settings', 'content_assignee_role_ids')) {
                $table->dropColumn('content_assignee_role_ids');
            }
        });
    }
};
