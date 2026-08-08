<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every row is one immutable-once-published version of a group's
        // financial rules. Financial objects reference the row they were
        // created under and never follow a newer version.
        Schema::create('group_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status')->default('draft');
            $table->jsonb('config');
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'version']);
            $table->index(['group_id', 'status']);
        });

        DB::statement("ALTER TABLE group_policies ADD CONSTRAINT group_policies_status_check CHECK (status IN ('draft','published','superseded'))");

        // A published policy must carry its effective date; a draft must not.
        DB::statement("
            ALTER TABLE group_policies ADD CONSTRAINT group_policies_effective_from_check
            CHECK (status = 'draft' OR effective_from IS NOT NULL)
        ");

        DB::statement("
            ALTER TABLE group_policies ADD CONSTRAINT group_policies_effective_range_check
            CHECK (effective_until IS NULL OR effective_from IS NULL OR effective_until >= effective_from)
        ");

        // At most one open-ended published version per group: this is the
        // database-level guarantee behind 'only one policy is active at a time'.
        DB::statement("
            CREATE UNIQUE INDEX group_policies_single_active_idx
            ON group_policies (group_id)
            WHERE status = 'published' AND effective_until IS NULL
        ");

        // Administrators edit one draft at a time.
        DB::statement("
            CREATE UNIQUE INDEX group_policies_single_draft_idx
            ON group_policies (group_id)
            WHERE status = 'draft'
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('group_policies');
    }
};
