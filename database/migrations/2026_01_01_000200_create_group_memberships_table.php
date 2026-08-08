<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The membership is the "member" everywhere else in the schema: columns
        // named member_id point at this table, never at users.
        Schema::create('group_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('role')->default('member');
            $table->string('status')->default('requested');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['group_id', 'user_id']);
            $table->index(['group_id', 'status']);
        });

        DB::statement("ALTER TABLE group_memberships ADD CONSTRAINT group_memberships_role_check CHECK (role IN ('member','admin'))");
        DB::statement("ALTER TABLE group_memberships ADD CONSTRAINT group_memberships_status_check CHECK (status IN ('requested','approved','active','rejected','suspended','removed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('group_memberships');
    }
};
