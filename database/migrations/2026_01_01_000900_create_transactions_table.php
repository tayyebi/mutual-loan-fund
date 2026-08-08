<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A transaction is a business financial event. It becomes financially
        // effective only once verified and posted to the ledger.
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('treasury_id')->nullable()->constrained('treasuries')->restrictOnDelete();
            $table->foreignId('counter_treasury_id')->nullable()->constrained('treasuries')->restrictOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('group_memberships')->restrictOnDelete();
            $table->foreignId('loan_id')->nullable();
            $table->foreignId('policy_version_id')->nullable()->constrained('group_policies')->restrictOnDelete();

            $table->string('type');
            $table->string('direction');
            $table->decimal('amount', 30, 8);
            $table->string('currency');

            // Second leg, used by exchanges (source -> destination) only.
            $table->decimal('counter_amount', 30, 8)->nullable();
            $table->string('counter_currency')->nullable();

            $table->decimal('fee_amount', 30, 8)->nullable();
            $table->string('fee_currency')->nullable();

            $table->string('status')->default('pending');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();

            // Blockchain evidence. (network, tx_hash) is unique across the whole
            // application so the same transfer can never be credited twice.
            $table->string('network')->nullable();
            $table->string('tx_hash')->nullable();
            $table->string('from_address')->nullable();
            $table->string('to_address')->nullable();
            $table->unsignedInteger('confirmations')->nullable();
            $table->timestamp('chain_verified_at')->nullable();
            $table->jsonb('chain_evidence')->nullable();

            $table->date('occurred_on');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['group_id', 'status']);
            $table->index(['group_id', 'type']);
            $table->index(['group_id', 'occurred_on']);
        });

        DB::statement("
            ALTER TABLE transactions ADD CONSTRAINT transactions_type_check
            CHECK (type IN ('contribution','loan_disbursement','loan_repayment','treasury_transfer','treasury_exchange','fee','adjustment'))
        ");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_direction_check CHECK (direction IN ('in','out','internal'))");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_status_check CHECK (status IN ('pending','verified','rejected'))");
        DB::statement('ALTER TABLE transactions ADD CONSTRAINT transactions_amount_check CHECK (amount > 0)');
        DB::statement('ALTER TABLE transactions ADD CONSTRAINT transactions_counter_amount_check CHECK (counter_amount IS NULL OR counter_amount > 0)');
        DB::statement('ALTER TABLE transactions ADD CONSTRAINT transactions_fee_amount_check CHECK (fee_amount IS NULL OR fee_amount >= 0)');

        // The uniqueness rule that prevents double-crediting a blockchain
        // transfer. Rejected transactions are excluded so that a mistyped hash
        // can be re-submitted after rejection.
        DB::statement("
            CREATE UNIQUE INDEX transactions_chain_identity_idx
            ON transactions (network, tx_hash)
            WHERE tx_hash IS NOT NULL AND status <> 'rejected'
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
