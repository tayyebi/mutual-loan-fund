<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A named, seeded preset of advisory rules (Islamic Finance, Microfinance,
        // ...) a group administrator may optionally point their group at. Purely
        // reference data: there is no UI to create one, only FinancialFrameworkSeeder.
        Schema::create('financial_frameworks', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->jsonb('rules')->default('[]');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_frameworks');
    }
};
