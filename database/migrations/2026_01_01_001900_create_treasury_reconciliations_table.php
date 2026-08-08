<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Records a comparison between an externally observed balance and the
        // ledger balance. A difference is reported, never silently corrected.
        Schema::create('treasury_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('treasury_id')->constrained('treasuries')->cascadeOnDelete();
            $table->date('as_of');
            $table->decimal('external_balance', 30, 8);
            $table->decimal('ledger_balance', 30, 8);
            $table->decimal('difference', 30, 8);
            $table->string('currency');
            $table->text('note')->nullable();
            $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['group_id', 'treasury_id', 'as_of']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasury_reconciliations');
    }
};
