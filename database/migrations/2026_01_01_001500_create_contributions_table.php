<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A contribution is the business record beside its transaction. It is
        // immutable once the transaction is verified; corrections are made with
        // accounting adjustments, never by editing history.
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('group_memberships')->restrictOnDelete();
            $table->foreignId('cost_center_id')->constrained('cost_centers')->restrictOnDelete();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('policy_version_id')->nullable()->constrained('group_policies')->restrictOnDelete();
            $table->decimal('amount', 30, 8);
            $table->string('currency');
            $table->timestamps();

            $table->unique('transaction_id');
            $table->index(['group_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};
