<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('status')->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['group_id', 'year', 'month']);
        });

        DB::statement("ALTER TABLE accounting_periods ADD CONSTRAINT accounting_periods_status_check CHECK (status IN ('open','closed'))");
        DB::statement('ALTER TABLE accounting_periods ADD CONSTRAINT accounting_periods_month_check CHECK (month BETWEEN 1 AND 12)');
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
    }
};
