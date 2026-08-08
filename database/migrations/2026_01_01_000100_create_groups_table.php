<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A group is the tenant boundary and represents one independent fund.
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE groups ADD CONSTRAINT groups_status_check CHECK (status IN ('active','suspended'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
