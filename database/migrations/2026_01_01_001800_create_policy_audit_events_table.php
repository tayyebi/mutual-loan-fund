<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Policy changes get their own audit table so the complete before/after
        // configuration is retained, including draft edits.
        Schema::create('policy_audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('policy_version_id')->nullable()->constrained('group_policies')->nullOnDelete();
            $table->unsignedInteger('version')->nullable();
            $table->string('action');
            $table->jsonb('old_config')->nullable();
            $table->jsonb('new_config')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['group_id', 'created_at']);
        });

        DB::statement("
            ALTER TABLE policy_audit_events ADD CONSTRAINT policy_audit_events_action_check
            CHECK (action IN ('created','updated','published','deleted','superseded'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_audit_events');
    }
};
