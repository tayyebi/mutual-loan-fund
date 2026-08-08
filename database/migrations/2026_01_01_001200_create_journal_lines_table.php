<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->restrictOnDelete();

            // Native amounts: the currency the money actually moved in.
            $table->string('currency');
            $table->decimal('debit', 30, 8)->default(0);
            $table->decimal('credit', 30, 8)->default(0);

            // Functional amounts: the same line expressed in the group's
            // functional currency. Balance is enforced on these, which is what
            // makes multi-currency entries (exchanges) representable.
            $table->string('functional_currency');
            $table->decimal('functional_debit', 30, 8)->default(0);
            $table->decimal('functional_credit', 30, 8)->default(0);

            // Valuation snapshots frozen at posting time. Later rate changes
            // must never alter a historical valuation.
            $table->decimal('exchange_rate_snapshot', 30, 12)->nullable();
            $table->decimal('gold_rate_snapshot', 30, 12)->nullable();
            $table->decimal('gold_value_snapshot', 30, 8)->nullable();

            $table->string('description')->nullable();
            $table->unsignedSmallInteger('sequence')->default(0);
            $table->timestamps();

            $table->index(['group_id', 'account_id']);
            $table->index(['group_id', 'cost_center_id']);
            $table->index('journal_entry_id');
        });

        DB::statement('ALTER TABLE journal_lines ADD CONSTRAINT journal_lines_debit_check CHECK (debit >= 0)');
        DB::statement('ALTER TABLE journal_lines ADD CONSTRAINT journal_lines_credit_check CHECK (credit >= 0)');
        DB::statement('ALTER TABLE journal_lines ADD CONSTRAINT journal_lines_functional_debit_check CHECK (functional_debit >= 0)');
        DB::statement('ALTER TABLE journal_lines ADD CONSTRAINT journal_lines_functional_credit_check CHECK (functional_credit >= 0)');

        // debit > 0 XOR credit > 0 — a line is one side or the other, never both
        // and never neither.
        DB::statement('
            ALTER TABLE journal_lines ADD CONSTRAINT journal_lines_side_check
            CHECK ((debit > 0 AND credit = 0) OR (credit > 0 AND debit = 0))
        ');

        // The functional side must match the native side.
        DB::statement('
            ALTER TABLE journal_lines ADD CONSTRAINT journal_lines_functional_side_check
            CHECK ((debit > 0 AND functional_credit = 0) OR (credit > 0 AND functional_debit = 0))
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};
