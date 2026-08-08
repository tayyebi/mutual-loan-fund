<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->date('due_date');
            $table->decimal('principal_amount', 30, 8);
            $table->decimal('interest_amount', 30, 8)->default(0);
            $table->decimal('amount', 30, 8);
            $table->decimal('paid_amount', 30, 8)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(['loan_id', 'sequence']);
            $table->index(['group_id', 'status']);
            $table->index(['group_id', 'due_date']);
        });

        DB::statement("ALTER TABLE loan_installments ADD CONSTRAINT loan_installments_status_check CHECK (status IN ('pending','partially_paid','paid','overdue'))");
        DB::statement('ALTER TABLE loan_installments ADD CONSTRAINT loan_installments_amount_check CHECK (amount > 0)');
        DB::statement('ALTER TABLE loan_installments ADD CONSTRAINT loan_installments_paid_check CHECK (paid_amount >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_installments');
    }
};
