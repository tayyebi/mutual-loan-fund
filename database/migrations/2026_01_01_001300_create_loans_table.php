<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('group_memberships')->restrictOnDelete();
            $table->foreignId('cost_center_id')->constrained('cost_centers')->restrictOnDelete();

            // Mandatory: a loan is always governed by the policy version that was
            // active when it was created, for its whole life.
            $table->foreignId('policy_version_id')->constrained('group_policies')->restrictOnDelete();

            $table->string('reference');
            $table->string('currency');
            $table->decimal('principal', 30, 8);
            $table->decimal('interest_rate', 30, 12)->default(0);
            $table->string('interest_method')->default('none');
            $table->unsignedSmallInteger('term_months');
            $table->string('status')->default('requested');
            $table->text('purpose')->nullable();
            $table->text('decision_reason')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->date('first_installment_on')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['group_id', 'reference']);
            $table->index(['group_id', 'status']);
            $table->index(['group_id', 'member_id']);
        });

        DB::statement("
            ALTER TABLE loans ADD CONSTRAINT loans_status_check
            CHECK (status IN ('requested','approved','rejected','disbursed','active','fully_repaid','overdue','defaulted','cancelled'))
        ");
        DB::statement("ALTER TABLE loans ADD CONSTRAINT loans_interest_method_check CHECK (interest_method IN ('none','flat','declining'))");
        DB::statement('ALTER TABLE loans ADD CONSTRAINT loans_principal_check CHECK (principal > 0)');
        DB::statement('ALTER TABLE loans ADD CONSTRAINT loans_interest_rate_check CHECK (interest_rate >= 0)');
        DB::statement('ALTER TABLE loans ADD CONSTRAINT loans_term_check CHECK (term_months >= 1)');

        // transactions.loan_id could not be constrained when transactions was
        // created, because loans did not exist yet.
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('loan_id')->references('id')->on('loans')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['loan_id']);
        });

        Schema::dropIfExists('loans');
    }
};
