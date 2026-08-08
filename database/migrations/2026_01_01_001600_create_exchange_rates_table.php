<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Exchange rates are GLOBAL application data: deliberately no group_id.
        //
        // Every rate is expressed against one gram of 18K gold:
        //     1 g 18K = <units_per_gram_18k> <unit>
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('unit');
            $table->decimal('units_per_gram_18k', 30, 12);
            $table->date('effective_date');

            // Optional standard market input the admin typed instead of the
            // direct 18K gram rate (1 troy oz 24K = X USD), kept for audit. The
            // direct 18K rate remains authoritative.
            $table->decimal('source_troy_ounce_24k', 30, 12)->nullable();
            $table->string('source_note')->nullable();

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['unit', 'effective_date']);
            $table->index(['unit', 'effective_date']);
        });

        DB::statement('ALTER TABLE exchange_rates ADD CONSTRAINT exchange_rates_positive_check CHECK (units_per_gram_18k > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
