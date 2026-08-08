<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chart of accounts, one per group. There are deliberately no
        // member-specific accounts: member attribution is a cost-center concern.
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('type');
            $table->string('currency')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('requires_cost_center')->default(false);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['group_id', 'code']);
            $table->index(['group_id', 'type']);
        });

        DB::statement("ALTER TABLE accounts ADD CONSTRAINT accounts_type_check CHECK (type IN ('ASSET','LIABILITY','EQUITY','INCOME','EXPENSE'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
