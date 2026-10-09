<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 9 (hardening): status and due_date are filtered on every manager
 *  task-list request and by the deadline/overdue cron, which runs every
 *  minute — worth an index even at this app's modest scale. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_tasks', function (Blueprint $table) {
            if (!$this->hasIndex('portal_tasks', 'portal_tasks_status_index')) {
                $table->index('status');
            }
            if (!$this->hasIndex('portal_tasks', 'portal_tasks_due_date_index')) {
                $table->index('due_date');
            }
        });
    }

    public function down()
    {
        Schema::table('portal_tasks', function (Blueprint $table) {
            $table->dropIndex('portal_tasks_status_index');
            $table->dropIndex('portal_tasks_due_date_index');
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = \Illuminate\Support\Facades\DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
