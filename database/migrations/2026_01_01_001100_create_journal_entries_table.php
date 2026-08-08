<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->string('entry_number');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->restrictOnDelete();
            $table->foreignId('accounting_period_id')->nullable()->constrained('accounting_periods')->restrictOnDelete();
            $table->foreignId('reverses_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->string('template')->nullable();
            $table->date('entry_date');
            $table->date('posting_date')->nullable();
            $table->string('functional_currency');
            $table->text('description');
            $table->text('reason')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'entry_number']);
            $table->index(['group_id', 'status']);
            $table->index(['group_id', 'entry_date']);
        });

        DB::statement("ALTER TABLE journal_entries ADD CONSTRAINT journal_entries_status_check CHECK (status IN ('draft','posted','reversed'))");

        // One journal entry per business transaction (a reversal gets its own
        // entry and references the original through reverses_entry_id).
        DB::statement("
            CREATE UNIQUE INDEX journal_entries_transaction_idx
            ON journal_entries (transaction_id)
            WHERE transaction_id IS NOT NULL AND reverses_entry_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
