<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A cost center answers "who/what does this belong to". It never holds
        // money; that is what ledger accounts and treasuries are for.
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('group_memberships')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['group_id', 'code']);
            $table->unique(['group_id', 'member_id']);
            $table->index(['group_id', 'status']);
        });

        DB::statement("ALTER TABLE cost_centers ADD CONSTRAINT cost_centers_status_check CHECK (status IN ('active','inactive'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_centers');
    }
};
